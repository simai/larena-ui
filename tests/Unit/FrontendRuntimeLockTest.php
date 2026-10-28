<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Larena\Core\Assets\VerifiedAssetBundlePublisher;
use Larena\Ui\Frontend\FrontendRuntimeLock;

$lock = FrontendRuntimeLock::bundled();
// The free Framework release and the Larena components package built over it, pinned together.
assert($lock->pairId() === 'ui-194f75ac620f-larena-db37a4d1cf18');
assert($lock->frameworkId() === 'ui-194f75ac620f');
assert($lock->larenaId() === 'larena-db37a4d1cf18');
assert($lock->packageIds() === ['larena' => 'larena-db37a4d1cf18']);
assert($lock->package('larena')['requires_framework'] === $lock->frameworkId());
// The admin kit is built from simai/ui-pro and released on simai/larena-ui; inside Larena it is bundled.
assert($lock->package('larena')['repository'] === 'simai/ui-pro' && $lock->package('larena')['package_path'] === 'components');
assert($lock->packageReleaseRepository('larena') === 'simai/larena-ui');
assert($lock->packageLicence('larena') === ['ref' => 'PolyForm-Small-Business-1.0.0 OR PolyForm-Noncommercial-1.0.0 OR LicenseRef-SIMAI-Commercial', 'tier' => 'bundled-larena']);
assert($lock->bundleId() === 'ui-194f75ac620f-larena-db37a4d1cf18-registry-d477077d-exact-git-tree-v2');
assert($lock->uiBase() === 'ui/distr/' && $lock->smartBase() === 'ui/distr/smart/' && $lock->larenaBase() === 'larena/smart/');
assert($lock->packageBases() === ['larena' => 'larena/smart/']);
assert($lock->bootPackagesScript('/b/') === '{larena:{base:"\/b\/larena\/smart\/"}}');
assert($lock->publicationProfile() === 'exact-git-tree-v2');
$registry = $lock->frameworkRegistry();
assert(($registry['schema_id'] ?? null) === 'simai.framework.contract-registry');
assert(($registry['compatibility_id'] ?? null) === $lock->frameworkId());
assert(($registry['file_sha256'] ?? null) === 'd477077d32d16ac715898e00f0a1de460d6f8fbaaebf532a681efcdf9c321de6');

// The lock file is written in the current shape: packages, no top-level larena slot, no boot.larena_base.
$file = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/resources/sf/runtime-lock.json'), true, 512, JSON_THROW_ON_ERROR);
assert($file['schema'] === 'larena.ui.frontend_runtime_lock' && $file['schema_version'] === FrontendRuntimeLock::SCHEMA_VERSION);
assert(array_keys($file['packages']) === ['larena'] && !array_key_exists('larena', $file) && !array_key_exists('larena_base', $file['boot']));
assert($file === $lock->toArray());

// The single-package bundle id is byte-identical to the one every bundle had before packages were a list.
assert(FrontendRuntimeLock::composeBundleId(
    FrontendRuntimeLock::composePairId('194f75ac620fe7af4cf053360c3e4db4dff8ba57', ['larena' => 'db37a4d1cf188c5700b1a17b1d3d10ea785001ad']),
    'd477077d32d16ac715898e00f0a1de460d6f8fbaaebf532a681efcdf9c321de6',
    'exact-git-tree-v2',
) === 'ui-194f75ac620f-larena-db37a4d1cf18-registry-d477077d-exact-git-tree-v2');

