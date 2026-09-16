# Dataview host lifecycle candidate

The existing Smart event bridge tracks each workbench instance and disposes only the subscriptions and pending requests it owns. Reinsertion reconnects once; child replacement invalidates the previous binding. Query sequence ordering remains per instance. This candidate does not alter domain routes, permissions or Framework grammar.

Run `composer test:dataview-host` with the supported Node runtime on PATH. Tests execute the production bridge with controlled transport responses: duplicate discovery, independent instances, out-of-order results, removal, late output, remount and child replacement. They do not prove Chrome hydration or a complete two-source list.

This is an isolated candidate based on the existing owner-bound composition branch. Do not install its package revision until owner integration, browser acceptance and exact Root pins are completed. Rollback is the preceding package revision. Row-ID/action payload migration remains a separate Framework/public-event boundary.

## Isolated integration resource contract

The changed bridge uses resource revision `20260916-dataview-host-lifecycle-59e27f70219c`. The matching Admin candidate must pin SHA-256 `59e27f70219c23c59af066a4289b4fafc76d9b2034c0c8f629c0af73b6212127`; an old expectation correctly refuses activation. A successful controlled-port probe alone does not establish this cross-package contract.

## Registered list presentation continuation

`RegisteredListRenderer` uses the existing `dataview.table/default` composite through SmartManager. A caller supplies an authorized DataviewDatasetSnapshot, a stable per-placement instance ID and trusted registered scalar columns. The renderer projects only those columns and preserves integer/opaque string record IDs. Table and pagination have separate deterministic IDs per placement; IDs are not inferred from cell text or data hashes.

`SmartManager::renderView(..., requestDataBindings: ...)` separates server-request data from structural childProps. Bindings use the existing nested `_props`/`_children` shape but may override only manifest-declared `request_bound_properties`. Initially ui.dataview declares only data. Unknown children or attempts to alter id/structure through the data channel fail. Structural Smart View documents retain their no-code/no-HTML validation; request data is not persisted as a definition, Recipe or common snapshot. It passes component props validation and safe JSON serialization. Callers must supply authorized, registered, projected data; never populate the binding tree directly from browser or composition JSON.

This local candidate renders scalar cells and leaves action/selection/settings flags off. It is not final interactive-list acceptance. Owner presentation for links/badges, registered filters/preferences, Framework Document integration and Chrome hydration remain work. The renderer now emits an escaped semantic table inside noscript for disabled-JavaScript viewing; browser-disabled-JS acceptance is still pending. Public action ports and exact Recipe 1.0.1 supply remain Framework owner prerequisites.

Tests use the package's own source even when vendor has an optimized classmap pointing to another worktree. Full native quality gate and independent PHPStan level 5 of the renderer/SmartManager pass. No runtime distribution or live app is switched.


## Composition Document continuation candidate

`resources/composition/registered-list.type-manifest.json` registers `larena.registered-list` through the existing trusted product registry. The static node contains `source_key`, `column_preset` and `title`; it cannot contain rows, a custom endpoint, actor or scope. Framework renders a static host during publication. It does not resolve private data at that stage.

`RegisteredDocumentRenderer` is a PHP rendering adapter for normalized Documents, not a Recipe compiler. It requires an upstream conformance validator and preflights all registered nodes before renderer callbacks. `RegisteredListDocumentNodeRenderer` supplies a pure registration check and then resolves an authorized request-local typed snapshot through a trusted server closure. Column presets are server-owned. Two instances retain distinct node/component IDs.

This remains an isolated candidate. The boundary tests use validator spies and prove ordering, source/preset refusal and safe rendering; they do not prove complete Framework PHP conformance. Production wiring requires the exact pinned schema/type validator, isolated Root route and Chrome acceptance. Recipe checks currently use the exact bundled 1.0.0 pair and must be repeated on the accepted 1.0.1 delivery. No request-local rows or personalized HTML may be activated as a shared compiled snapshot.

Run `composer test:composition-list` with an exact `SIMAI_UI_ROOT`. The command fails when unconfigured rather than treating missing conformance input as a passing check.


### Pinned schema shape gate

`FrameworkDocumentSchemaValidator::fromPinnedSchema(path, digest)` reads the owner-provided Framework Document schema and checks its exact bytes and identity. It implements only the keywords used by that schema; an encountered unsupported keyword or external reference fails closed. This is a Document shape adapter, not an independent normative schema or complete Framework semantic validator.

`document(rawJson)` validates JSON objects before conversion to associative PHP arrays, retaining the distinction between `{}` and `[]` during checks. `binding(rawJson)` creates a request-local validation closure that accepts only the exact PHP document decoded from those checked bytes. Use this closure in `RegisteredDocumentRenderer`; a changed source key or node after validation is refused. The raw JSON must be the normalized compiled Document from trusted publication, not arbitrary browser JSON.

Manifest checks still own registered type/profile/slot/presentation semantics and supported extensions. Complete conformance, exact 1.0.1 delivery, persisted snapshot wiring and browser acceptance remain pending. `composer test:composition-schema` requires the exact `SIMAI_UI_ROOT` and the independently recorded schema digest; it does not silently skip absent input.
