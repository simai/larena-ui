<?php

declare(strict_types=1);

// The admin's alert bar above the header (owner decision 2026-11-05; Framework sf-alert-bar 1.0.0): its items go in the
// component's settings island, cleaned to the contract: an unknown field is dropped, a link off the site is left out,
// an unknown level is refused.

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

$bar = $manager->render('ui.alert_bar', [
    'items' => [
        ['id' => 'env:backup', 'level' => 'warning', 'text' => 'Backup failed <b>', 'time' => '2026-11-05T03:30:00Z',
            'action' => ['label' => 'Open', 'href' => '/admin/server'], 'acknowledge' => ['label' => 'Seen'], 'extra' => 'dropped'],
        ['id' => 'x', 'level' => 'info', 'text' => 'Off-site link', 'action' => ['label' => 'Go', 'href' => 'https://example.com']],
    ],
    'label' => 'Important notices', 'id' => 'admin-alerts',
], $activation);
$html = $bar->html();
assert($bar->isRenderable());
assert(str_contains($html, '<sf-alert-bar') && str_contains($html, '<script type="application/json" data-sf-settings>'));
assert(str_contains($html, '"href":"/admin/server"') && !str_contains($html, 'example.com') && !str_contains($html, 'dropped'));
assert(str_contains($html, 'Backup failed \u003Cb\u003E') && !str_contains($html, 'failed <b>'), 'the text cannot close the island');
$refused = false;
try {
    $refused = $manager->render('ui.alert_bar', ['items' => [['id' => 'a', 'level' => 'loud', 'text' => 'x']], 'id' => 'admin-alerts'], $activation)->html() === '';
} catch (InvalidArgumentException) {
    $refused = true;
}
assert($refused, 'an unknown level is refused');

echo "AlertBarSmartTest passed.\n";
