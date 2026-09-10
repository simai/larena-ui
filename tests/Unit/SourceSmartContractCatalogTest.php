<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Developer\SourceSmartContractCatalog;
use Larena\Ui\Registry\SmartRegistry;

$catalog = new SourceSmartContractCatalog();
$menu = $catalog->contract('smart.admin-menu');
assert($menu['version'] === '1.0.0');
assert($menu['reuse_mode'] === 'whole');
assert($menu['custom_element'] === 'sf-admin-menu');
assert($menu['compatibility']['contract_schema']['version'] === 1);
assert(preg_match('/^[a-f0-9]{64}$/', $menu['manifest_sha256']) === 1);

$dataview = $catalog->contract('smart.data-view');
assert($dataview['custom_element'] === 'sf-table');
assert(isset($dataview['events']['sf-data-view-column-settings-save']));

$registry = SmartRegistry::withDefaults();
$adapter = json_decode(
    (string) file_get_contents(dirname(__DIR__, 2) . '/resources/adapters/ui.dataview.json'),
    true,
    512,
    JSON_THROW_ON_ERROR,
);
$validated = $catalog->validateAdapter('ui.dataview', $adapter, $registry->manifest('ui.dataview'));
assert($validated['id'] === 'smart.data-view');

$adapter['allowed_events']['sf-invented-browser-event'] = 'must fail closed';
$failedClosed = false;
try {
    $catalog->validateAdapter('ui.dataview', $adapter, $registry->manifest('ui.dataview'));
} catch (\InvalidArgumentException $exception) {
    $failedClosed = str_contains($exception->getMessage(), 'ui_source_contract_event_mismatch');
}
assert($failedClosed === true);

echo "SourceSmartContractCatalogTest passed.\n";
