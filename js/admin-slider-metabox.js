/**
 * Slider CPT Meta Box — Client-side logic
 *
 * Handles:
 * - Show/hide panels based on selected slider type
 * - Disable hidden panel fields (prevents form data collision)
 * - Add slides via <template> cloneNode (single source of truth in PHP)
 * - Remove slides + reindex field names
 * - WordPress Media uploader for slide images
 */
(function ($) {
  "use strict";

  function syncPanelFields() {
    $(".pk-slider-type-panel").each(function () {
      const isVisible = $(this).is(":visible");
      $(this)
        .find("input, textarea, select")
        .prop("disabled", !isVisible);
    });
  }

  function reindexSlides($repeater) {
    $repeater.find(".pk-slider-slide").each(function (idx) {
      const $slide = $(this);
      $slide.attr("data-index", idx);
      $slide.find(".pk-slide-header strong").text("Slide " + (idx + 1));

      $slide.find("[name]").each(function () {
        const name = $(this).attr("name");
        $(this).attr(
          "name",
          name.replace(/pk_slider_slides\[\w+\]/, "pk_slider_slides[" + idx + "]")
        );
      });
    });
  }

  $(document).ready(function () {
    syncPanelFields();

    // --- Type switcher ---
    $("#pk_slider_type").on("change", function () {
      const selected = $(this).val();
      $(".pk-slider-type-panel").hide();
      $('.pk-slider-type-panel[data-type="' + selected + '"]').show();
      syncPanelFields();
    });

    // --- Add slide (clone from <template>) ---
    $(document).on("click", ".pk-add-slide", function () {
      const type = $(this).data("type");
      const $repeater = $(this).siblings(".pk-slider-repeater");
      const tpl = document.getElementById("pk-tpl-" + type);

      if (!tpl) return;

      const clone = tpl.content.cloneNode(true);
      $repeater.append(clone);
      reindexSlides($repeater);
    });

    // --- Remove slide ---
    $(document).on("click", ".pk-remove-slide", function () {
      const $slide = $(this).closest(".pk-slider-slide");
      const $repeater = $slide.closest(".pk-slider-repeater");

      if ($repeater.find(".pk-slider-slide").length <= 1) {
        alert("At least one slide is required.");
        return;
      }

      $slide.remove();
      reindexSlides($repeater);
    });

    // --- Media uploader ---
    $(document).on("click", ".pk-slide-media-btn", function (e) {
      e.preventDefault();
      const $btn = $(this);
      const $container = $btn.closest(".pk-slide-image");
      const $input = $container.find(".pk-slide-image-id");
      const $preview = $container.find(".pk-slide-image-preview");
      const title = $btn.data("title") || "Select Media";

      const uploader = wp.media({
        title: title,
        button: { text: "Use this media" },
        multiple: false,
      });

      uploader.on("select", function () {
        const attachment = uploader.state().get("selection").first().toJSON();
        $input.val(attachment.id);
        const url = attachment.sizes?.thumbnail?.url || attachment.url;
        $preview.html(
          '<img src="' +
            url +
            '" style="max-height:50px; border:1px solid #ddd; border-radius:3px;">'
        );
      });

      uploader.open();
    });
  });
})(jQuery);
