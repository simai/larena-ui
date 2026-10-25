<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Larena\Ui\Frontend\ComponentPackageRequirements;
use Larena\Ui\Frontend\FrontendRuntimeLock;

$lockFile = dirname(__DIR__, 2) . '/resources/sf/runtime-lock.json';
$lock = json_decode((string) file_get_contents($lockFile), true, 512, JSON_THROW_ON_ERROR);
assert(is_array($lock));
// The pinned pair moves with every repin; the test reads it from the lock instead of naming it.
$pinnedRelease = $lock['packages']['larena']['release'];
$pinnedFramework = $lock['packages']['larena']['requires_framework'];

/** @param array<string, mixed> $sets */
function componentConsumer(string $name, array $sets): array
{
    return ['name' => $name, 'extra' => ['larena' => ['component_packages' => $sets]]];
}

$admin = componentConsumer('larena/admin', ['larena' => [
    'releases' => '*',
    'release_repository' => 'simai/larena-ui',
    'source_repositories' => ['simai/larena-ui', 'simai/ui-admin', 'simai/ui-pro'],
]]);

// ok: the bundled lock pins the set admin declares, built over the pinned Framework release, with every hash.
$ok = ComponentPackageRequirements::fromComposerManifests([$admin, ['name' => 'larena/core', 'extra' => []]])->check($lock);
assert($ok['schema'] === 'larena.ui.component_package_check' && $ok['status'] === 'passed' && $ok['errors'] === []);
assert($ok['framework_release'] === $pinnedFramework);
assert($ok['pinned'] === ['larena' => $pinnedRelease]);
assert($ok['declared'] === ['larena' => ['larena/admin']] && $ok['unrequested'] === []);

// An exact release list accepts the pinned release.
$exact = componentConsumer('larena/admin', ['larena' => ['releases' => ['larena-000000000000', $pinnedRelease]]]);
assert(ComponentPackageRequirements::fromComposerManifests([$exact])->check($lock)['errors'] === []);

// missing: a package needs a set the lock does not pin.
$chat = componentConsumer('larena/chat', ['chat' => ['releases' => '*']]);
$missing = ComponentPackageRequirements::fromComposerManifests([$admin, $chat])->check($lock);
assert($missing['status'] === 'failed' && $missing['errors'] === ['component_package_missing:chat:larena/chat']);

// not accepted: the pinned release is outside the declared list.
$older = componentConsumer('larena/admin', ['larena' => ['releases' => ['larena-000000000000']]]);
$notAccepted = ComponentPackageRequirements::fromComposerManifests([$older])->check($lock);
assert($notAccepted['errors'] === ['component_package_release_not_accepted:larena:larena/admin']);

// The declaration is read as release repository and accepted sources.
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->declarations() === ['larena/admin' => ['larena' => [
    'releases' => '*',
    'release_repository' => 'simai/larena-ui',
    'source_repositories' => ['simai/larena-ui', 'simai/ui-admin', 'simai/ui-pro'],
]]]);

// release repository: the pinned set is released from another repository than the declaration names.
$elsewhere = componentConsumer('larena/admin', ['larena' => ['releases' => '*', 'release_repository' => 'simai/elsewhere']]);
assert(ComponentPackageRequirements::fromComposerManifests([$elsewhere])->check($lock)['errors']
    === ['component_package_release_repository_mismatch:larena:larena/admin']);
// `repository`, the key used before sources and releases were split, is the release repository.
$aliased = componentConsumer('larena/admin', ['larena' => ['releases' => '*', 'repository' => 'simai/larena-ui']]);
assert(ComponentPackageRequirements::fromComposerManifests([$aliased])->declarations()['larena/admin']['larena']
    === ['releases' => '*', 'release_repository' => 'simai/larena-ui', 'source_repositories' => null]);
assert(ComponentPackageRequirements::fromComposerManifests([$aliased])->check($lock)['errors'] === []);
$aliasedElsewhere = componentConsumer('larena/admin', ['larena' => ['releases' => '*', 'repository' => 'simai/elsewhere']]);
assert(ComponentPackageRequirements::fromComposerManifests([$aliasedElsewhere])->check($lock)['errors']
    === ['component_package_release_repository_mismatch:larena:larena/admin']);

