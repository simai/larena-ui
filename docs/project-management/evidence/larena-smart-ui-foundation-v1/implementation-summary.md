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
