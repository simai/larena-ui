<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeLock;

$lock = FrontendRuntimeLock::bundled();
// The free Framework release and the Larena components package built over it, pinned together.
assert($lock->pairId() === 'ui-45dfc07d5e04-larena-f05fc5b26373');
assert($lock->frameworkId() === 'ui-45dfc07d5e04');
assert($lock->larenaId() === 'larena-f05fc5b26373');
assert($lock->toArray()['larena']['requires_framework'] === $lock->frameworkId());
assert($lock->toArray()['larena']['repository'] === 'simai/larena-ui' && $lock->toArray()['larena']['package_path'] === 'components');
assert($lock->bundleId() === 'ui-45dfc07d5e04-larena-f05fc5b26373-registry-f61d89d7-exact-git-tree-v2');
assert($lock->uiBase() === 'ui/distr/' && $lock->smartBase() === 'ui/distr/smart/' && $lock->larenaBase() === 'larena/smart/');
assert($lock->publicationProfile() === 'exact-git-tree-v2');
$registry = $lock->frameworkRegistry();
assert(($registry['schema_id'] ?? null) === 'simai.framework.contract-registry');
assert(($registry['compatibility_id'] ?? null) === $lock->frameworkId());
assert(($registry['file_sha256'] ?? null) === 'f61d89d76f827e0a4156cbef0eee77e8dec0aeb672c0d64601b220f9cc1248a8');

$expectation = $lock->publicationExpectation();
assert($expectation['schema'] === 'larena.ui.frontend_runtime_artifact.v1');
assert($expectation['runtime'] === 'simai-framework');
assert($expectation['bundle_id'] === $lock->bundleId());
assert($expectation['publication_profile'] === $lock->publicationProfile());
assert($expectation['sources'] === [
    [
        'commit' => '45dfc07d5e0435498efc268b3d98aebcfe86a9ef',
        'tree' => 'distr',
        'mount' => 'ui',
        'archive_sha256' => '512e86d2264559ab51bed0ec2feb81b67e8cdc7c863da29848a8c8ff03078e7e',
        'files' => 8207,
    ],
    [
        'commit' => 'f05fc5b26373d0abe22931fe82b3f24d0f78cb12',
        'tree' => 'smart',
        'mount' => 'larena',
        'archive_sha256' => '47dda8e624e44c58e5bc9818d5dd92ab6dddcc32a49fcebec432bbaa6864e6a1',
        'files' => 62,
    ],
    [
        'commit' => '4b7d1a8c52d601d1fcc455d3f96be1912dca99f6',
        'tree' => 'contracts/generated',
        'mount' => 'contract',
        'archive_sha256' => 'af59f377a039980bc2d84c2ca7f005357062754f60f04432ba6f729cdfdc46b5',
        'files' => 2,
    ],
]);
assert(count(array_unique(array_column($expectation['sources'], 'mount'))) === 3);

$requiredRuntimeFiles = $lock->requiredRuntimeFiles();
assert(count($requiredRuntimeFiles) === count(array_unique($requiredRuntimeFiles)));
foreach ([
    'ui/distr/core/css/core.min.css',
    'ui/distr/core/js/core.min.js',
    'ui/distr/core/js/smart-base.js',
    'contract/contracts/generated/framework-contract-registry.json',
    'ui/distr/smart/buttons/js/buttons.js',
    'ui/distr/smart/inputs/js/inputs.js',
    'ui/distr/smart/inputs/css/inputs.css',
    'larena/smart/table/js/table.js',
    'larena/smart/table/css/table.css',
    'larena/smart/data-view/js/data-view.js',
] as $requiredRuntimeFile) {
    assert(in_array($requiredRuntimeFile, $requiredRuntimeFiles, true));
}
foreach ($lock->toArray()['components'] as $component) {
    assert(in_array($component['javascript'], $requiredRuntimeFiles, true));
    if ($component['css'] !== null) {
        assert(in_array($component['css'], $requiredRuntimeFiles, true));
    }
}
assert(!in_array('ui/distr/', $requiredRuntimeFiles, true));
assert(!in_array('larena/smart/', $requiredRuntimeFiles, true));
// The four Larena tags resolve inside the package's tree, every other one inside the free Smart tree.
foreach ($lock->toArray()['components'] as $tag => $component) {
    $larena = in_array($tag, ['sf-table', 'sf-data-view', 'sf-property-view', 'sf-admin-menu'], true);
    assert($component['package'] === ($larena ? 'larena' : 'framework'));
    assert(str_starts_with($component['javascript'], $larena ? 'larena/smart/' : 'ui/distr/smart/'));
}

