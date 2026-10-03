# Changelog

- Keep unsaved column choices visible on a revision conflict and let the user refresh the layer revision before retrying.

## Unreleased — current Framework pair

- Pin the Framework pair `ui-c051aa62458e-smart-85d1858d7632` (registry `d5a3b15e`), with the column move and pinned-cell fixes, replacing `ui-c33663c3822b-smart-5e974280513e`.
- Pin the Framework pair `ui-c33663c3822b-smart-5e974280513e` (registry `b68ec231`), with the table fixes and count on request, replacing `ui-ed549e7282ef-smart-6a58ac134bdb`.
- Pin the Framework pair `ui-ed549e7282ef-smart-6a58ac134bdb` (registry `65f2a16a`) with the data view table behaviour, replacing `ui-5e466412cac0-smart-c969ab09e15b`; the bridge declares data view port 1.2.0 and the pagination allow-list gains `action-choose-label`, `actions-region-label` and `clear-selection-label`.
- Pin the Framework pair `ui-5e466412cac0-smart-c969ab09e15b` (registry `640a1653`), handed over by the Framework owner on 2026-10-03 with the data view table presentation, replacing `ui-5e466412cac0-smart-a794c2e9c492`.
- Pin the Framework pair `ui-5e466412cac0-smart-a794c2e9c492` (registry `6932972a`), handed over by the Framework owner on 2026-10-03 with the data view table defect fixes, replacing `ui-3b0f4adecf3e-smart-ff82c2636e0e`. The host port bridge no longer appends the rows of "Show more": the data view does it.
- The `sf-pagination` allow-list and the `ui.pagination` contract use the label attributes of the pinned Framework manifest (`previous-label`, `next-label`, `last-label`, `actions-label`); the stale names `previous-page-label`, `next-page-label`, `last-page-text`, `go-to-page-label` and `show-total-text` from 2026-08-20 are gone, so every pagination label can be translated.
- The host port bridge adds the rows of "Show more" under the rows already shown, with their revisions, instead of replacing the page.
- Pin the Framework pair `ui-3b0f4adecf3e-smart-ff82c2636e0e` (registry `945f9a99`), bound by the Framework owner on 2026-10-03, replacing `ui-227b90a6355a-smart-5c48c415d04d`.
- Pin the Framework pair `ui-227b90a6355a-smart-5c48c415d04d` (registry `70a87a25`), bound by the Framework owner on 2026-10-02, replacing `ui-f36cee75e553-smart-ae58fee26055`.
- Pin the Framework pair `ui-f36cee75e553-smart-ae58fee26055` (registry `8ce17733`), bound by the Framework owner on 2026-10-02, replacing `ui-0315c0ffebb6-smart-41fd1892b345`.
- Pin the Framework pair `ui-0315c0ffebb6-smart-41fd1892b345` (registry `c66d7212`), bound by the Framework owner on 2026-10-01, replacing `ui-e5a1228a9d8a-smart-c184f5944ae6`.

## Unreleased

- Pin Framework Core `56cd91e1d7a3` and Smart `90208335806d` with registry `7d7edec0`. The `sf-data-view` selected action now reaches the host port with current revisions for one or more checked records; row actions retain their separate permission check.

- Bind a slotted CMS `sf-data-view` to the scoped 1.1.0 host port. The composite owns query lifecycle; the Larena bridge refuses records without owner-projected `display_values` and never inserts raw reference values into table cells.

- Register the pinned `sf-data-view` script and its table/pagination dependencies in Larena's runtime lock so the CMS host can load the new composite through the verified asset graph.

- Pin Framework Core `56cd91e1d7a3` and Smart `5e7adda70be4` with registry `980b3a9f`: the separate `sf-data-view` and endpoint-backed filter options are available for Larena laboratory integration.

- Pin the Framework pair ui-56cd91e1d7a3-smart-903ad66c4f4f: Stage C declares simai.dataview-port 1.0.0 on sf-table; host conformance remains to be proven

- Pin the Framework pair ui-cb1cda301648-smart-81741eac168d: Core cb1cda30 whose manifests declare their own version (smart manifest 2.1.0, composition type manifest 1.1.0); standards reissued as 1.0.2.

- Pin the Framework pair ui-bc8dfd7e4bdb-smart-81741eac168d: data view stage B (events, data bindings and settings persistence in the type manifest).

- Pin the Framework pair ui-d81ccde2bdd5-smart-a916bbadf3aa: data view stage A (template delete event, instance isolation, declared data states).

- Pin the Framework pair ui-d81ccde2bdd5-smart-d448fb5563cc: keyboard filter chips, bubbling modal events and the sf-pagination "for all" fix.

- Pin the Framework pair ui-1f1c9d42d964-smart-6c5d313aca4d with sf-table host intents and bubbling drawer events on top of ui-1f1c9d42d964-smart-121e8882d016, which brought shared Floating UI positioning and the composition editor surfaces as Smart components; ui-eb212efe40cd-smart-9e8d8e762e03 stays available for rollback.

- Connect the CMS records list pagination and table preferences through its query form; ui.input declares its native input type and ui.checkbox submits as a list.

- Let the CMS records pagination span the list as in the table design.

- Pin the Framework pair ui-eb212efe40cd-smart-9e8d8e762e03 with the docked sf-drawer, the composition editor overlay, sortable drag and drop and the inline text editor; the previous pair stays available for rollback.

- Pin the Framework pair ui-2b9aa9635ad0-smart-db547bb87b6b with named regions, scope routing and region field kinds; bind registered lists to the published sf-table ports and answer route context queries through the table sequence.

- Dispose Dataview host listeners and abort pending requests on removal or child replacement; reconnect inserted instances once and ignore detached responses.

- Pin the Auth-ready generated Smart runtime that renders safe structured
  table links while preserving the separately identified immutable carrier.

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


### Declarative Document schema candidate

- Added a pinned Framework Document schema shape gate and a binding that refuses changes after validation. JSON object/array identity is checked before PHP conversion.
- Added required configured schema and registered-list Recipe checks to the UI quality gate. Type semantics, Root adoption, exact Recipe 1.0.1 and Chrome acceptance remain pending; this is not a release readiness claim.
