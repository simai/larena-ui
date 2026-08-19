# Changelog

## Unreleased

- Pin the local Developer Preview to the reproducible Framework rule-registry
  candidate that removes merge markers, duplicate rule names and the phantom
  `cl-alert` stylesheet request.
- Advance the local candidate to a reproducible full-core regeneration with
  valid rule JavaScript and all referenced font assets present.
- Render optional same-origin continuation links inside `ui.pagination` without exposing opaque paging state to sibling components.
- Compose the saved Dataview selector as a replaceable `ui.dropdown` child of the JSON-defined toolbar.
- Add the replaceable `admin.record_editor` Smart View shell with server-composed field and action slots.

### Verified

- Verify the Minimal CMS Smart Component allowlist, renderer, props and asset safety contract.
- Add the validated Smart View v1 descriptor, registered view resolution and public `Smart::renderView()` path.
- Add bounded recursive composite rendering for Admin collection, table Dataview and toolbar views with exact child asset aggregation.
- Allow fail-closed nested child-prop overrides and compose the Dataview toolbar from replaceable input and submit-button views.
- Add a replaceable dropdown Smart View and compose filter, sort and page-size controls into declared toolbar slots.

### Documentation

- Record the accepted Minimal CMS v1 Smart Component, allowlist and asset ownership boundary.
- Document replaceable JSON views, presets, modifiers and the server-owned composition boundary.

### Non-claims

- No runtime behavior, public contract or package version changes are introduced by the B0 preparation.
