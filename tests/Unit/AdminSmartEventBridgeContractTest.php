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
assert(str_contains($bridge, "table.addEventListener('onFilterUpdate'"));
assert(str_contains($bridge, "table.addEventListener('onTemplateSave'"));
assert(str_contains($bridge, "table.addEventListener('onColumnSettingsChange'"));
assert(str_contains($bridge, "pagination.addEventListener('sf-page-change'"));
assert(str_contains($bridge, "pagination.addEventListener('sf-action-apply'"));
assert(str_contains($bridge, "'sf-data-view-create-request'"));
assert(str_contains($bridge, "'sf-data-view-row-action'"));
assert(str_contains($bridge, "'sf-data-view-bulk-action'"));
assert(str_contains($bridge, 'base_revision'));
assert(str_contains($runtimeBridge, "intent !== 'larena.record.delete'"));
assert(str_contains($runtimeBridge, "intent !== 'larena.record.restore'"));
assert(str_contains($runtimeBridge, "var mode = operation === 'restore' ? 'delete' : operation"));
assert(str_contains($runtimeBridge, "url.searchParams.set('record_mode', mode)"));

echo "AdminSmartEventBridgeContractTest passed.\n";
