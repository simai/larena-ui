<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeLock;

$lock = FrontendRuntimeLock::bundled();
assert($lock->pairId() === 'ui-1f1c9d42d964-smart-6c5d313aca4d');
assert($lock->bundleId() === 'ui-1f1c9d42d964-smart-6c5d313aca4d-registry-2bbb3905-exact-git-tree-v2');
assert($lock->publicationProfile() === 'exact-git-tree-v2');
$registry = $lock->frameworkRegistry();
assert(($registry['schema_id'] ?? null) === 'simai.framework.contract-registry');
assert(($registry['compatibility_id'] ?? null) === $lock->pairId());
assert(($registry['file_sha256'] ?? null) === '2bbb39055514e9705abded013c6a08a0d75a9deead9f725f6744358ebf3cb541');

$expectation = $lock->publicationExpectation();
assert($expectation['schema'] === 'larena.ui.frontend_runtime_artifact.v1');
assert($expectation['runtime'] === 'simai-framework');
assert($expectation['bundle_id'] === $lock->bundleId());
assert($expectation['publication_profile'] === $lock->publicationProfile());
assert($expectation['sources'] === [
    [
        'commit' => '1f1c9d42d964321ba97c3bdfbea66048946886f7',
        'tree' => 'distr',
        'mount' => 'ui',
        'archive_sha256' => '39d257af1cea9b51a4fb942b4530976dd2f9da3ba3aa07fe86cce8d10e45f158',
        'files' => 5131,
    ],
    [
        'commit' => '6c5d313aca4dbc429765a5bc1176c66b2ef7d654',
        'tree' => 'smart',
        'mount' => 'smart',
        'archive_sha256' => '06f737018c30f2554fccdd0d389a9446564a3f445240587c16524d08633a7d1d',
        'files' => 684,
    ],
    [
        'commit' => '7c8a7659a70658005e6c2110736e2c10ac1bc9c6',
        'tree' => 'contracts/generated',
        'mount' => 'contract',
        'archive_sha256' => '01f9807ba52d2c5190be54be520ab330f92aad17755b2bf36aa24cbba318f3cc',
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
    'smart/smart/buttons/js/buttons.js',
    'smart/smart/inputs/js/inputs.js',
    'smart/smart/inputs/css/inputs.css',
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
assert(!in_array('smart/', $requiredRuntimeFiles, true));

$sources = $lock->publicationSources('/tmp/ui', '/tmp/ui-smart');
assert(count($sources) === 3);
assert($sources[0]['repository'] === '/tmp/ui');
assert($sources[1]['repository'] === '/tmp/ui-smart');
assert($sources[2] === [
    'repository' => '/tmp/ui',
    'commit' => '7c8a7659a70658005e6c2110736e2c10ac1bc9c6',
    'tree' => 'contracts/generated',
    'mount' => 'contract',
    'sha256' => '01f9807ba52d2c5190be54be520ab330f92aad17755b2bf36aa24cbba318f3cc',
]);

foreach ([
    static function (array &$data): void { $data['runtime'] = 'other-framework'; },
    static function (array &$data): void { $data['bundle_id'] = $data['pair_id']; },
    static function (array &$data): void { $data['publication_profile'] = 'unverified-copy'; },
    static function (array &$data): void { $data['schema'] = 'larena.ui.frontend_runtime_lock.v2'; },
    static function (array &$data): void { $data['framework_registry']['compatibility_id'] = 'sf-v5.4.0-local.2-wrong-wrong'; },
    static function (array &$data): void { $data['framework_registry']['relative_path'] = '../registry.json'; },
    static function (array &$data): void { $data['framework_registry']['source']['commit'] = str_repeat('0', 39); },
    static function (array &$data): void { $data['ui']['tree'] = 'smart'; },
    static function (array &$data): void { $data['ui']['tree'] = '../distr'; },
    static function (array &$data): void { $data['ui']['mount'] = 'runtime'; },
    static function (array &$data): void { $data['ui']['mount'] = '../ui'; },
    static function (array &$data): void { $data['ui']['files'] = 0; },
    static function (array &$data): void { $data['ui']['files'] = '2671'; },
    static function (array &$data): void { $data['ui_smart']['tree'] = 'distr'; },
    static function (array &$data): void { $data['ui_smart']['mount'] = 'ui'; },
    static function (array &$data): void { unset($data['ui_smart']); },
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
