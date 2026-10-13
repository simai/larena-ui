SF.RuleLoader["cl-table"] = {
  tags: ["sf-table"],
  type: "smart",
  mode: "smart",
  js: true,
  css: true,
  // lazy: rendered later -- filter editors, cell editors, menus -- fetched after the page is ready (2026-10-12).
  relation: [
    { name: "cl-checkbox" },
    // A colour property draws sf-color in a cell as well as in a card.
    { name: "cl-color", lazy: true },
    // A string whose variant is a phone draws sf-country-code. The rule fence
    // reads a component's own templates, and this element comes from the shared
    // renderer set instead -- so nothing would have caught its absence until a
    // page showed an element that never upgraded.
    { name: "cl-country-code", lazy: true },
    { name: "cl-buttons" },
    { name: "cl-icon-buttons" },
    { name: "icons" },
    { name: "cl-inputs" },
    { name: "cl-tags" },
    { name: "cl-pagination" },
    { name: "cl-spinner" },
    { name: "cl-datepicker", lazy: true },
    { name: "cl-dropdown" },
    { name: "cl-tabs", lazy: true },
    { name: "cl-context-menu", lazy: true },
    // The windows' lists are reordered with sf-sortable (owner, 2026-10-13).
    { name: "cl-sortable", lazy: true },
    { name: "cl-avatar" },
    { name: "cl-badges" },
    { name: "cl-list-item" },
    { name: "cl-modal", lazy: true },
    { name: "cl-range-slider", lazy: true },
  ],
};
