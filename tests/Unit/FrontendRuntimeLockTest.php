<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeLock;

$lock = FrontendRuntimeLock::bundled();
assert($lock->pairId() === 'ui-13fb3b08c318-smart-e4396290ec1f');
assert($lock->bundleId() === 'ui-13fb3b08c318-smart-e4396290ec1f-registry-b7695882-exact-git-tree-v2');
assert($lock->publicationProfile() === 'exact-git-tree-v2');
$registry = $lock->frameworkRegistry();
assert(($registry['schema_id'] ?? null) === 'simai.framework.contract-registry');
assert(($registry['compatibility_id'] ?? null) === $lock->pairId());
assert(($registry['file_sha256'] ?? null) === 'b7695882f642d2aec2489e827fe4cdf6561e2893db87e31d6b9b4850963419f8');

$expectation = $lock->publicationExpectation();
assert($expectation['schema'] === 'larena.ui.frontend_runtime_artifact.v1');
assert($expectation['runtime'] === 'simai-framework');
assert($expectation['bundle_id'] === $lock->bundleId());
assert($expectation['publication_profile'] === $lock->publicationProfile());
assert($expectation['sources'] === [
    [
        'commit' => '13fb3b08c31837e58d3e83ad583f3428444e9b07',
        'tree' => 'distr',
        'mount' => 'ui',
        'archive_sha256' => 'c360db3cb01706043874f174edfc329d9c8c0af4becd3ab0ea83b671ff727d10',
        'files' => 6143,
    ],
    [
        'commit' => 'e4396290ec1f16e2f1ae595177f396f3d50a2630',
        'tree' => 'smart',
        'mount' => 'smart',
        'archive_sha256' => 'c7d15b6540fecb80e0c3024a0d96fe6eb17f4280939a21616b1d60a8d1fc2fd2',
        'files' => 987,
    ],
    [
        'commit' => '954066528412c20bbf122696b7160dcacc13dd62',
        'tree' => 'contracts/generated',
        'mount' => 'contract',
        'archive_sha256' => '996cd3ffcd9585f8a46102dbeeca737d346637d310a386ef9dc206347dd12e70',
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
    'commit' => '954066528412c20bbf122696b7160dcacc13dd62',
    'tree' => 'contracts/generated',
    'mount' => 'contract',
    'sha256' => '996cd3ffcd9585f8a46102dbeeca737d346637d310a386ef9dc206347dd12e70',
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
