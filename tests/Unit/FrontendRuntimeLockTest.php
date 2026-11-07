<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Larena\Core\Assets\VerifiedAssetBundlePublisher;
use Larena\Ui\Frontend\FrontendRuntimeLock;

$lock = FrontendRuntimeLock::bundled();
// The free Framework release and the Larena components package built over it, pinned together.
assert($lock->pairId() === 'ui-b58b5dec35e4-pro-9caaf616cbd5');
assert($lock->frameworkId() === 'ui-b58b5dec35e4');
assert($lock->packageIds() === ['pro' => 'pro-9caaf616cbd5']);
assert($lock->package('pro')['requires_framework'] === $lock->frameworkId());
// The component package pro is built from simai/ui-pro and released there; inside Larena it is bundled.
assert($lock->package('pro')['repository'] === 'simai/ui-pro' && $lock->package('pro')['package_path'] === 'components');
assert($lock->packageReleaseRepository('pro') === 'simai/ui-pro');
assert($lock->packageLicence('pro') === ['ref' => 'PolyForm-Small-Business-1.0.0 OR PolyForm-Noncommercial-1.0.0 OR LicenseRef-SIMAI-Commercial', 'tier' => 'bundled-larena']);
assert($lock->bundleId() === 'ui-b58b5dec35e4-pro-9caaf616cbd5-registry-1b349fa2-exact-git-tree-v2');
assert($lock->uiBase() === 'ui/distr/' && $lock->smartBase() === 'ui/distr/smart/');
assert($lock->packageBases() === ['pro' => 'pro/smart/']);
assert($lock->bootPackagesScript('/b/') === '{pro:{base:"\/b\/pro\/smart\/"}}');
assert($lock->publicationProfile() === 'exact-git-tree-v2');
$registry = $lock->frameworkRegistry();
assert(($registry['schema_id'] ?? null) === 'simai.framework.contract-registry');
assert(($registry['compatibility_id'] ?? null) === $lock->frameworkId());
assert(($registry['file_sha256'] ?? null) === '1b349fa2c0d66155275e22b92eb62d8be7a5fb16792804c1acdf8fabcda2071c');

// The lock file is written in the current shape: packages, no top-level larena slot, no boot.larena_base.
$file = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/resources/sf/runtime-lock.json'), true, 512, JSON_THROW_ON_ERROR);
assert($file['schema'] === 'larena.ui.frontend_runtime_lock' && $file['schema_version'] === FrontendRuntimeLock::SCHEMA_VERSION);
assert(array_keys($file['packages']) === ['pro'] && !array_key_exists('larena', $file) && !array_key_exists('larena_base', $file['boot']));
assert($file === $lock->toArray());

// The single-package bundle id is byte-identical to the one every bundle had before packages were a list.
assert(FrontendRuntimeLock::composeBundleId(
    FrontendRuntimeLock::composePairId('b58b5dec35e4c542774ef82c85cfb3aa302895f0', ['pro' => '9caaf616cbd5e52dadc202bc822492772dfdcf6c']),
    '1b349fa2c0d66155275e22b92eb62d8be7a5fb16792804c1acdf8fabcda2071c',
    'exact-git-tree-v2',
) === 'ui-b58b5dec35e4-pro-9caaf616cbd5-registry-1b349fa2-exact-git-tree-v2');