$sources = $lock->publicationSources('/tmp/ui', '/tmp/package-larena.tar');
assert(count($sources) === 3);
assert($sources[0]['repository'] === '/tmp/ui');
assert($sources[1]['repository'] === '/tmp/package-larena.tar');
assert($sources[2] === [
    'repository' => '/tmp/ui',
    'commit' => '4b7d1a8c52d601d1fcc455d3f96be1912dca99f6',
    'tree' => 'contracts/generated',
    'mount' => 'contract',
    'sha256' => 'af59f377a039980bc2d84c2ca7f005357062754f60f04432ba6f729cdfdc46b5',
]);

foreach ([
    static function (array &$data): void { $data['runtime'] = 'other-framework'; },
    static function (array &$data): void { $data['bundle_id'] = $data['pair_id']; },
    static function (array &$data): void { $data['publication_profile'] = 'unverified-copy'; },
    static function (array &$data): void { $data['schema'] = 'larena.ui.frontend_runtime_lock.v4'; },
    static function (array &$data): void { $data['framework_registry']['compatibility_id'] = 'sf-v5.4.0-local.2-wrong-wrong'; },
    static function (array &$data): void { $data['framework_registry']['relative_path'] = '../registry.json'; },
    static function (array &$data): void { $data['framework_registry']['source']['commit'] = str_repeat('0', 39); },
    static function (array &$data): void { $data['ui']['tree'] = 'smart'; },
    static function (array &$data): void { $data['ui']['tree'] = '../distr'; },
    static function (array &$data): void { $data['ui']['mount'] = 'runtime'; },
    static function (array &$data): void { $data['ui']['mount'] = '../ui'; },
    static function (array &$data): void { $data['ui']['files'] = 0; },
    static function (array &$data): void { $data['ui']['files'] = '2671'; },
    static function (array &$data): void { $data['larena']['tree'] = 'distr'; },
    static function (array &$data): void { $data['larena']['mount'] = 'ui'; },
    static function (array &$data): void { unset($data['larena']); },
    // A package built over another free release is refused: the two are always pinned together.
    static function (array &$data): void { $data['larena']['requires_framework'] = 'ui-000000000000'; },
    static function (array &$data): void { $data['larena']['release'] = 'larena-000000000000'; },
    static function (array &$data): void { $data['larena']['repository'] = 'simai/ui-pro'; },
    static function (array &$data): void { $data['larena']['package_path'] = 'src'; },
    static function (array &$data): void { $data['larena']['builder']['revision'] = 'main'; },
    static function (array &$data): void { $data['larena']['framework_source']['repository'] = 'simai/ui'; },
    static function (array &$data): void { unset($data['larena']['receipt_sha256']); },
    static function (array &$data): void { $data['components']['sf-table']['package'] = 'framework'; },
    static function (array &$data): void { $data['components']['sf-button']['package'] = 'larena'; },
    static function (array &$data): void { $data['components']['sf-button']['package'] = 'pro'; },
    static function (array &$data): void { $data['boot']['larena_base'] = 'ui/distr/'; },
    static function (array &$data): void { $data['framework_registry']['source']['files'] = 0; },
    static function (array &$data): void { $data['framework_registry']['source']['mount'] = 'smart'; },
    static function (array &$data): void { unset($data['framework_registry']['source']); },
    static function (array &$data): void { $data['framework_registry']['relative_path'] = '/contract/registry.json'; },
    static function (array &$data): void { $data['framework_registry']['relative_path'] = 'ui/registry.json'; },
    static function (array &$data): void { unset($data['boot']['css']); },
    static function (array &$data): void { $data['boot']['css'] = 'ui/../core.css'; },
    static function (array &$data): void { $data['boot']['javascript'] = 'smart/core.js'; },
    static function (array &$data): void { $data['boot']['ui_base'] = 'outside/'; },
    static function (array &$data): void { $data['boot']['smart_base_path'] = '../smart/'; },
    static function (array &$data): void { $data['boot']['smart_base_path'] = 'larena/smart/'; },
    static function (array &$data): void { $data['components'] = []; },
    static function (array &$data): void { unset($data['components']['sf-button']['javascript']); },
    static function (array &$data): void { $data['components']['sf-button']['javascript'] = 'smart/../button.js'; },
    static function (array &$data): void { $data['components']['sf-button']['javascript'] = 'ui/distr/button.js'; },
    static function (array &$data): void { unset($data['components']['sf-button']['css']); },
    static function (array &$data): void { $data['components']['sf-button']['css'] = ''; },
    static function (array &$data): void { $data['components']['sf-button']['css'] = 'contract/button.css'; },
] as $mutate) {
    $failed = false;
    $data = $lock->toArray();
    $mutate($data);
    try {
        FrontendRuntimeLock::fromArray($data);
    } catch (RuntimeException) {
        $failed = true;
    }
    assert($failed);
}

echo "FrontendRuntimeLockTest passed.\n";
