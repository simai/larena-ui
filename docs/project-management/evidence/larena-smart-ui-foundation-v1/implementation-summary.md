# Implementation Summary

Batch B0 established the branch, launch context and evidence packet. Batch B1
keeps the accepted registry/manager/reference prerequisite and adds one typed
projection path for developer and AI discovery.

The manual Admin component catalog and its renderer were removed instead of
being preserved as a second registry. `AdminDataviewRenderer` now resolves the
canonical `ui.dataview` manifest and renderer through one `SmartRegistry` and
`SmartManager` pair.

`SmartCatalogProjection` returns localized `SmartCatalogEntry` values in stable
`atlas.order -> component key` order. A visible manifest must provide valid
RU/EN metadata, controls, examples, provenance and four independent readiness
flags. Unknown manifests, hidden manifests, missing renderers and incomplete
visible metadata fail closed.

`SmartAiCatalogProjection` returns deterministic JSON from those same entries.
It has no timestamps, executable handler/class/template fields or absolute
local paths. Unsafe provenance is discarded; manifest files are represented by
a package-relative `resources/smart/.../manifest.json` reference and SHA-256.
Complex frontend props are reported as an unavailable example with a stable
reason instead of a fabricated HTML invocation.

Batch B2 promotes `ui.input` and `ui.dataview` to complete visible entries and
registers seven additional archetypes beside `ui.button`: `ui.textarea`,
`ui.checkbox`, `ui.dropdown`, `ui.pagination`, `ui.badge`, `ui.alert` and
`ui.modal`. The explicit contribution contains exactly those ten components in
stable `atlas.order -> key` order. Every manifest points to the pinned
Simai Framework source revision, uses only lock-backed props plus the bounded
table-data/dropdown-options adapters, and declares an asset graph exactly equal
to the runtime graph for its custom element.

All entries provide RU/EN metadata, supported controls, states, accessibility
notes and readiness `true, true, false, false`. The test library changes and
renders every one of the 48 exposed controls, so a catalog control cannot pass
merely by accepting a value without changing real SmartManager output.
Structured dropdown options and dataview data retain working backend examples
and renders while reporting frontend snippets as explicitly unavailable.

The UI Lab integration releases the initial focus taken by an already-open
inline modal preview after the pinned runtime becomes ready. This keeps the
real modal behavior intact when a developer opens it, while allowing the
page-level skip link to remain the first keyboard destination on catalog,
reference and recipe demonstrators. The correction also resets the browser's
sequential focus starting point to the document body after releasing the
preview panel, because blurring the panel alone leaves the next Tab position
inside that modal in Chromium.

Source event projection now accepts bounded colon-separated names required by
`sf-dropdown:change` and the real `modal:*` lifecycle. Spaces, slashes,
malformed separators and `on*` handler-style names still fail closed, and no
backend handler binding is authorized.
