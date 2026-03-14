// index.js — Lucide Icon Picker Block

(function (blocks, element, components, editor, blockEditor) {
  const { createElement: el, useState, useEffect } = element;
  const { Button, TextControl, RangeControl, PanelBody } = components;
  const { InspectorControls, BlockControls, AlignmentToolbar, useBlockProps } =
    blockEditor;
  const { PanelColorSettings } = editor;

  const BATCH_SIZE = 50;
  const ICON_BASE_PATH = LucideIconsData.basePath;
  const ICONS_LIST = LucideIconsData.icons;

  blocks.registerBlockType("custom/lucide-icon", {
    title: "Lucide Icon",
    icon: "star-filled",
    category: "design",
    apiVersion: 3,

    attributes: {
      icon: { type: "string", default: "" },
      size: { type: "number", default: 48 },
      stroke: { type: "number", default: 2 },
      alignment: { type: "string", default: "center" },
      color: { type: "string", default: "" },
      svgContent: { type: "string", default: "" },
    },

    supports: {
      align: true,
      color: false,
    },

    edit: function (props) {
      const { attributes, setAttributes } = props;
      const [visibleCount, setVisibleCount] = useState(BATCH_SIZE);
      const [searchTerm, setSearchTerm] = useState("");
      const [isSelecting, setIsSelecting] = useState(!attributes.icon);

/* ========================================
   Load SVG when icon change
======================================== */
      useEffect(() => {
        if (attributes.icon) {
          fetch(`${ICON_BASE_PATH}${attributes.icon}.svg`)
            .then((response) => response.text())
            .then((svgText) => {
              const parser = new DOMParser();
              const doc = parser.parseFromString(svgText, "image/svg+xml");
              const svgElement = doc.querySelector("svg");
              if (svgElement) {
                setAttributes({ svgContent: svgElement.innerHTML });
              }
            })
            .catch((err) => console.error("Error loading SVG:", err));
        }
      }, [attributes.icon]);

      const blockProps = useBlockProps({
        className:
          "lucide-block-wrapper" +
          (isSelecting || !attributes.icon ? " lucide-selector-mode" : ""),
        style: {
          textAlign: attributes.alignment,
        },
      });

      const filteredIcons = ICONS_LIST.filter((name) =>
        name.toLowerCase().includes(searchTerm.toLowerCase()),
      );
      const visibleIcons = filteredIcons.slice(0, visibleCount);


/* ========================================
       Inspector Controls (sidebar)
======================================== */
      const inspector = el(
        InspectorControls,
        {},
        attributes.icon &&
          el(
            PanelBody,
            { title: "Icon Settings", initialOpen: true },
            el(RangeControl, {
              label: "Size (px)",
              value: attributes.size,
              onChange: (val) => setAttributes({ size: val }),
              min: 16,
              max: 128,
            }),
            el(RangeControl, {
              label: "Stroke Width",
              value: attributes.stroke,
              onChange: (val) => setAttributes({ stroke: val }),
              min: 0.5,
              max: 8,
              step: 0.5,
            }),
            el(
              Button,
              {
                isSecondary: true,
                onClick: () => setIsSelecting(true),
                className: "lucide-change-icon-btn",
              },
              "Change Icon",
            ),
          ),
        attributes.icon &&
          el(PanelColorSettings, {
            title: "Color Settings",
            initialOpen: false,
            colorSettings: [
              {
                value: attributes.color,
                onChange: (color) => setAttributes({ color }),
                label: "Icon Color",
              },
            ],
          }),
      );

        
        
/* ========================================
       Block Controls (toolbar)
======================================== */
      const blockControls =
        attributes.icon &&
        el(
          BlockControls,
          {},
          el(AlignmentToolbar, {
            value: attributes.alignment,
            onChange: (alignment) => setAttributes({ alignment }),
          }),
        );

      // Vista previa del icono seleccionado
      if (attributes.icon && !isSelecting && attributes.svgContent) {
        return el("div", blockProps, [
          inspector,
          blockControls,
          el("svg", {
            className: "lucide-icon-preview",
            width: attributes.size,
            height: attributes.size,
            viewBox: "0 0 24 24",
            fill: "none",
            stroke: attributes.color || "currentColor",
            strokeWidth: attributes.stroke,
            strokeLinecap: "round",
            strokeLinejoin: "round",
            dangerouslySetInnerHTML: { __html: attributes.svgContent },
          }),
        ]);
      }


/* ========================================
             Icons Selector
======================================== */
      const searchField = el(TextControl, {
        value: searchTerm,
        onChange: (val) => {
          setSearchTerm(val);
          setVisibleCount(BATCH_SIZE);
        },
        placeholder: "Search icons...",
        className: "lucide-search-field",
      });

      const iconsGrid = el(
        "div",
        {
          className: "lucide-icons-grid",
        },
        visibleIcons.map((iconName) =>
          el(
            Button,
            {
              key: iconName,
              className: "lucide-icon-button",
              onClick: () => {
                setAttributes({ icon: iconName });
                setIsSelecting(false);
              },
              title: iconName,
            },
            el("img", {
              src: `${ICON_BASE_PATH}${iconName}.svg`,
              width: 24,
              height: 24,
              alt: iconName,
              className: "lucide-icon-img",
            }),
          ),
        ),
      );

      const loadMoreButton =
        visibleCount < filteredIcons.length
          ? el(
              Button,
              {
                isSecondary: true,
                onClick: () => setVisibleCount(visibleCount + BATCH_SIZE),
                className: "lucide-load-more-btn",
              },
              `Load more (${filteredIcons.length - visibleCount} remaining)`,
            )
          : null;

      const resultCount = el(
        "p",
        {
          className: "lucide-result-count",
        },
        `${filteredIcons.length} icons found`,
      );

      const cancelButton = attributes.icon
        ? el(
            Button,
            {
              isLink: true,
              onClick: () => setIsSelecting(false),
              className: "lucide-cancel-btn",
            },
            "← Back to icon",
          )
        : null;

      return el("div", blockProps, [
        inspector,
        el("div", { className: "lucide-selector-panel" }, [
          el("div", { className: "lucide-selector-header" }, [
            el(
              "h4",
              { className: "lucide-selector-title" },
              attributes.icon ? "Change Icon" : "Select a Lucide Icon",
            ),
          ]),
          el("div", { className: "lucide-selector-search" }, [
            searchField,
            resultCount,
          ]),
          iconsGrid,
          el("div", { className: "lucide-selector-footer" }, [
            loadMoreButton,
            cancelButton,
          ]),
        ]),
      ]);
    },

    save: function (props) {
      const { attributes } = props;
      if (!attributes.icon || !attributes.svgContent) return null;

      const blockProps = useBlockProps.save({
        style: {
          textAlign: attributes.alignment,
        },
      });

      return el(
        "div",
        blockProps,
        el("svg", {
          width: attributes.size,
          height: attributes.size,
          viewBox: "0 0 24 24",
          fill: "none",
          stroke: attributes.color || "currentColor",
          strokeWidth: attributes.stroke,
          strokeLinecap: "round",
          strokeLinejoin: "round",
          dangerouslySetInnerHTML: { __html: attributes.svgContent },
        }),
      );
    },
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.components,
  window.wp.editor,
  window.wp.blockEditor,
);
