<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Developer\InstalledSmartAdapterCatalog;
use Larena\Ui\Registry\SmartRegistry;

$catalog = new InstalledSmartAdapterCatalog(SmartRegistry::withDefaults());
$components = $catalog->components();
$menu = array_values(array_filter($components, static fn (array $item): bool => $item['id'] === 'ui.admin_menu'))[0];
assert($menu['frontend']['tag'] === 'sf-admin-menu');
assert($menu['adapter_available'] === true);
assert($menu['source_contract']['id'] === 'smart.admin-menu');
assert($menu['source_contract']['version'] === '1.0.0');
$dataview = array_values(array_filter($components, static fn (array $item): bool => $item['id'] === 'ui.dataview'))[0];
assert($dataview['frontend']['tag'] === 'sf-table');
assert($dataview['adapter_available'] === true);

$packet = $catalog->describe('ui.admin_menu', true);
assert($packet['schema'] === 'larena.ui.smart_adapter_technology_packet.v1');
assert($packet['decision']['reuse_mode'] === 'whole');
assert($packet['identity']['source_contract']['id'] === 'smart.admin-menu');
assert($packet['public_abi']['compatibility']['contract_schema']['version'] === 1);
assert(isset($packet['public_abi']['events']['sf-admin-menu-settings-save']));
assert($packet['host_adapter']['transport']['component_http_allowed'] === false);
assert(str_contains($packet['host_adapter']['examples']['php'], "Smart::render('ui.admin_menu'"));
assert(!str_contains(json_encode($packet, JSON_THROW_ON_ERROR), '/Users/'));

$dataviewPacket = $catalog->describe('ui.dataview', true);
assert($dataviewPacket['identity']['source_contract']['id'] === 'smart.data-view');
assert($dataviewPacket['decision']['reuse_mode'] === 'whole');
assert(str_contains($dataviewPacket['decision']['required_action'], 'larena/dataview'));
assert($dataviewPacket['host_adapter']['transport']['component_http_allowed'] === false);
assert(str_contains($dataviewPacket['host_adapter']['examples']['php'], "Smart::renderView('dataview.table'"));

echo "InstalledSmartAdapterCatalogTest passed.\n";
