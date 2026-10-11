SF.RuleLoader["cl-admin-menu"] = {
  tags: ["sf-admin-menu"],
  type: "smart",
  mode: "smart",
  js: true,
  // The navigation shell brings its own stylesheet; the `items` menu is styled
  // by the free admin-menu component and needs none.
  css: true,
  relation: [
    { name: "admin-menu" },
    { name: "icons" },
    { name: "inputs" },
    { name: "badges" },
    { name: "cl-buttons" },
    { name: "cl-context-menu" },
    { name: "cl-badges" },
    { name: "cl-icons" },
    { name: "cl-icon-buttons" },
    { name: "cl-inputs" },
    // The navigation shell's edit mode moves rows with sf-sortable; a tag the
    // menu renders is a tag it fetches.
    { name: "cl-sortable" },
  ],
};
