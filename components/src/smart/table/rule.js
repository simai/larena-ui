SF.RuleLoader["cl-table"] = {
  tags: ["sf-table"],
  type: "smart",
  mode: "smart",
  js: true,
  css: true,
  relation: [
    { name: "cl-checkbox" },
    // A colour property draws sf-color in a cell as well as in a card.
    { name: "cl-color" },
    // A string whose variant is a phone draws sf-country-code. The rule fence
    // reads a component's own templates, and this element comes from the shared
    // renderer set instead -- so nothing would have caught its absence until a
    // page showed an element that never upgraded.
    { name: "cl-country-code" },
    { name: "cl-buttons" },
    { name: "cl-icon-buttons" },
    { name: "icons" },
    { name: "cl-inputs" },
    { name: "cl-tags" },
    { name: "cl-pagination" },
    { name: "cl-spinner" },
    { name: "cl-datepicker" },
    { name: "cl-dropdown" },
    { name: "cl-tabs" },
    { name: "cl-context-menu" },
    { name: "cl-avatar" },
    { name: "cl-badges" },
    { name: "cl-list-item" },
    { name: "cl-modal" },
    { name: "cl-range-slider" },
  ],
};