$expectation = $lock->publicationExpectation();
assert($expectation['schema'] === 'larena.ui.frontend_runtime_artifact');
assert($expectation['runtime'] === 'simai-framework');
assert($expectation['bundle_id'] === $lock->bundleId());
assert($expectation['publication_profile'] === $lock->publicationProfile());
assert($expectation['sources'] === [
    [
        'commit' => 'b58b5dec35e4c542774ef82c85cfb3aa302895f0',
        'tree' => 'distr',
        'mount' => 'ui',
        'archive_sha256' => '5fc4db8beef52a60281786e8045eea2681b96e2a8cddda8a36b5a06ea9eaabdc',
        'files' => 12032,
    ],
    [
        'commit' => '9caaf616cbd5e52dadc202bc822492772dfdcf6c',
        'tree' => 'smart',
        'mount' => 'pro',
        'archive_sha256' => '768bd72adcccdeb91136c700f67fec66eba7f03ffc70520a9d1d6a478c8d48c3',
        'files' => 722,
    ],
    [
        'commit' => '8f998c1bb6e66c4069b4e612bc2131a863d576d4',
        'tree' => 'contracts/generated',
        'mount' => 'contract',
        'archive_sha256' => '684dcd8f0080b54fddc1642a6cf1da2e48c0916ee6bb611f8b2134dd5305064a',
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
    'ui/distr/smart/button/js/button.min.js',
    'ui/distr/smart/input/js/input.min.js',
    'ui/distr/smart/input/css/input.min.css',
    'pro/smart/view-table/js/view-table.min.js',
    'pro/smart/view-table/css/view-table.min.css',
    'pro/smart/view/js/view.min.js',
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
// The four package tags resolve inside the package's tree, every other one inside the free Smart tree.
foreach ($lock->toArray()['components'] as $tag => $component) {
    $larena = in_array($tag, ['sf-view-table', 'sf-view', 'sf-view-properties', 'sf-admin-menu'], true);
    assert($component['package'] === ($larena ? 'pro' : 'framework'));
    assert(str_starts_with($component['javascript'], $larena ? 'pro/smart/' : 'ui/distr/smart/'));
}

$sources = $lock->publicationSources('/tmp/ui', '/tmp/package-larena.tar');
assert(count($sources) === 3);
assert($sources[0]['repository'] === '/tmp/ui');
assert($sources[1]['repository'] === '/tmp/package-larena.tar');
assert($sources === $lock->publicationSources('/tmp/ui', ['pro' => '/tmp/package-larena.tar']));
assert($sources[2] === [
    'repository' => '/tmp/ui',
    'commit' => '8f998c1bb6e66c4069b4e612bc2131a863d576d4',
    'tree' => 'contracts/generated',
    'mount' => 'contract',
    'sha256' => '684dcd8f0080b54fddc1642a6cf1da2e48c0916ee6bb611f8b2134dd5305064a',
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
    static function (array &$data): void { $data['packages']['pro']['tree'] = 'distr'; },
    static function (array &$data): void { $data['packages']['pro']['mount'] = 'ui'; },
    static function (array &$data): void { $data['packages']['pro']['mount'] = 'admin'; },
    static function (array &$data): void { unset($data['packages']['pro']); },
    static function (array &$data): void { unset($data['packages']); },
    static function (array &$data): void { $data['packages'] = [$data['packages']['pro']]; },
    // The legacy slot next to packages is refused: a lock names its packages once.
    static function (array &$data): void { $data['larena'] = $data['packages']['pro']; },
    static function (array &$data): void { $data['boot']['larena_base'] = 'larena/smart/'; },
    // A package built over another free release is refused: the two are always pinned together.
    static function (array &$data): void { $data['packages']['pro']['requires_framework'] = 'ui-000000000000'; },
    static function (array &$data): void { $data['packages']['pro']['release'] = 'larena-000000000000'; },
    static function (array &$data): void { $data['packages']['pro']['repository'] = 'simai/ui-chat'; },
    static function (array &$data): void { $data['packages']['pro']['package_path'] = 'src'; },
    static function (array &$data): void { $data['packages']['pro']['builder']['revision'] = 'main'; },
    static function (array &$data): void { $data['packages']['pro']['framework_source']['repository'] = 'simai/ui'; },
    static function (array &$data): void { unset($data['packages']['pro']['receipt_sha256']); },
    static function (array &$data): void { unset($data['packages']['pro']['manifest_sha256']); },
    static function (array &$data): void { unset($data['packages']['pro']['licence']); },
    static function (array &$data): void { $data['packages']['pro']['licence'] = ['ref' => 'Apache-2.0']; },
    static function (array &$data): void { $data['packages']['pro']['licence']['tier'] = 'Bundled Larena'; },
    static function (array &$data): void { $data['packages']['pro']['licence']['ref'] = ''; },
    static function (array &$data): void { $data['components']['sf-view-table']['package'] = 'framework'; },
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

// A set built over another pair is pinned with a pair receipt of the same release for the pinned one (Framework
// decision 2026-11-07); a receipt for a third pair, or for the pair it was built over, is refused.
$receipted = $lock->toArray();
$receipted['packages']['pro']['requires_framework'] = 'ui-000000000000';
$receipted['packages']['pro']['pair_receipt'] = ['pair' => $lock->frameworkId(), 'sha256' => str_repeat('b', 64), 'asset' => 'pair-receipt-' . $lock->frameworkId() . '.json'];
assert(!$rejected($receipted));
$third = $receipted;
$third['packages']['pro']['pair_receipt'] = ['pair' => 'ui-111111111111', 'sha256' => str_repeat('b', 64), 'asset' => 'pair-receipt-ui-111111111111.json'];
assert($rejected($third));
$own = $receipted;
$own['packages']['pro']['pair_receipt'] = ['pair' => 'ui-000000000000', 'sha256' => str_repeat('b', 64), 'asset' => 'pair-receipt-ui-000000000000.json'];
assert($rejected($own));
// The free pair may pin its own ui-source, which a pair receipt over it must name; it is a full revision.
$withSource = $receipted;
$withSource['ui']['source_revision'] = str_repeat('e', 40);
assert(!$rejected($withSource));
$withSource['ui']['source_revision'] = 'e1';
assert($rejected($withSource));

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
// A legacy lock had one package and it was named larena: the pinned package under that name (the pro package was
// larena until Framework renamed it, owner decision q-4ac7d07595).
$asLarena = static function (array $current): array {
    $package = [...$current['packages']['pro'], 'release' => 'larena-' . substr($current['packages']['pro']['commit'], 0, 12), 'mount' => 'larena'];
    $current['packages'] = ['larena' => $package];
    foreach ($current['components'] as $tag => $component) {
        if ($component['package'] === 'pro') {
            $current['components'][$tag] = [...$component, 'package' => 'larena',
                'source' => 'larena/' . substr($component['source'], 4), 'javascript' => 'larena/' . substr($component['javascript'], 4),
                'css' => $component['css'] === null ? null : 'larena/' . substr($component['css'], 4)];
        }
    }
    $current['pair_id'] = FrontendRuntimeLock::composePairId($current['ui']['commit'], ['larena' => $package['commit']]);
    $current['bundle_id'] = FrontendRuntimeLock::composeBundleId($current['pair_id'], $current['framework_registry']['file_sha256'], $current['publication_profile']);

    return $current;
};
$larenaLock = FrontendRuntimeLock::fromArray($asLarena($lock->toArray()));
$legacy = $legacyFrom($larenaLock->toArray());
$fromLegacy = FrontendRuntimeLock::fromArray($legacy);
// A legacy lock names no licence, so it reads as Apache-2.0 whatever the pinned kit carries now.
$expectedFromLegacy = $larenaLock->toArray();
$expectedFromLegacy['packages']['larena']['licence'] = ['ref' => 'Apache-2.0', 'tier' => 'bundled-larena'];
assert($fromLegacy->toArray() === $expectedFromLegacy);
assert($fromLegacy->bundleId() === $larenaLock->bundleId() && $fromLegacy->larenaBase() === 'larena/smart/');
assert($fromLegacy->packageLicence('larena') === ['ref' => 'Apache-2.0', 'tier' => 'bundled-larena']);
assert($fromLegacy->publicationExpectation() === $larenaLock->publicationExpectation());
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
        ...$data['packages']['pro'],
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
assert($two->pairId() === 'ui-b58b5dec35e4-pro-9caaf616cbd5-admin-0123456789ab');
assert($two->bundleId() === 'ui-b58b5dec35e4-pro-9caaf616cbd5-admin-0123456789ab-registry-1b349fa2-exact-git-tree-v2');
assert($two->packageIds() === ['pro' => 'pro-9caaf616cbd5', 'admin' => 'admin-0123456789ab']);
assert($two->packageBases() === ['pro' => 'pro/smart/', 'admin' => 'admin/smart/']);
assert($two->packageLicence('admin') === ['ref' => 'LicenseRef-SIMAI-Pro', 'tier' => 'bundled-larena']);
// The boot config declares every pinned package, in lock order.
assert($two->bootPackagesScript('/b/') === '{pro:{base:"\/b\/pro\/smart\/"},admin:{base:"\/b\/admin\/smart\/"}}');
assert(array_column($two->publicationExpectation()['sources'], 'mount') === ['ui', 'pro', 'admin', 'contract']);
assert(in_array('admin/smart/admin-menu/js/admin-menu.js', $two->requiredRuntimeFiles(), true));
$twoSources = $two->publicationSources('/tmp/ui', ['admin' => '/tmp/package-admin.tar', 'pro' => '/tmp/package-larena.tar']);
assert(array_column($twoSources, 'repository') === ['/tmp/ui', '/tmp/package-larena.tar', '/tmp/package-admin.tar', '/tmp/ui']);
foreach ([
    static fn () => $two->publicationSources('/tmp/ui', '/tmp/package-larena.tar'),
    static fn () => $two->publicationSources('/tmp/ui', ['chat' => '/tmp/package-larena.tar']),
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
    static function (array &$data): void { $data['packages']['admin']['mount'] = 'pro'; },
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
            'sf-view-table' => ['source' => 'larena/smart/table', 'javascript' => 'larena/smart/table/js/table.js', 'css' => null, 'attributes' => [], 'package' => 'larena'],
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

    // A set built over another pair is packaged with its pair receipt (Framework decision 2026-11-07), checked byte
    // for byte against the lock and kept in the artifact; without it, or with one of another release or other bytes,
    // the packaging fails closed.
    $admin = $fixtureLock['packages']['admin'];
    $pairDocument = [
        'schema' => 'simai.framework.set-pair-receipt', 'schema_version' => 1, 'set' => 'admin', 'release' => $admin['release'],
        'package' => ['source_revision' => $admin['commit'], 'archive_sha256' => $admin['sha256'], 'manifest_sha256' => $admin['manifest_sha256'], 'receipt_sha256' => $admin['receipt_sha256']],
        'built_over' => ['pair' => 'ui-000000000000'],
        'pair' => ['id' => $fixtureLock['ui']['release'], 'commit' => $fixtureLock['ui']['commit'], 'source_revision' => $fixtureLock['packages']['larena']['framework_source']['revision']],
        'checks' => ['equivalence' => ['status' => 'same-bytes']], 'status' => 'passed',
    ];
    $pairLock = static function (array $document) use ($fixtureLock, $fixture): array {
        $bytes = json_encode($document, JSON_THROW_ON_ERROR);
        $path = $fixture . '/pair-receipt-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents($path, $bytes);
        $lockData = $fixtureLock;
        $lockData['packages']['admin']['requires_framework'] = 'ui-000000000000';
        $lockData['packages']['admin']['pair_receipt'] = ['pair' => $fixtureLock['ui']['release'], 'sha256' => hash('sha256', $bytes), 'asset' => 'pair-receipt-' . $fixtureLock['ui']['release'] . '.json'];

        return [$lockData, $path];
    };
    [$receiptedLock, $receiptPath] = $pairLock($pairDocument);
    [$exit, , $stderr] = $package($receiptedLock, $fixture . '/out-pair', [...$archiveArguments, '--pair-receipt', 'admin=' . $receiptPath]);
    assert($exit === 0, $stderr);
    $pairManifest = json_decode((string) file_get_contents($fixture . '/out-pair/' . $fixtureRuntime->bundleId() . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    $kept = $pairManifest['sources'][2]['pair_receipt'] ?? null;
    assert(is_array($kept) && is_file($fixture . '/out-pair/' . $fixtureRuntime->bundleId() . '/' . $kept['path'])
        && $kept['sha256'] === $receiptedLock['packages']['admin']['pair_receipt']['sha256'] && !isset($pairManifest['sources'][1]['pair_receipt']));
    [$exit, , $stderr] = $package($receiptedLock, $fixture . '/out-pair-missing', $archiveArguments);
    assert($exit !== 0 && str_contains($stderr, 'component_package_pair_receipt_missing:admin'), $stderr);
    foreach (['release' => ['release' => 'admin-000000000000'], 'archive_sha256' => ['package' => ['archive_sha256' => str_repeat('d', 64)] + $pairDocument['package']]] as $field => $change) {
        [$otherLock, $otherPath] = $pairLock($change + $pairDocument);
        [$exit, , $stderr] = $package($otherLock, $fixture . '/out-pair-' . $field, [...$archiveArguments, '--pair-receipt', 'admin=' . $otherPath]);
        assert($exit !== 0 && str_contains($stderr, 'component_package_pair_receipt_mismatch:admin:' . $field), $stderr);
    }
    // The pair's ui-source must be the one the set built over that pair names.
    [$otherLock, $otherPath] = $pairLock(['pair' => ['source_revision' => str_repeat('c', 40)] + $pairDocument['pair']] + $pairDocument);
    [$exit, , $stderr] = $package($otherLock, $fixture . '/out-pair-source', [...$archiveArguments, '--pair-receipt', 'admin=' . $otherPath]);
    assert($exit !== 0 && str_contains($stderr, 'component_package_pair_receipt_mismatch:admin:pair_source_revision'), $stderr);
    // The free pair's own ui-source in the lock decides (Framework, msg-d4dc9d67: never accept a receipt over another
    // revision of the new pair); without it, the set built over the pair names it, and with neither the receipt is refused.
    $pinned = $receiptedLock;
    $pinned['ui']['source_revision'] = str_repeat('e', 40);
    [$exit, , $stderr] = $package($pinned, $fixture . '/out-pair-pinned', [...$archiveArguments, '--pair-receipt', 'admin=' . $receiptPath]);
    assert($exit !== 0 && str_contains($stderr, 'component_package_pair_receipt_mismatch:admin:pair_source_revision'), $stderr);
    [$pinnedLock, $pinnedPath] = $pairLock(['pair' => ['source_revision' => str_repeat('e', 40)] + $pairDocument['pair']] + $pairDocument);
    $pinnedLock['ui']['source_revision'] = str_repeat('e', 40);
    [$exit, , $stderr] = $package($pinnedLock, $fixture . '/out-pair-pinned-ok', [...$archiveArguments, '--pair-receipt', 'admin=' . $pinnedPath]);
    assert($exit === 0, $stderr);
    [$exit, , $stderr] = $package($receiptedLock, $fixture . '/out-pair-sha', [...$archiveArguments, '--pair-receipt', 'admin=' . $pairLock($pairDocument + ['extra' => 1])[1]]);
    assert($exit !== 0 && str_contains($stderr, 'component_package_pair_receipt_mismatch:admin:sha256'), $stderr);

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
