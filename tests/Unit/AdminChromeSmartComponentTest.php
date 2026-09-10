<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Runtime\SmartManager;

$activation = [
    'schema' => 'larena.core_assets.activation_contract.v1',
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

$menu = $manager->render('ui.admin_menu', [
    'brand' => 'Larena', 'logo-href' => '/admin', 'aria-label' => 'Admin navigation',
    'searchable' => true, 'collapsible' => true, 'settings' => false, 'compact' => false,
    'persistence-mode' => 'external', 'settings-mode' => 'user',
    'settings-revision' => 0, 'system-settings-revision' => 0,
    'settings-state-endpoint' => '/admin/navigation/state',
    'settings-endpoint' => '/admin/navigation/preferences/me',
    'settings-reset-endpoint' => '/admin/navigation/preferences/me',
    'system-settings-endpoint' => '/admin/navigation/defaults',
    'system-settings-reset-endpoint' => '/admin/navigation/defaults',
    'allow-system-settings' => false,
    'search-placeholder' => 'Search sections', 'toggle-label' => 'Menu',
    'items' => [
        ['item-id' => 'larena.cms.content', 'label' => 'Content', 'href' => '/admin/cms', 'left-icon' => 'database', 'active' => true, 'order' => 10],
        ['item-id' => 'larena.admin.settings', 'label' => 'Settings', 'href' => '/admin/settings', 'left-icon' => 'settings', 'active' => false, 'order' => 20],
    ],
    'id' => 'admin-menu',
], $activation);
assert($menu->isRenderable());
assert(str_contains($menu->html(), '<sf-admin-menu'));
assert(str_contains($menu->html(), 'settings="false"'));
assert(str_contains($menu->html(), '<sf-admin-menu-item'));
assert(str_contains($menu->html(), 'href="/admin/cms"'));
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
    ['ui.icon_button', ['size' => '1', 'variant' => 'icon', 'type' => 'link', 'scheme' => 'on-surface', 'icon' => 'help', 'loading' => false, 'disabled' => false, 'native-type' => 'button', 'aria-label' => 'Help']],
    ['ui.avatar', ['size' => '1', 'text' => 'AZ', 'status' => 'none', 'aria-label' => 'Admin', 'disabled' => false, 'inactive' => false]],
    ['ui.tag', ['type' => 'primary', 'size' => '1/2', 'text' => '12', 'active' => false, 'disabled' => false, 'closable' => false, 'aria-label' => 'Records: 12']],
    ['ui.toggle', ['size' => '2', 'type' => 'icon', 'label' => 'Theme', 'icon' => 'light_mode', 'checked' => false, 'disabled' => false, 'name' => 'theme', 'value' => 'dark', 'aria-label' => 'Theme']],
] as [$key, $props]) {
    assert($manager->render($key, $props, $activation)->isRenderable());
}

$unsafeRejected = false;
try {
    $manager->render('ui.admin_menu', [
        'brand' => 'Larena', 'logo-href' => '/admin', 'aria-label' => 'Admin navigation',
        'searchable' => true, 'collapsible' => true, 'settings' => false, 'compact' => false,
        'search-placeholder' => 'Search', 'toggle-label' => 'Menu',
        'items' => [['item-id' => 'larena.test.unsafe', 'label' => 'Unsafe', 'href' => 'javascript:alert(1)']],
    ], $activation);
} catch (InvalidArgumentException $exception) {
    $unsafeRejected = $exception->getMessage() === 'ui_smart_admin_menu_item_href_invalid';
}
assert($unsafeRejected);

echo "AdminChromeSmartComponentTest passed.\n";
