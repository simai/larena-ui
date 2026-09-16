# Dataview host lifecycle candidate

The existing Smart event bridge tracks each workbench instance and disposes only the subscriptions and pending requests it owns. Reinsertion reconnects once; child replacement invalidates the previous binding. Query sequence ordering remains per instance. This candidate does not alter domain routes, permissions or Framework grammar.

Run `composer test:dataview-host` with the supported Node runtime on PATH. Tests execute the production bridge with controlled transport responses: duplicate discovery, independent instances, out-of-order results, removal, late output, remount and child replacement. They do not prove Chrome hydration or a complete two-source list.

This is an isolated candidate based on the existing owner-bound composition branch. Do not install its package revision until owner integration, browser acceptance and exact Root pins are completed. Rollback is the preceding package revision. Row-ID/action payload migration remains a separate Framework/public-event boundary.

## Isolated integration resource contract

The changed bridge uses resource revision `20260916-dataview-host-lifecycle-59e27f70219c`. The matching Admin candidate must pin SHA-256 `59e27f70219c23c59af066a4289b4fafc76d9b2034c0c8f629c0af73b6212127`; an old expectation correctly refuses activation. A successful controlled-port probe alone does not establish this cross-package contract.

## Registered list presentation continuation

`RegisteredListRenderer` uses the existing `dataview.table/default` composite through SmartManager. A caller supplies an authorized DataviewDatasetSnapshot, a stable per-placement instance ID and trusted registered scalar columns. The renderer projects only those columns and preserves integer/opaque string record IDs. Table and pagination have separate deterministic IDs per placement; IDs are not inferred from cell text or data hashes.

`SmartManager::renderView(..., requestDataBindings: ...)` separates server-request data from structural childProps. Bindings use the existing nested `_props`/`_children` shape but may override only manifest-declared `request_bound_properties`. Initially ui.dataview declares only data. Unknown children or attempts to alter id/structure through the data channel fail. Structural Smart View documents retain their no-code/no-HTML validation; request data is not persisted as a definition, Recipe or common snapshot. It passes component props validation and safe JSON serialization. Callers must supply authorized, registered, projected data; never populate the binding tree directly from browser or composition JSON.

This local candidate renders scalar cells and leaves action/selection/settings flags off. It is not final interactive-list acceptance. Owner presentation for links/badges, registered filters/preferences, Framework Document integration, meaningful no-JS list HTML and Chrome hydration remain work. Public action ports and exact Recipe 1.0.1 supply remain Framework owner prerequisites.

Tests use the package's own source even when vendor has an optimized classmap pointing to another worktree. Full native quality gate and independent PHPStan level 5 of the renderer/SmartManager pass. No runtime distribution or live app is switched.