$expectation = $lock->publicationExpectation();
assert($expectation['schema'] === 'larena.ui.frontend_runtime_artifact');
assert($expectation['runtime'] === 'simai-framework');
assert($expectation['bundle_id'] === $lock->bundleId());
assert($expectation['publication_profile'] === $lock->publicationProfile());
assert($expectation['sources'] === [
    [
        'commit' => '194f75ac620fe7af4cf053360c3e4db4dff8ba57',
        'tree' => 'distr',
        'mount' => 'ui',
        'archive_sha256' => 'c3032c22e275974ee499c2641762316dfa88c06ecd83a173a7758b1b6cd65874',
        'files' => 10734,
    ],
    [
        'commit' => 'db37a4d1cf188c5700b1a17b1d3d10ea785001ad',
        'tree' => 'smart',
        'mount' => 'larena',
        'archive_sha256' => '3095a02786f33aa9aa77c0567a8553a1b07a4223b3fbe490c248c0d7a9427545',
        'files' => 302,
    ],
    [
        'commit' => '274d39770d38b51d66ca0502029257992dd54604',
        'tree' => 'contracts/generated',
        'mount' => 'contract',
        'archive_sha256' => 'd18c005c33f14ab84619dcbc5a162c777734289e7aa9e43c4d81297ecc301e6f',
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
assert($sources === $lock->publicationSources('/tmp/ui', ['larena' => '/tmp/package-larena.tar']));
assert($sources[2] === [
    'repository' => '/tmp/ui',
    'commit' => '274d39770d38b51d66ca0502029257992dd54604',
    'tree' => 'contracts/generated',
    'mount' => 'contract',
    'sha256' => 'd18c005c33f14ab84619dcbc5a162c777734289e7aa9e43c4d81297ecc301e6f',
]);

$rejected = static function (array $data): bool {
    try {
        FrontendRuntimeLock::fromArray($data);
    } catch (RuntimeException) {
        return true;
    }

    return false;
};

foreach ([
    static function (array &$data): void { $data['runtime'] = 'other-framework'; },
    static function (array &$data): void { $data['bundle_id'] = $data['pair_id']; },
    static function (array &$data): void { $data['publication_profile'] = 'unverified-copy'; },
    static function (array &$data): void { $data['schema'] = 'larena.ui.frontend_runtime_lock.v4'; unset($data['schema_version']); },
    static function (array &$data): void { $data['schema_version'] = 4; },
    // A lock in the current shape that declares the legacy version is refused (it is no legacy lock).
    static function (array &$data): void { $data['schema_version'] = 5; },
    static function (array &$data): void { unset($data['schema_version']); },
    static function (array &$data): void { $data['framework_registry']['compatibility_id'] = 'sf-v5.4.0-local.2-wrong-wrong'; },
    static function (array &$data): void { $data['framework_registry']['relative_path'] = '../registry.json'; },
    static function (array &$data): void { $data['framework_registry']['source']['commit'] = str_repeat('0', 39); },
    static function (array &$data): void { $data['ui']['tree'] = 'smart'; },
    static function (array &$data): void { $data['ui']['tree'] = '../distr'; },
    static function (array &$data): void { $data['ui']['mount'] = 'runtime'; },
    static function (array &$data): void { $data['ui']['mount'] = '../ui'; },
    static function (array &$data): void { $data['ui']['files'] = 0; },
    static function (array &$data): void { $data['ui']['files'] = '2671'; },
    static function (array &$data): void { $data['packages']['larena']['tree'] = 'distr'; },
    static function (array &$data): void { $data['packages']['larena']['mount'] = 'ui'; },
    static function (array &$data): void { $data['packages']['larena']['mount'] = 'admin'; },
    static function (array &$data): void { unset($data['packages']['larena']); },
    static function (array &$data): void { unset($data['packages']); },
    static function (array &$data): void { $data['packages'] = [$data['packages']['larena']]; },
    // The legacy slot next to packages is refused: a lock names its packages once.
    static function (array &$data): void { $data['larena'] = $data['packages']['larena']; },
    static function (array &$data): void { $data['boot']['larena_base'] = 'larena/smart/'; },
    // A package built over another free release is refused: the two are always pinned together.
    static function (array &$data): void { $data['packages']['larena']['requires_framework'] = 'ui-000000000000'; },
    static function (array &$data): void { $data['packages']['larena']['release'] = 'larena-000000000000'; },
    static function (array &$data): void { $data['packages']['larena']['repository'] = 'simai/ui-chat'; },
    static function (array &$data): void { $data['packages']['larena']['package_path'] = 'src'; },
    static function (array &$data): void { $data['packages']['larena']['builder']['revision'] = 'main'; },
    static function (array &$data): void { $data['packages']['larena']['framework_source']['repository'] = 'simai/ui'; },
    static function (array &$data): void { unset($data['packages']['larena']['receipt_sha256']); },
    static function (array &$data): void { unset($data['packages']['larena']['manifest_sha256']); },
    static function (array &$data): void { unset($data['packages']['larena']['licence']); },
    static function (array &$data): void { $data['packages']['larena']['licence'] = ['ref' => 'Apache-2.0']; },
    static function (array &$data): void { $data['packages']['larena']['licence']['tier'] = 'Bundled Larena'; },
    static function (array &$data): void { $data['packages']['larena']['licence']['ref'] = ''; },
    static function (array &$data): void { $data['components']['sf-table']['package'] = 'framework'; },
    static function (array &$data): void { $data['components']['sf-button']['package'] = 'larena'; },
    static function (array &$data): void { $data['components']['sf-button']['package'] = 'pro'; },
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
    $data = $lock->toArray();
    $mutate($data);
    assert($rejected($data));
}

// Sources and releases are separate. A package is built from an allowed source (ComponentPackageRequirements::
// SOURCE_REPOSITORIES: simai/larena-ui, simai/ui-admin, simai/ui-pro) and released from an allowed release repository
// (simai/larena-ui). A lock without release_repository is released from its repository, as before the split.
$pinned = $lock->toArray();
$pinnedName = array_key_first($pinned['packages']);
assert(is_string($pinnedName));
$legacy = $pinned;
unset($legacy['packages'][$pinnedName]['release_repository']);
$legacy['packages'][$pinnedName]['repository'] = 'simai/larena-ui';
assert(FrontendRuntimeLock::fromArray($legacy)->packageReleaseRepository($pinnedName) === 'simai/larena-ui');
foreach (\Larena\Ui\Frontend\ComponentPackageRequirements::SOURCE_REPOSITORIES as $sourceRepository) {
    $sourced = $pinned;
    $sourced['packages'][$pinnedName]['repository'] = $sourceRepository;
    $sourced['packages'][$pinnedName]['release_repository'] = 'simai/larena-ui';
    $sourcedLock = FrontendRuntimeLock::fromArray($sourced);
    assert($sourcedLock->package($pinnedName)['repository'] === $sourceRepository);
    assert($sourcedLock->packageReleaseRepository($pinnedName) === 'simai/larena-ui');
}
$refusal = static function (array $data): ?string {
    try {
        FrontendRuntimeLock::fromArray($data);
    } catch (RuntimeException $exception) {
        return $exception->getMessage();
    }

    return null;
};
$adminSourced = $pinned;
$adminSourced['packages'][$pinnedName]['repository'] = 'simai/ui-admin';
foreach ([
    // An unknown source is refused.
    ['repository', 'simai/elsewhere', 'ui_frontend_runtime_source_invalid:' . $pinnedName],
    ['repository', 'simai/ui-chat', 'ui_frontend_runtime_source_invalid:' . $pinnedName],
    // An ui-admin source without a release repository would be released from ui-admin, which publishes no releases.
    ['release_repository', null, 'ui_frontend_runtime_package_release_repository_invalid:' . $pinnedName],
    ['release_repository', 'simai/ui-admin', 'ui_frontend_runtime_package_release_repository_invalid:' . $pinnedName],
    ['release_repository', '', 'ui_frontend_runtime_package_release_repository_invalid:' . $pinnedName],
] as [$field, $value, $error]) {
    $data = $adminSourced;
    if ($value === null) {
        unset($data['packages'][$pinnedName][$field]);
    } else {
        $data['packages'][$pinnedName][$field] = $value;
    }
    assert($refusal($data) === $error, $field . '=' . var_export($value, true));
}

// licence.ref is an SPDX licence expression: ids, LicenseRef-…, AND/OR/WITH and parentheses; nothing else.
foreach ([
    'Apache-2.0',
    'LicenseRef-SIMAI-Commercial',
    'PolyForm-Small-Business-1.0.0 OR PolyForm-Noncommercial-1.0.0 OR LicenseRef-SIMAI-Commercial',
    'MIT AND (Apache-2.0 OR LicenseRef-SIMAI-Commercial)',
    '(MIT OR Apache-2.0) AND BSD-3-Clause',
    'GPL-2.0-or-later WITH Classpath-exception-2.0',
    'GPL-2.0+ WITH AdditionRef-SIMAI-exception',
    '( MIT OR Apache-2.0 )',
] as $expression) {
    $licensed = $pinned;
    $licensed['packages'][$pinnedName]['licence']['ref'] = $expression;
    assert(FrontendRuntimeLock::fromArray($licensed)->packageLicence($pinnedName)['ref'] === $expression, $expression);
}
foreach ([
    '',
    ' MIT',
    'MIT ',
    'MIT  OR Apache-2.0',
    'MIT or Apache-2.0',
    'MIT OR',
    'OR MIT',
    'MIT Apache-2.0',
    'MIT AND AND Apache-2.0',
    '(MIT OR Apache-2.0',
    'MIT OR Apache-2.0)',
    '()',
    '(MIT) WITH Classpath-exception-2.0',
    'MIT WITH',
    'MIT WITH OR',
    'Licensed to SIMAI customers only',
    'Proprietary; see LICENSE.md',
    "MIT\nOR Apache-2.0",
    'MIT/Apache-2.0',
    '-MIT',
    'LicenseRef-',
    str_repeat('(', 9) . 'MIT' . str_repeat(')', 9),
    implode(' OR ', array_fill(0, 40, 'Apache-2.0')),
] as $expression) {
    $licensed = $pinned;
    $licensed['packages'][$pinnedName]['licence']['ref'] = $expression;
    assert($refusal($licensed) === 'ui_frontend_runtime_package_licence_invalid:' . $pinnedName, $expression);
}
$licensed = $pinned;
$licensed['packages'][$pinnedName]['licence']['ref'] = ['MIT'];
assert($rejected($licensed));

// The lock is named without a version; a lock written before the move (`….v6`, no schema_version) is still read.
$labelled = $lock->toArray();
$labelled['schema'] = 'larena.ui.frontend_runtime_lock.v6';
unset($labelled['schema_version']);
assert(FrontendRuntimeLock::fromArray($labelled)->pairId() === $lock->pairId());

// A legacy lock (schema_version 5: one package in a top-level larena slot, its base in boot.larena_base) is read
// as packages.larena, licence Apache-2.0 / bundled-larena; it comes back in the current shape.
$legacyFrom = static function (array $current): array {
    $legacy = [];
    foreach ($current as $key => $value) {
        if ($key === 'schema_version') {
            $legacy[$key] = 5;
        } elseif ($key === 'packages') {
            $package = $value['larena'];
            unset($package['licence']);
            $legacy['larena'] = $package;
        } elseif ($key === 'boot') {
            $legacy['boot'] = [...$value, 'larena_base' => 'larena/smart/'];
        } else {
            $legacy[$key] = $value;
        }
    }

    return $legacy;
};
$legacy = $legacyFrom($lock->toArray());
$fromLegacy = FrontendRuntimeLock::fromArray($legacy);
// A legacy lock names no licence, so it reads as Apache-2.0 whatever the pinned kit carries now.
$expectedFromLegacy = $lock->toArray();
$expectedFromLegacy['packages']['larena']['licence'] = ['ref' => 'Apache-2.0', 'tier' => 'bundled-larena'];
assert($fromLegacy->toArray() === $expectedFromLegacy);
assert($fromLegacy->bundleId() === $lock->bundleId() && $fromLegacy->larenaBase() === 'larena/smart/');
assert($fromLegacy->packageLicence('larena') === ['ref' => 'Apache-2.0', 'tier' => 'bundled-larena']);
assert($fromLegacy->publicationExpectation() === $lock->publicationExpectation());
$legacyLabelled = $legacy;
$legacyLabelled['schema'] = 'larena.ui.frontend_runtime_lock.v5';
unset($legacyLabelled['schema_version']);
assert(FrontendRuntimeLock::fromArray($legacyLabelled)->toArray() === $expectedFromLegacy);
foreach ([
    static function (array &$data): void { $data['boot']['larena_base'] = 'ui/distr/'; },
    static function (array &$data): void { unset($data['boot']['larena_base']); },
    static function (array &$data): void { $data['packages'] = ['larena' => $data['larena']]; },
    static function (array &$data): void { unset($data['larena']); },
    static function (array &$data): void { $data['larena']['licence'] = ['ref' => 'LicenseRef-SIMAI-Pro', 'tier' => 'bundled-larena']; },
    // The legacy shape is read only under its own version.
    static function (array &$data): void { $data['schema_version'] = FrontendRuntimeLock::SCHEMA_VERSION; },
] as $mutate) {
    $data = $legacy;
    $mutate($data);
    assert($rejected($data));
}

// Two packages: each pinned over the same free release, mounted under its own name, composed in lock order.
$second = '0123456789abcdef0123456789abcdef01234567';
$twoPackages = static function (array $current, string $commit): array {
    $data = $current;
    $data['packages']['admin'] = [
        ...$data['packages']['larena'],
        'release' => 'admin-' . substr($commit, 0, 12),
        'commit' => $commit,
        'mount' => 'admin',
        'sha256' => str_repeat('a', 64),
        'files' => 3,
        'licence' => ['ref' => 'LicenseRef-SIMAI-Pro', 'tier' => 'bundled-larena'],
    ];
    $data['components']['sf-admin-menu'] = [
        ...$data['components']['sf-admin-menu'],
        'source' => 'admin/smart/admin-menu',
        'javascript' => 'admin/smart/admin-menu/js/admin-menu.js',
        'css' => 'admin/smart/admin-menu/css/admin-menu.css',
        'package' => 'admin',
    ];
    $data['pair_id'] = FrontendRuntimeLock::composePairId($data['ui']['commit'], array_map(
        static fn (array $package): string => $package['commit'],
        $data['packages'],
    ));
    $data['bundle_id'] = FrontendRuntimeLock::composeBundleId($data['pair_id'], $data['framework_registry']['file_sha256'], $data['publication_profile']);

    return $data;
};
$two = FrontendRuntimeLock::fromArray($twoPackages($lock->toArray(), $second));
assert($two->pairId() === 'ui-194f75ac620f-larena-db37a4d1cf18-admin-0123456789ab');
assert($two->bundleId() === 'ui-194f75ac620f-larena-db37a4d1cf18-admin-0123456789ab-registry-d477077d-exact-git-tree-v2');
assert($two->packageIds() === ['larena' => 'larena-db37a4d1cf18', 'admin' => 'admin-0123456789ab']);
assert($two->packageBases() === ['larena' => 'larena/smart/', 'admin' => 'admin/smart/']);
assert($two->packageLicence('admin') === ['ref' => 'LicenseRef-SIMAI-Pro', 'tier' => 'bundled-larena']);
// The boot config declares every pinned package, in lock order.
assert($two->bootPackagesScript('/b/') === '{larena:{base:"\/b\/larena\/smart\/"},admin:{base:"\/b\/admin\/smart\/"}}');
assert(array_column($two->publicationExpectation()['sources'], 'mount') === ['ui', 'larena', 'admin', 'contract']);
assert(in_array('admin/smart/admin-menu/js/admin-menu.js', $two->requiredRuntimeFiles(), true));
$twoSources = $two->publicationSources('/tmp/ui', ['admin' => '/tmp/package-admin.tar', 'larena' => '/tmp/package-larena.tar']);
assert(array_column($twoSources, 'repository') === ['/tmp/ui', '/tmp/package-larena.tar', '/tmp/package-admin.tar', '/tmp/ui']);
foreach ([
    static fn () => $two->publicationSources('/tmp/ui', '/tmp/package-larena.tar'),
    static fn () => $two->publicationSources('/tmp/ui', ['larena' => '/tmp/package-larena.tar']),
] as $call) {
    $failed = false;
    try {
        $call();
    } catch (RuntimeException) {
        $failed = true;
    }
    assert($failed);
}
foreach ([
    // The order is part of the identity: the packages listed the other way round give another pair id.
    static function (array &$data): void { $data['packages'] = array_reverse($data['packages'], true); },
    static function (array &$data): void { $data['packages']['admin']['requires_framework'] = 'ui-000000000000'; },
    static function (array &$data): void { $data['packages']['admin']['release'] = 'larena-0123456789ab'; },
    static function (array &$data): void { $data['packages']['admin']['mount'] = 'larena'; },
    static function (array &$data): void { $data['components']['sf-admin-menu']['javascript'] = 'larena/smart/admin-menu/js/admin-menu.js'; },
    static function (array &$data): void { $data['packages']['contract'] = $data['packages']['admin']; },
] as $mutate) {
    $data = $two->toArray();
    $mutate($data);
    assert($rejected($data));
}

// Asset sync over a two-package fixture: the packager copies every package into its own mount, the publisher
// installs both, and a hash mismatch of either package fails closed.
$fixture = sys_get_temp_dir() . '/larena-ui-two-package-lock-' . bin2hex(random_bytes(6));
// A child's output goes to files, not pipes: one that fills a pipe nobody reads yet would never finish.
$run = static function (array $command, ?string $cwd = null) use ($fixture): array {
    @mkdir($fixture, 0700, true);
    $capture = $fixture . '/.run-' . bin2hex(random_bytes(4));
    $process = proc_open(
        $command,
        [0 => ['file', '/dev/null', 'r'], 1 => ['file', $capture . '.out', 'w'], 2 => ['file', $capture . '.err', 'w']],
        $pipes,
        $cwd,
    );
    assert(is_resource($process));
    $result = [proc_close($process), (string) file_get_contents($capture . '.out'), (string) file_get_contents($capture . '.err')];
    unlink($capture . '.out');
    unlink($capture . '.err');

    return $result;
};
$removeTree = static function (string $path) use (&$removeTree): void {
    if (is_link($path) || is_file($path)) {
        unlink($path);
        return;
    }
    if (!is_dir($path)) {
        return;
    }
    foreach (scandir($path) ?: [] as $entry) {
        if ($entry !== '.' && $entry !== '..') {
            $removeTree($path . '/' . $entry);
        }
    }
    rmdir($path);
};
try {
    $repository = $fixture . '/ui';
    $files = [
        'distr/core/css/core.min.css' => "body{}\n",
        'distr/core/js/core.min.js' => "window.sf=1;\n",
        'distr/core/js/smart-base.js' => "window.sfBase=1;\n",
        'distr/smart/buttons/js/buttons.js' => "window.sfButton=1;\n",
        'contracts/generated/framework-contract-registry.json' => "{\"compatibility\":{}}\n",
        'contracts/generated/documentation-source.json' => "{}\n",
    ];
    foreach ($files as $path => $contents) {
        @mkdir(dirname($repository . '/' . $path), 0700, true);
        file_put_contents($repository . '/' . $path, $contents);
    }
    $git = static function (string ...$arguments) use ($run, $repository): string {
        [$exit, $stdout] = $run(['git', '-C', $repository, '-c', 'user.name=Fixture', '-c', 'user.email=fixture@example.invalid',
            '-c', 'commit.gpgsign=false', ...$arguments]);
        assert($exit === 0);

        return $stdout;
    };
    $git('init', '-q');
    $git('add', '.');
    $git('commit', '-q', '-m', 'fixture');
    $uiCommit = trim($git('rev-parse', 'HEAD'));
    $archiveSha = static fn (string $tree): string => hash('sha256', $git('archive', '--format=tar', $uiCommit, $tree));
    $packageTar = static function (string $path, array $members) use ($run): string {
        $script = 'import io,sys,tarfile,json' . "\n"
            . 'members=json.loads(sys.argv[2])' . "\n"
            . 'with tarfile.open(sys.argv[1],"w",format=tarfile.USTAR_FORMAT) as t:' . "\n"
            . ' for n,c in members.items():' . "\n"
            . '  d=c.encode();i=tarfile.TarInfo(n);i.size=len(d);i.mode=0o644;i.mtime=0;t.addfile(i,io.BytesIO(d))' . "\n";
        [$exit] = $run(['python3', '-I', '-c', $script, $path, json_encode($members, JSON_THROW_ON_ERROR)]);
        assert($exit === 0);

        return (string) hash_file('sha256', $path);
    };
    $archives = [
        'larena' => $fixture . '/package-larena.tar',
        'admin' => $fixture . '/package-admin.tar',
    ];
    $packageFiles = [
        'larena' => ['smart/table/js/table.js' => "window.sfTable=1;\n"],
        'admin' => ['smart/admin-menu/js/admin-menu.js' => "window.sfAdminMenu=1;\n", 'smart/admin-menu/css/admin-menu.css' => ".m{}\n"],
    ];
    $packageEntry = static fn (string $name, string $commit, string $sha, int $count, string $licence): array => [
        'release' => FrontendRuntimeLock::packageRelease($name, $commit),
        'repository' => 'simai/larena-ui',
        'tag' => null,
        'commit' => $commit,
        'tree' => 'smart',
        'mount' => $name,
        'sha256' => $sha,
        'files' => $count,
        'requires_framework' => 'ui-' . substr($uiCommit, 0, 12),
        'package_path' => 'components',
        'package_tree' => str_repeat('1', 40),
        'framework_source' => ['repository' => 'simai/ui-source', 'revision' => str_repeat('2', 40)],
        'builder' => ['repository' => 'simai/ui-builder', 'revision' => str_repeat('3', 40)],
        'manifest_sha256' => str_repeat('4', 64),
        'receipt_sha256' => str_repeat('5', 64),
        'licence' => ['ref' => $licence, 'tier' => 'bundled-larena'],
    ];
    $registrySha = hash('sha256', $files['contracts/generated/framework-contract-registry.json']);
    $fixtureLock = [
        'schema' => FrontendRuntimeLock::SCHEMA,
        'schema_version' => FrontendRuntimeLock::SCHEMA_VERSION,
        'runtime' => 'simai-framework',
        'pair_id' => '',
        'bundle_id' => '',
        'publication_profile' => 'exact-git-tree-v2',
        'tag' => null,
        'ui' => ['release' => 'ui-' . substr($uiCommit, 0, 12), 'repository' => 'simai/ui', 'tag' => null, 'commit' => $uiCommit,
            'tree' => 'distr', 'mount' => 'ui', 'sha256' => $archiveSha('distr'), 'files' => 4],
        'packages' => [
            'larena' => $packageEntry('larena', str_repeat('6', 40), $packageTar($archives['larena'], $packageFiles['larena']), 1, 'Apache-2.0'),
            'admin' => $packageEntry('admin', str_repeat('7', 40), $packageTar($archives['admin'], $packageFiles['admin']), 2, 'LicenseRef-SIMAI-Pro'),
        ],
        'framework_registry' => [
            'schema_id' => 'simai.framework.contract-registry',
            'compatibility_id' => 'ui-' . substr($uiCommit, 0, 12),
            'profile' => 'plain-assets-v1',
            'relative_path' => 'contract/contracts/generated/framework-contract-registry.json',
            'file_sha256' => $registrySha,
            'source' => ['tag' => null, 'commit' => $uiCommit, 'tree' => 'contracts/generated', 'mount' => 'contract',
                'sha256' => $archiveSha('contracts/generated'), 'files' => 2, 'tree_oid' => trim($git('rev-parse', $uiCommit . ':contracts/generated'))],
            'documentation_source' => ['schema' => 'docara.documentation_source.v1',
                'relative_path' => 'contract/contracts/generated/documentation-source.json', 'file_sha256' => hash('sha256', "{}\n")],
        ],
        'boot' => ['css' => 'ui/distr/core/css/core.min.css', 'javascript' => 'ui/distr/core/js/core.min.js',
            'smart_base' => 'ui/distr/core/js/smart-base.js', 'ui_base' => 'ui/distr/', 'smart_base_path' => 'ui/distr/smart/'],
        'components' => [
            'sf-button' => ['source' => 'ui/distr/smart/buttons', 'javascript' => 'ui/distr/smart/buttons/js/buttons.js', 'css' => null, 'attributes' => [], 'package' => 'framework'],
            'sf-table' => ['source' => 'larena/smart/table', 'javascript' => 'larena/smart/table/js/table.js', 'css' => null, 'attributes' => [], 'package' => 'larena'],
            'sf-admin-menu' => ['source' => 'admin/smart/admin-menu', 'javascript' => 'admin/smart/admin-menu/js/admin-menu.js',
                'css' => 'admin/smart/admin-menu/css/admin-menu.css', 'attributes' => [], 'package' => 'admin'],
        ],
    ];
    $fixtureLock['pair_id'] = FrontendRuntimeLock::composePairId($uiCommit, ['larena' => str_repeat('6', 40), 'admin' => str_repeat('7', 40)]);
    $fixtureLock['bundle_id'] = FrontendRuntimeLock::composeBundleId($fixtureLock['pair_id'], $registrySha, 'exact-git-tree-v2');
    $fixtureRuntime = FrontendRuntimeLock::fromArray($fixtureLock);
    assert($fixtureRuntime->bundleId() === 'ui-' . substr($uiCommit, 0, 12) . '-larena-666666666666-admin-777777777777-registry-'
        . substr($registrySha, 0, 8) . '-exact-git-tree-v2');

    $packager = dirname(__DIR__, 2) . '/scripts/package-frontend-runtime-artifact.py';
    $package = static function (array $lockData, string $output, array $archiveArguments) use ($fixture, $packager, $repository, $run): array {
        $lockPath = $fixture . '/lock-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents($lockPath, json_encode($lockData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");

        return $run(['python3', $packager, '--lock', $lockPath, '--ui-repository', $repository, ...$archiveArguments, '--output-root', $output]);
    };
    $archiveArguments = ['--package-archive', 'larena=' . $archives['larena'], '--package-archive', 'admin=' . $archives['admin']];
    [$exit, $stdout, $stderr] = $package($fixtureLock, $fixture . '/out', $archiveArguments);
    assert($exit === 0, $stderr);
    $artifact = $fixture . '/out/' . $fixtureRuntime->bundleId();
    $manifest = json_decode((string) file_get_contents($artifact . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    assert(array_column($manifest['sources'], 'mount') === ['ui', 'larena', 'admin', 'contract']);
    assert(array_column($manifest['sources'], 'archive') === ['sources/ui.tar.gz', 'sources/larena.tar.gz', 'sources/admin.tar.gz', 'sources/contract.tar.gz']);
    assert(is_file($artifact . '/sources/larena.tar.gz') && is_file($artifact . '/sources/admin.tar.gz'));

    // Every package's archive is required; a package left out is refused.
    [$exit] = $package($fixtureLock, $fixture . '/out-missing', ['--package-archive', 'larena=' . $archives['larena']]);
    assert($exit !== 0);
    // A hash mismatch of either package fails closed and names that package.
    foreach (['larena', 'admin'] as $name) {
        $drifted = $fixtureLock;
        $drifted['packages'][$name]['sha256'] = str_repeat('0', 64);
        [$exit, , $stderr] = $package($drifted, $fixture . '/out-drift-' . $name, $archiveArguments);
        assert($exit !== 0 && str_contains($stderr, 'runtime_archive_sha256_mismatch:' . $name), $stderr);
    }

    // The publisher installs both packages into their mounts of the bundle.
    $public = $fixture . '/public';
    mkdir($public, 0700, true);
    (new VerifiedAssetBundlePublisher())->publishArtifactDirectory(
        $fixtureRuntime->publicationExpectation(),
        $artifact,
        $public,
        $fixture . '/state/sf-active.json',
    );
    $bundle = $public . '/' . $fixtureRuntime->bundleId();
    assert(file_get_contents($bundle . '/larena/smart/table/js/table.js') === "window.sfTable=1;\n");
    assert(file_get_contents($bundle . '/admin/smart/admin-menu/js/admin-menu.js') === "window.sfAdminMenu=1;\n");
    foreach ($fixtureRuntime->requiredRuntimeFiles() as $required) {
        assert(is_file($bundle . '/' . $required), $required);
    }

    // An artifact whose package archive was swapped fails integrity for that package, whichever it is.
    foreach (['larena', 'admin'] as $name) {
        $tampered = $fixture . '/tampered-' . $name;
        $run(['cp', '-R', $artifact, $tampered]);
        $other = $name === 'larena' ? 'admin' : 'larena';
        copy($artifact . '/sources/' . $other . '.tar.gz', $tampered . '/sources/' . $name . '.tar.gz');
        $failed = null;
        try {
            (new VerifiedAssetBundlePublisher())->publishArtifactDirectory(
                $fixtureRuntime->publicationExpectation(),
                $tampered,
                $fixture . '/public-' . $name,
                $fixture . '/state-' . $name . '/sf-active.json',
            );
        } catch (RuntimeException $exception) {
            $failed = $exception->getMessage();
        }
        assert(is_string($failed) && str_ends_with($failed, ':' . $name), (string) $failed);
    }

    // A legacy one-package lock is still packaged with --larena-package-archive, into the same manifest bytes.
    $single = $fixtureLock;
    unset($single['packages']['admin'], $single['components']['sf-admin-menu']);
    $single['pair_id'] = FrontendRuntimeLock::composePairId($uiCommit, ['larena' => str_repeat('6', 40)]);
    $single['bundle_id'] = FrontendRuntimeLock::composeBundleId($single['pair_id'], $registrySha, 'exact-git-tree-v2');
    FrontendRuntimeLock::fromArray($single);
    [$exit, $current, $stderr] = $package($single, $fixture . '/out-single', ['--larena-package-archive', $archives['larena']]);
    assert($exit === 0, $stderr);
    $singleLegacy = $legacyFrom($single);
    assert(FrontendRuntimeLock::fromArray($singleLegacy)->bundleId() === $single['bundle_id']);
    [$exit, $legacyRun, $stderr] = $package($singleLegacy, $fixture . '/out-single-legacy', ['--larena-package-archive', $archives['larena']]);
    assert($exit === 0, $stderr);
    assert(json_decode($current, true)['manifest_sha256'] === json_decode($legacyRun, true)['manifest_sha256']);
} finally {
    $removeTree($fixture);
}

echo "FrontendRuntimeLockTest passed.\n";
