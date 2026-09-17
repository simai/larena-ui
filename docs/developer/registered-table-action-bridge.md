# Registered table action bridge — isolated candidate

Status: implemented and partially accepted in the 2026-09-17 acceptance copies. No live adoption or business-operation acceptance.

Framework owns `sf-table-action-intent` with closed `{action_id, record_ids}`. Larena uses `LarenaDataviewActions.register(table, new Map([...]))` from trusted product JavaScript. Register exact DOM table instances and allowlisted handler closures after the component is defined. Never place handlers, arbitrary URLs, actor IDs, permissions or executable strings in Recipe/Document JSON. The returned function unregisters and aborts outstanding actions. Removal or replacement of a workbench child disposes listeners and registration; reconnecting requires fresh owner registration.

Handlers receive a frozen envelope `{action_id, record_ids, signal, table}`. Record IDs are preserved as opaque strings or safe integers, including zero. Registered owner code must repeat backend ACL and revision checks through the established owner operation; browser registration and hidden controls grant no permission. Observe `signal`, and do not claim cancellation undoes an already committed server operation.

Dispatch enters `pending`. Handler rejection reports a generic error without exposing private exception content. Undefined or unknown result reports unavailable. `state: cancelled` reports cancellation. `state: completed` is reserved for an owner result that actually proves operation completion; returning it merely after dispatch or HTTP acceptance is prohibited. The current browser fixture always returns cancelled and proves routing only.

Only one action runs per mounted instance. Unregistering aborts the active operation immediately; late completions cannot overwrite a replacement operation. Nested and sibling table events are rejected by their composed origin.

Pagination uses its owned `sf-action-apply` event, registered `detail.action`, and the table's own `getSelectedRecordIds()`/`requestActionIntent()` port. It ignores supplied record IDs and endpoints. `actionForAll: true` fails closed: the current Framework port only selects current-result rows, so all-result operations require a separately accepted owner capability.

Create requests have no existing record ID and remain outside this table action port. Their dispatch is pending, never evidence of business success.

Rollback: restore the previous package source and asset manifest together; do not mix the bridge bytes and manifest SHA. This candidate revision and resource digest are captured in Root `source/workflow/evidence/framework-pair-acceptance/consumer-receipt.json`; no main/pins/DB/site changes were performed.

## Explicit nested host lifecycle

Trusted Composite owner code may call `LarenaDataviewActions.registerHost(host, table, handlers)` after the exact instance is connected and its pagination, JSON state and status exist. Returns an unregister closure. Owner discovery selects only nodes whose closest workbench is this host; nested controls cannot replace parent controls. Explicit hosts are observed in their public root, removed/disconnected instances release handlers and abort operations, and remount requires fresh registration. No event automatically grants registration. Existing exact composedPath origin checks remain.
