<?php

declare(strict_types=1);

$bridge = (string) file_get_contents(__DIR__ . '/../../resources/js/admin-smart-event-bridge.js');
$runtimeBridge = (string) file_get_contents(__DIR__ . '/../../resources/js/sf-runtime-bridge.js');
$manifest = json_decode(
    (string) file_get_contents(__DIR__ . '/../../resources/smart/ui-admin-menu/manifest.json'),
    true,
    flags: JSON_THROW_ON_ERROR,
);

assert(isset($manifest['events']['sf-admin-menu-compact-change']));
assert(($manifest['events']['sf-admin-menu-compact-change']['backend_handler_binding'] ?? null) === false);
assert(str_contains($bridge, "document.addEventListener('sf-admin-menu-compact-change'"));
assert(str_contains($bridge, 'shell.dataset.larenaNavigationCompact'));
assert(str_contains($bridge, "customElements.whenDefined('sf-admin-menu')"));
assert(!str_contains($bridge, 'localStorage'));
assert(str_contains($bridge, '[data-larena-dataview-workbench]'));
assert(str_contains($bridge, 'runQuery({filters: selectedTemplate.data.values, page: 1})'));
assert(str_contains($bridge, "listen(table, 'onFilterUpdate'"));
assert(str_contains($bridge, "listen(table, 'onTemplateSave'"));
assert(str_contains($bridge, "listen(table, 'onColumnSettingsChange'"));
assert(str_contains($bridge, "listen(pagination, 'sf-page-change'"));
assert(str_contains($bridge, "listen(pagination, 'sf-action-apply'"));
assert(str_contains($bridge, "'sf-data-view-create-request'"));
// Framework row/bulk operations use the immutable typed port, not legacy data-view events.
assert(str_contains($bridge, "listen(table, 'sf-table-action-intent'"));
assert(str_contains($bridge, "Object.defineProperty(globalThis, 'LarenaDataviewActions'"));
assert(str_contains($bridge, 'const actionRegistrations = new WeakMap()'));
assert(str_contains($bridge, 'registration?.handlers.get(detail.action_id)'));
assert(str_contains($bridge, 'table.requestActionIntent(actionId, ids)'));
assert(!str_contains($bridge, "listen(table, 'sf-data-view-row-action'"));
assert(!str_contains($bridge, "listen(table, 'sf-data-view-bulk-action'"));
assert(str_contains($bridge, 'base_revision'));
assert(str_contains($runtimeBridge, "intent !== 'larena.record.delete'"));
assert(str_contains($runtimeBridge, "intent !== 'larena.record.restore'"));
assert(str_contains($runtimeBridge, "var mode = operation === 'restore' ? 'delete' : operation"));
assert(str_contains($runtimeBridge, "url.searchParams.set('record_mode', mode)"));
assert(str_contains($runtimeBridge, 'syncPaginationSelection'));
assert(str_contains($runtimeBridge, 'td[data-key="select"] input[type="checkbox"][value]:checked'));
assert(str_contains($runtimeBridge, 'var observedSelectionInputs = new WeakSet()'));
assert(str_contains($runtimeBridge, "input.addEventListener('change', syncPaginationSelection)"));
assert(substr_count($runtimeBridge, 'observeSelectionInputs();') >= 2);
assert(str_contains($runtimeBridge, "target.addEventListener('sf-table-selection-change'"));

echo "AdminSmartEventBridgeContractTest passed.\n";
