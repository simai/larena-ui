<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Larena\Ui\Frontend\SourceBackedAssetProvenanceLock;

$packageRoot = dirname(__DIR__, 2);
$lock = SourceBackedAssetProvenanceLock::bundled();
$data = $lock->toArray();

assert($data['schema'] === 'larena.ui.source_backed_asset_provenance_lock.v1');
assert($data['runtime'] === 'simai-framework');
assert(array_keys($data['sources']) === ['ui', 'ui_smart', 'ui_play']);
assert(count($lock->assets()) === 14);
assert(count($lock->assets('button_proof')) === 4);
assert(count($lock->assets('catalog')) === 10);
assert(count($lock->assets('catalog', 'buttons')) === 4);
assert(count($lock->assets('catalog', 'tags')) === 3);
assert(count($lock->assets('catalog', 'pagination')) === 3);
assert(count($lock->examples()) === 9);

$verification = $lock->verifyBundledAssets($packageRoot);
assert($verification['status'] === 'passed');
assert($verification['provenance_lock'] === SourceBackedAssetProvenanceLock::RELATIVE_PATH);
assert($verification['asset_count'] === 14);
assert($verification['matched_count'] === 14);
foreach ($verification['checks'] as $check) {
    assert($check['exists'] === true);
    assert($check['matches_provenance'] === true);
    assert(preg_match('/^[a-f0-9]{64}$/', (string) $check['expected_sha256']) === 1);
    assert($check['expected_sha256'] === $check['actual_sha256']);
    assert(str_contains((string) $check['source_ref'], '@'));
}

$unavailable = $lock->auditExternalSources([]);
assert($unavailable['status'] === 'source_audit_unavailable');
assert($unavailable['requested'] === false);
assert($unavailable['reason'] === 'not_requested');

$partial = $lock->auditExternalSources(['ui' => '/definitely/missing']);
assert($partial['status'] === 'source_audit_unavailable');
assert($partial['requested'] === true);
assert($partial['reason'] === 'one_or_more_roots_unavailable');

$tmp = sys_get_temp_dir() . '/larena-ui-provenance-' . bin2hex(random_bytes(8));
$roots = [
    'ui' => $tmp . '/ui',
    'ui_smart' => $tmp . '/ui-smart',
    'ui_play' => $tmp . '/ui-play',
];

try {
    foreach ($roots as $root) {
        assert(mkdir($root, 0700, true) || is_dir($root));
    }

    foreach ($data['assets'] as $key => $asset) {
        $sourcePath = $roots[$asset['source']] . '/' . $asset['source_path'];
        assert(is_dir(dirname($sourcePath)) || mkdir(dirname($sourcePath), 0700, true));
        $packagePath = $packageRoot . '/' . $asset['package_resource_path'];
        assert(copy($packagePath, $sourcePath));
        assert(hash_file('sha256', $sourcePath) === $asset['sha256']);
    }

    foreach ($data['examples'] as $key => &$example) {
        $sourcePath = $roots[$example['source']] . '/' . $example['source_path'];
        assert(is_dir(dirname($sourcePath)) || mkdir(dirname($sourcePath), 0700, true));
        $content = 'portable-example-fixture:' . $example['source_path'];
        file_put_contents($sourcePath, $content);
        $example['sha256'] = hash('sha256', $content);
    }
    unset($example);

    $fixtureLock = SourceBackedAssetProvenanceLock::fromArray($data);
    $audit = $fixtureLock->auditExternalSources($roots);
    assert($audit['status'] === 'passed');
    assert($audit['requested'] === true);
    assert($audit['reason'] === 'all_locked_sources_match');
    assert(count($audit['asset_checks']) === 14);
    assert(count($audit['example_checks']) === 9);

    $driftPath = $roots['ui'] . '/' . $data['assets']['catalog.buttons.component_css']['source_path'];
    file_put_contents($driftPath, 'drift', FILE_APPEND);
    $driftAudit = $fixtureLock->auditExternalSources($roots);
    assert($driftAudit['status'] === 'failed');
    assert($driftAudit['reason'] === 'source_drift_detected');
} finally {
    if (is_dir($tmp)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($tmp);
    }
}

foreach ([
    static function (array &$value): void { $value['runtime'] = 'other'; },
    static function (array &$value): void { $value['contract']['external_source_audit'] = 'required'; },
    static function (array &$value): void { unset($value['sources']['ui_play']); },
    static function (array &$value): void { $value['sources']['ui']['revision'] = 'short'; },
    static function (array &$value): void { $value['assets']['catalog.buttons.component_css']['source_path'] = '../buttons.css'; },
    static function (array &$value): void { $value['assets']['catalog.buttons.component_css']['sha256'] = 'invalid'; },
    static function (array &$value): void { $value['assets']['catalog.buttons.component_css']['package_resource_path'] = '/absolute.css'; },
    static function (array &$value): void { $value['examples']['catalog.buttons.component_all']['source'] = 'ui'; },
] as $mutate) {
    $invalid = $lock->toArray();
    $mutate($invalid);
    $failed = false;
    try {
        SourceBackedAssetProvenanceLock::fromArray($invalid);
    } catch (RuntimeException) {
        $failed = true;
    }
    assert($failed);
}

echo "SourceBackedAssetProvenanceLockTest passed.\n";