// source repositories: a set built from simai/ui-admin and released from simai/larena-ui passes; a declaration that
// accepts only larena-ui refuses it; a lock that names an unknown source or release repository fails for everyone.
$adminSourced = $lock;
$adminSourced['packages']['larena']['repository'] = 'simai/ui-admin';
$adminSourced['packages']['larena']['release_repository'] = 'simai/larena-ui';
$adminSourcedCheck = ComponentPackageRequirements::fromComposerManifests([$admin])->check($adminSourced);
assert($adminSourcedCheck['status'] === 'passed' && $adminSourcedCheck['errors'] === []);
assert(ComponentPackageRequirements::releaseRepository($adminSourced['packages']['larena']) === 'simai/larena-ui');
// Without release_repository a set is released from its own repository (locks written before the split).
$unsplit = $lock['packages']['larena'];
unset($unsplit['release_repository']);
assert(ComponentPackageRequirements::releaseRepository($unsplit) === $unsplit['repository']);
$larenaUiOnly = componentConsumer('larena/admin', ['larena' => ['releases' => '*', 'source_repositories' => ['simai/larena-ui']]]);
assert(ComponentPackageRequirements::fromComposerManifests([$larenaUiOnly])->check($adminSourced)['errors']
    === ['component_package_source_repository_not_accepted:larena:larena/admin']);
$larenaUiSourced = $lock;
$larenaUiSourced['packages']['larena']['repository'] = 'simai/larena-ui';
unset($larenaUiSourced['packages']['larena']['release_repository']);
assert(ComponentPackageRequirements::fromComposerManifests([$larenaUiOnly])->check($larenaUiSourced)['errors'] === []);
$unknownSource = $lock;
$unknownSource['packages']['larena']['repository'] = 'simai/elsewhere';
$unknownSource['packages']['larena']['release_repository'] = 'simai/larena-ui';
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check($unknownSource)['errors'] === [
    'component_package_source_repository_invalid:larena',
    'component_package_source_repository_not_accepted:larena:larena/admin',
]);
// Released from where it is built (no release_repository) works only for a release repository.
$unreleased = $adminSourced;
unset($unreleased['packages']['larena']['release_repository']);
assert(ComponentPackageRequirements::fromComposerManifests([])->check($unreleased)['errors']
    === ['component_package_release_repository_invalid:larena']);

// framework mismatch: a pinned set built over another Framework release.
$drifted = $lock;
$drifted['packages']['larena']['requires_framework'] = 'ui-000000000000';
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check($drifted)['errors']
    === ['component_package_framework_mismatch:larena']);

// receipt: every pinned set carries its archive, manifest and receipt hashes.
$unreceipted = $lock;
unset($unreceipted['packages']['larena']['receipt_sha256']);
$unreceipted['packages']['larena']['sha256'] = 'not-a-hash';
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check($unreceipted)['errors'] === [
    'component_package_receipt_missing:larena:sha256',
    'component_package_receipt_missing:larena:receipt_sha256',
]);

// extra set: a second pinned set nobody declares is allowed and reported; it is still checked.
$commit = str_repeat('c', 40);
$twoSets = $lock;
$twoSets['packages']['chat'] = [
    ...$lock['packages']['larena'],
    'release' => 'chat-' . substr($commit, 0, 12),
    'commit' => $commit,
    'mount' => 'chat',
];
$extra = ComponentPackageRequirements::fromComposerManifests([$admin])->check($twoSets);
assert($extra['status'] === 'passed' && $extra['unrequested'] === ['chat']);
assert($extra['pinned'] === ['larena' => $pinnedRelease, 'chat' => 'chat-cccccccccccc']);
$twoSets['packages']['chat']['requires_framework'] = 'ui-000000000000';
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check($twoSets)['errors'] === ['component_package_framework_mismatch:chat']);
$twoSets['packages']['chat']['requires_framework'] = $lock['ui']['release'];
$twoSets['packages']['chat']['release'] = 'chat-000000000000';
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check($twoSets)['errors'] === ['component_package_release_invalid:chat']);

// Declarations are data only: unknown keys, bad names, empty lists and foreign release names are rejected.
foreach ([
    ['larena' => ['releases' => '*', 'minimum' => 'larena-3f6c86da4dd6']],
    ['larena' => ['releases' => []]],
    ['larena' => ['releases' => ['chat-3f6c86da4dd6']]],
    ['larena' => ['releases' => 'latest']],
    ['Larena' => ['releases' => '*']],
    ['ui' => ['releases' => '*']],
    ['larena' => ['releases' => '*', 'repository' => 'not a repository']],
    ['larena' => ['releases' => '*', 'release_repository' => 'not a repository']],
    // The alias and its new name together are ambiguous.
    ['larena' => ['releases' => '*', 'repository' => 'simai/larena-ui', 'release_repository' => 'simai/larena-ui']],
    ['larena' => ['releases' => '*', 'source_repositories' => 'simai/ui-admin']],
    ['larena' => ['releases' => '*', 'source_repositories' => []]],
    ['larena' => ['releases' => '*', 'source_repositories' => ['simai/ui-admin', 'not a repository']]],
    ['larena' => ['releases' => '*', 'source_repositories' => ['admin' => 'simai/ui-admin']]],
    ['larena' => ['releases' => '*', 'source_repository' => 'simai/ui-admin']],
    [['releases' => '*']],
] as $index => $sets) {
    $invalid = ComponentPackageRequirements::fromComposerManifests([componentConsumer('larena/admin', $sets)])->check($lock);
    assert($invalid['errors'] === ['component_package_declaration_invalid:larena/admin'], (string) $index);
}
// Packages outside larena/* and packages that declare nothing are not consumers.
assert(ComponentPackageRequirements::fromComposerManifests([componentConsumer('acme/admin', ['chat' => ['releases' => '*']])])->declarations() === []);

