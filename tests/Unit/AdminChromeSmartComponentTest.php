<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Runtime\SmartManager;

$activation = [
    'schema' => 'larena.core_assets.activation_contract',
    'status' => 'ready',
    'activation_owner' => 'larena/core:core.assets',
    'activation_mode' => 'verified_immutable_bundle',
    'physical_publication_ready' => true,
    'writes_database' => false,
    'copies_to_root' => false,
    'uses_hardcoded_cdn' => false,
    'runtime_pair' => FrontendRuntimeLock::bundled()->pairId(),
    'renderable_tags' => ['<script src="/larena/assets/sf/core.js"></script>'],
];
$manager = SmartManager::withDefaults();

// The menu reads the navigation port only: no printed items, no settings of the former items menu.
$menu = $manager->render('ui.admin_menu', [
    'aria-label' => 'Admin navigation', 'expected-columns' => 2, 'id' => 'admin-menu',
], $activation);
assert($menu->isRenderable());
assert(str_contains($menu->html(), '<sf-admin-menu'));
assert(str_contains($menu->html(), 'expected-columns="2"'));
assert(!str_contains($menu->html(), '<sf-admin-menu-item'));
assert(!str_contains($menu->html(), '<script'));

$breadcrumbs = $manager->render('ui.breadcrumbs', [
    'items' => [['label' => 'Larena', 'href' => '/admin', 'icon' => 'home'], ['label' => 'Content', 'current' => true]],
    'separator-icon' => 'chevron_right', 'home-icon' => 'home', 'aria-label' => 'Breadcrumbs',
    'expand-on-ellipsis' => true, 'max-items' => 4, 'id' => 'breadcrumbs',
], $activation);
assert($breadcrumbs->isRenderable());
assert(str_contains($breadcrumbs->html(), '<sf-breadcrumbs'));
assert(str_contains($breadcrumbs->html(), '&quot;label&quot;:&quot;Content&quot;'));

foreach ([
    ['ui.icon_button', ['size' => '1', 'kind' => 'icon', 'appearance' => 'link', 'scheme' => 'on-surface', 'icon' => 'help', 'loading' => false, 'disabled' => false, 'native-type' => 'button', 'aria-label' => 'Help']],
    ['ui.avatar', ['size' => '1', 'text' => 'AZ', 'status' => 'none', 'aria-label' => 'Admin', 'disabled' => false, 'inactive' => false]],
    ['ui.tag', ['type' => 'primary', 'size' => '1/2', 'text' => '12', 'active' => false, 'disabled' => false, 'closable' => false, 'aria-label' => 'Records: 12']],
] as [$key, $props]) {
    assert($manager->render($key, $props, $activation)->isRenderable());
}

// The former items menu's settings are refused.
$itemsRejected = false;
try {
    $manager->render('ui.admin_menu', [
        'aria-label' => 'Admin navigation',
        'items' => [['item-id' => 'larena.test.item', 'label' => 'Item', 'href' => '/admin']],
    ], $activation);
} catch (InvalidArgumentException) {
    $itemsRejected = true;
}
assert($itemsRejected);

echo "AdminChromeSmartComponentTest passed.\n";
