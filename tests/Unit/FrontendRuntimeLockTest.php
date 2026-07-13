<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeLock;

$lock = FrontendRuntimeLock::bundled();
assert($lock->pairId() === 'sf-v5.3.2-7e836d8a-dd786bba');
assert($lock->bundleId() === 'sf-v5.3.2-7e836d8a-dd786bba-registry-2c596327-exact-git-tree-v2');
assert($lock->publicationProfile() === 'exact-git-tree-v2');
$registry = $lock->frameworkRegistry();
assert(($registry['schema_id'] ?? null) === 'simai.framework.contract-registry');
assert(($registry['compatibility_id'] ?? null) === $lock->pairId());
assert(($registry['file_sha256'] ?? null) === '2c5963276d31af09770fe41cad04826c04b634f7b2d798d9b0e32864517346b7');

$sources = $lock->publicationSources('/tmp/ui', '/tmp/ui-smart');
assert(count($sources) === 3);
assert($sources[0]['repository'] === '/tmp/ui');
assert($sources[1]['repository'] === '/tmp/ui-smart');
assert($sources[2] === [
    'repository' => '/tmp/ui',
    'commit' => 'b7e8a2e810c0d49e31cb749a7ab34c373dd48bc6',
    'tree' => 'contracts/generated',
    'mount' => 'contract',
    'sha256' => '0f915061c3664571f8ce522793ed7192889b8eb19e4c62490440926449a13f9b',
]);

foreach ([
    static function (array &$data): void { $data['bundle_id'] = $data['pair_id']; },
    static function (array &$data): void { $data['publication_profile'] = 'unverified-copy'; },
    static function (array &$data): void { $data['schema'] = 'larena.ui.frontend_runtime_lock.v2'; },
    static function (array &$data): void { $data['framework_registry']['compatibility_id'] = 'sf-v5.3.2-wrong-wrong'; },
    static function (array &$data): void { $data['framework_registry']['relative_path'] = '../registry.json'; },
    static function (array &$data): void { $data['framework_registry']['source']['commit'] = str_repeat('0', 39); },
    static function (array &$data): void { $data['ui']['tree'] = 'smart'; },
    static function (array &$data): void { $data['ui']['mount'] = 'runtime'; },
    static function (array &$data): void { $data['ui']['files'] = 0; },
    static function (array &$data): void { $data['ui_smart']['tree'] = 'distr'; },
    static function (array &$data): void { $data['framework_registry']['source']['files'] = 2; },
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
