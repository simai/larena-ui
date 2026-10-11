<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeLock;

$lock = FrontendRuntimeLock::bundled();
// The free Framework release and the Pro release built against it, pinned together.
assert($lock->pairId() === 'ui-09bc6b371eca-pro-1f894a46f488');
assert($lock->frameworkId() === 'ui-09bc6b371eca');
assert($lock->proId() === 'pro-1f894a46f488');
assert($lock->toArray()['pro']['requires_framework'] === $lock->frameworkId());
assert($lock->bundleId() === 'ui-09bc6b371eca-pro-1f894a46f488-registry-58c57cca-exact-git-tree-v2');
assert($lock->uiBase() === 'ui/distr/' && $lock->smartBase() === 'ui/distr/smart/' && $lock->proBase() === 'pro/smart/');
assert($lock->publicationProfile() === 'exact-git-tree-v2');
$registry = $lock->frameworkRegistry();
assert(($registry['schema_id'] ?? null) === 'simai.framework.contract-registry');
assert(($registry['compatibility_id'] ?? null) === $lock->frameworkId());
assert(($registry['file_sha256'] ?? null) === '58c57cca12c41f36e80a6e3a8a9589f6f5ec70ffb429b438ed57886b8b6c18e3');

$expectation = $lock->publicationExpectation();
assert($expectation['schema'] === 'larena.ui.frontend_runtime_artifact.v1');
assert($expectation['runtime'] === 'simai-framework');
assert($expectation['bundle_id'] === $lock->bundleId());
assert($expectation['publication_profile'] === $lock->publicationProfile());
assert($expectation['sources'] === [
    [
        'commit' => '09bc6b371eca0ea27723d4a6ace756526c34851d',
        'tree' => 'distr',
        'mount' => 'ui',
        'archive_sha256' => '04ff2d1815b7ef769238df0ae14a58d814226a7d79e58f4564192ae8fc34ae5f',
        'files' => 7722,
    ],
    [
        'commit' => '1f894a46f48887b8f2874b4567ada7e6c36f473d',
        'tree' => 'smart',
        'mount' => 'pro',
        'archive_sha256' => '8b6a78c0465d9dabd962d33b5d861d5d87c21a6a85878856d895be132f97832b',
        'files' => 62,
    ],
    [
        'commit' => 'ff24012d2a32712602d0f0fca11579acd77dfa8a',
        'tree' => 'contracts/generated',
        'mount' => 'contract',
        'archive_sha256' => '094013017891139a019a668492cf37d0929265d829268c63fb7505570a216f47',
        'files' => 2,
    ],
]);
assert(count(array_unique(array_column($expectation['sources'], 'mount'))) === 3);

$requiredRuntimeFiles = $lock->requiredRuntimeFiles();
assert(count($requiredRuntimeFiles) === count(array_unique($requiredRuntimeFiles)));
foreach ([
    'ui/distr/core/css/core.css',
    'ui/distr/core/js/core.js',
    'ui/distr/core/js/smart-base.js',
    'contract/contracts/generated/framework-contract-registry.json',
    'ui/distr/smart/buttons/js/buttons.js',
    'ui/distr/smart/inputs/js/inputs.js',
    'ui/distr/smart/inputs/css/inputs.css',
    'pro/smart/table/js/table.js',
    'pro/smart/table/css/table.css',
    'pro/smart/data-view/js/data-view.js',
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
assert(!in_array('pro/smart/', $requiredRuntimeFiles, true));
// The four Pro tags resolve inside the Pro tree, every other one inside the free Smart tree.
foreach ($lock->toArray()['components'] as $tag => $component) {
    $pro = in_array($tag, ['sf-table', 'sf-data-view', 'sf-property-view', 'sf-admin-menu'], true);
    assert($component['package'] === ($pro ? 'pro' : 'framework'));
    assert(str_starts_with($component['javascript'], $pro ? 'pro/smart/' : 'ui/distr/smart/'));
}

$sources = $lock->publicationSources('/tmp/ui', '/tmp/ui-pro');
assert(count($sources) === 3);
assert($sources[0]['repository'] === '/tmp/ui');
assert($sources[1]['repository'] === '/tmp/ui-pro');
assert($sources[2] === [
    'repository' => '/tmp/ui',
    'commit' => 'ff24012d2a32712602d0f0fca11579acd77dfa8a',
    'tree' => 'contracts/generated',
    'mount' => 'contract',
    'sha256' => '094013017891139a019a668492cf37d0929265d829268c63fb7505570a216f47',
]);

foreach ([
    static function (array &$data): void { $data['runtime'] = 'other-framework'; },
    static function (array &$data): void { $data['bundle_id'] = $data['pair_id']; },
    static function (array &$data): void { $data['publication_profile'] = 'unverified-copy'; },
    static function (array &$data): void { $data['schema'] = 'larena.ui.frontend_runtime_lock.v3'; },
    static function (array &$data): void { $data['framework_registry']['compatibility_id'] = 'sf-v5.4.0-local.2-wrong-wrong'; },
    static function (array &$data): void { $data['framework_registry']['relative_path'] = '../registry.json'; },
    static function (array &$data): void { $data['framework_registry']['source']['commit'] = str_repeat('0', 39); },
    static function (array &$data): void { $data['ui']['tree'] = 'smart'; },
    static function (array &$data): void { $data['ui']['tree'] = '../distr'; },
    static function (array &$data): void { $data['ui']['mount'] = 'runtime'; },
    static function (array &$data): void { $data['ui']['mount'] = '../ui'; },
    static function (array &$data): void { $data['ui']['files'] = 0; },
    static function (array &$data): void { $data['ui']['files'] = '2671'; },
    static function (array &$data): void { $data['pro']['tree'] = 'distr'; },
    static function (array &$data): void { $data['pro']['mount'] = 'ui'; },
    static function (array &$data): void { unset($data['pro']); },
    // A Pro release built against another free release is refused: the two are always pinned together.
    static function (array &$data): void { $data['pro']['requires_framework'] = 'ui-000000000000'; },
    static function (array &$data): void { $data['pro']['release'] = 'pro-000000000000'; },
    static function (array &$data): void { $data['pro']['repository'] = 'simai/ui-smart'; },
    static function (array &$data): void { $data['components']['sf-table']['package'] = 'framework'; },
    static function (array &$data): void { $data['components']['sf-button']['package'] = 'pro'; },
    static function (array &$data): void { $data['boot']['pro_base'] = 'ui/distr/'; },
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
    static function (array &$data): void { $data['boot']['smart_base_path'] = 'pro/smart/'; },
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
