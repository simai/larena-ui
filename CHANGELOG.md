# Changelog

## Unreleased

- Preserve the selected record operation when a hydrated table action opens
  the Minimal CMS viewer, editor, delete confirmation or restore form.

- Present compact Dataview View and Search controls without visible field
  labels while retaining their programmatic labels for assistive technology.

- Publish the immutable Framework runtime artifact for the pinned
  `sf-v5.4.0-local.28-3db5af38-0b891dfb` UI/Smart revision pair.

- Render table action icons from their Smart descriptors instead of positional CSS glyph substitutions.

- Connect native pagination to applied backend queries and append backend-projected rows with one Show more control, preserving row actions and selection counts.

- Keep expanded collection filters in document flow so Search and Apply remain reachable.

- Hydrate native table column settings and serialize server saves; show save failures and protect pending changes on navigation.

- Register the source-backed Admin Menu, Breadcrumbs, Icon Button, Avatar,
  Tag and Toggle primitives required by the backend-composed Admin shell.
- Move advanced Dataview query controls behind a reusable progressive
  disclosure while keeping filtering, sorting and pagination server-owned.
- Render bounded structured Admin Menu and Breadcrumb item data through the
  Smart allowlist without accepting arbitrary HTML.
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
