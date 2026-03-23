/**
 * Map CPT Meta Box — Address autocomplete (Mapbox Geocoding)
 * Each post = one location. Autocomplete fills lat/lng.
 */
(function ($) {
  'use strict';

  const MAPBOX_API = 'https://api.mapbox.com/geocoding/v5/mapbox.places/';
  const DEBOUNCE_MS = 300;
  const MIN_CHARS = 3;

  function fillCoords($input, lng, lat) {
    const target = $input.data('fill-target');
    if (!target) return;
    const selectors = target.split(',');
    $(selectors[0].trim()).val(lat);
    $(selectors[1].trim()).val(lng);
  }

  function initAddressAutocomplete($input) {
    const token = typeof pkMaps !== 'undefined' && pkMaps.mapboxToken ? pkMaps.mapboxToken : '';
    if (!token) return;

    let debounceTimer;
    let $dropdown;

    $input.on('input', function () {
      clearTimeout(debounceTimer);
      if ($dropdown) { $dropdown.remove(); $dropdown = null; }

      const query = $(this).val().trim();
      if (query.length < MIN_CHARS) return;

      debounceTimer = setTimeout(function () {
        const url = MAPBOX_API + encodeURIComponent(query) + '.json?access_token=' + token + '&limit=10';
        fetch(url)
          .then(function (r) { return r.json(); })
          .then(function (data) {
            const features = data.features || [];
            if (!features.length) return;

            $dropdown = $('<div class="pk-map-autocomplete-dropdown"></div>');
            features.forEach(function (f) {
              const coords = f.geometry.coordinates;
              const $item = $('<div class="pk-map-autocomplete-item"></div>')
                .text(f.place_name || '')
                .on('mousedown', function (e) {
                  e.preventDefault();
                  $input.val(f.place_name || '');
                  fillCoords($input, coords[0], coords[1]);
                  $dropdown.remove();
                  $dropdown = null;
                });
              $dropdown.append($item);
            });
            $input.after($dropdown);
          })
          .catch(function () {});
      }, DEBOUNCE_MS);
    });

    $input.on('blur', function () {
      setTimeout(function () {
        if ($dropdown) { $dropdown.remove(); $dropdown = null; }
      }, 200);
    });

    $(document).on('click.pkMapAutocomplete', function (e) {
      if (!$(e.target).closest('.pk-map-address-autocomplete, .pk-map-autocomplete-dropdown').length) {
        if ($dropdown) { $dropdown.remove(); $dropdown = null; }
      }
    });
  }

  $(document).ready(function () {
    $('.pk-map-address-autocomplete').each(function () {
      initAddressAutocomplete($(this));
    });
  });
})(jQuery);