// A broken lock fails closed.
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check(['packages' => []])['errors'] === [
    'component_package_lock_invalid',
    'component_package_missing:larena:larena/admin',
]);
// A schema_version 5 lock holds its one set in a top-level larena slot.
$legacy = $lock;
$legacy['larena'] = $legacy['packages']['larena'];
unset($legacy['packages']);
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check($legacy)['status'] === 'passed');

// Package directories: every composer.json under the roots is read (the workspace this package sits in today).
$root = sys_get_temp_dir() . '/larena-ui-component-packages-' . bin2hex(random_bytes(4));
mkdir($root . '/admin', 0700, true);
mkdir($root . '/broken', 0700, true);
file_put_contents($root . '/admin/composer.json', json_encode($older, JSON_THROW_ON_ERROR));
file_put_contents($root . '/broken/composer.json', '{');
$fromDirectories = ComponentPackageRequirements::fromPackageDirectories([$root, $root . '/absent'])->check($lock);
assert($fromDirectories['errors'] === [
    'component_package_manifest_unreadable:broken',
    'component_package_release_not_accepted:larena:larena/admin',
]);

// The CLI exits 1 with the codes on a mismatch and 0 on the bundled lock.
$cli = dirname(__DIR__, 2) . '/scripts/check-component-packages.php';
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($cli) . ' --json --packages-dir=' . escapeshellarg($root) . ' 2>&1', $output, $exitCode);
$report = json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR);
assert($exitCode === 1 && in_array('component_package_release_not_accepted:larena:larena/admin', $report['errors'], true));
unlink($root . '/admin/composer.json');
unlink($root . '/broken/composer.json');
rmdir($root . '/admin');
rmdir($root . '/broken');
rmdir($root);
$output = [];
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($cli) . ' --json --lock=' . escapeshellarg($lockFile) . ' --packages-dir=' . escapeshellarg(sys_get_temp_dir() . '/larena-ui-absent') . ' 2>&1', $output, $exitCode);
assert($exitCode === 0, implode("\n", $output));

// Pages declare every pinned set in SF_BOOT_CONFIG.packages: the boot script is generated from the lock, so a
// second set appears in it without a code change, in lock order.
$runtime = FrontendRuntimeLock::bundled();
assert(ComponentPackageRequirements::undeclaredBootPackages($lock, $runtime->bootPackagesScript('/b/')) === []);
assert(ComponentPackageRequirements::undeclaredBootPackages($lock, '{chat:{base:"/b/chat/smart/"}}') === ['larena']);
assert(ComponentPackageRequirements::undeclaredBootPackages($lock, 'packages: {larena: {base: "/b/larena/smart/"}}') === []);
assert(ComponentPackageRequirements::undeclaredBootPackages($lock, '{xlarena:{base:"/b/"}}') === ['larena']);
$twoSetLock = FrontendRuntimeLock::fromArray([
    ...$lock,
    'packages' => [...$lock['packages'], 'chat' => [...$twoSets['packages']['chat'], 'release' => 'chat-cccccccccccc', 'tree' => 'smart']],
    'pair_id' => FrontendRuntimeLock::composePairId($lock['ui']['commit'], ['larena' => $lock['packages']['larena']['commit'], 'chat' => $commit]),
    'bundle_id' => FrontendRuntimeLock::composeBundleId(
        FrontendRuntimeLock::composePairId($lock['ui']['commit'], ['larena' => $lock['packages']['larena']['commit'], 'chat' => $commit]),
        $lock['framework_registry']['file_sha256'],
        $lock['publication_profile'],
    ),
]);
$twoSetBoot = $twoSetLock->bootPackagesScript('/b/');
assert($twoSetBoot === '{larena:{base:"\/b\/larena\/smart\/"},chat:{base:"\/b\/chat\/smart\/"}}');
assert(ComponentPackageRequirements::undeclaredBootPackages($twoSetLock->toArray(), $twoSetBoot) === []);
assert(ComponentPackageRequirements::undeclaredBootPackages($twoSetLock->toArray(), $runtime->bootPackagesScript('/b/')) === ['chat']);

echo "ComponentPackageRequirementsTest passed.\n";
