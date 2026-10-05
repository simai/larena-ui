<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeLock;

$lock = FrontendRuntimeLock::bundled();
assert($lock->pairId() === 'ui-3e83509fd810-smart-9501b9b58e06');
assert($lock->bundleId() === 'ui-3e83509fd810-smart-9501b9b58e06-registry-51c8170e-exact-git-tree-v2');
assert($lock->publicationProfile() === 'exact-git-tree-v2');
$registry = $lock->frameworkRegistry();
assert(($registry['schema_id'] ?? null) === 'simai.framework.contract-registry');
assert(($registry['compatibility_id'] ?? null) === $lock->pairId());
assert(($registry['file_sha256'] ?? null) === '51c8170e873ce7624b9c3300f4c5abab0bc93d39494609c9919726fc8caec75c');

$expectation = $lock->publicationExpectation();
assert($expectation['schema'] === 'larena.ui.frontend_runtime_artifact.v1');
assert($expectation['runtime'] === 'simai-framework');
assert($expectation['bundle_id'] === $lock->bundleId());
assert($expectation['publication_profile'] === $lock->publicationProfile());
assert($expectation['sources'] === [
    [
        'commit' => '3e83509fd8103f5ae4f84f52087b685337987c7c',
        'tree' => 'distr',
        'mount' => 'ui',
        'archive_sha256' => 'f370f57dfbe90fd9e4825e3cfb3c37328c2fa11dd6d7c34bc35bc9ffe168b634',
        'files' => 6143,
    ],
    [
        'commit' => '9501b9b58e069b5c2c1c85a3204cb49974f1e70e',
        'tree' => 'smart',
        'mount' => 'smart',
        'archive_sha256' => 'fbc33de8de974a68e50b78c407a16b0414fbbe67a7dfb672c35fb6fa702e9799',
        'files' => 987,
    ],
    [
        'commit' => '620d2d4eeb312a5b94c308dd546009491b361ce6',
        'tree' => 'contracts/generated',
        'mount' => 'contract',
        'archive_sha256' => '028f89b909fd511fb708c466fa3d3d96bd2df4d40a7c06283d14fb1cb5a4897a',
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
    'commit' => '620d2d4eeb312a5b94c308dd546009491b361ce6',
    'tree' => 'contracts/generated',
    'mount' => 'contract',
    'sha256' => '028f89b909fd511fb708c466fa3d3d96bd2df4d40a7c06283d14fb1cb5a4897a',
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
