<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Larena\Ui\Frontend\ComponentPackageRequirements;
use Larena\Ui\Frontend\FrontendRuntimeLock;

$lockFile = dirname(__DIR__, 2) . '/resources/sf/runtime-lock.json';
$lock = json_decode((string) file_get_contents($lockFile), true, 512, JSON_THROW_ON_ERROR);
assert(is_array($lock));
// The pinned pair moves with every repin; the test reads it from the lock instead of naming it.
$pinnedRelease = $lock['packages']['pro']['release'];
$pinnedFramework = $lock['packages']['pro']['requires_framework'];

/** @param array<string, mixed> $sets */
function componentConsumer(string $name, array $sets): array
{
    return ['name' => $name, 'extra' => ['larena' => ['component_packages' => $sets]]];
}

$admin = componentConsumer('larena/admin', ['pro' => [
    'releases' => '*',
    'release_repository' => 'simai/ui-pro',
    'source_repositories' => ['simai/larena-ui', 'simai/ui-admin', 'simai/ui-pro'],
]]);

// ok: the bundled lock pins the set admin declares, built over the pinned Framework release, with every hash.
$ok = ComponentPackageRequirements::fromComposerManifests([$admin, ['name' => 'larena/core', 'extra' => []]])->check($lock);
assert($ok['schema'] === 'larena.ui.component_package_check' && $ok['status'] === 'passed' && $ok['errors'] === []);
assert($ok['framework_release'] === $pinnedFramework);
// Chart is pinned next to pro without a declaring package yet: it is reported, not refused.
assert($ok['pinned'] === ['pro' => $pinnedRelease, 'chart' => $lock['packages']['chart']['release']]);
assert($ok['declared'] === ['pro' => ['larena/admin']] && $ok['unrequested'] === ['chart']);

// An exact release list accepts the pinned release.
$exact = componentConsumer('larena/admin', ['pro' => ['releases' => ['pro-000000000000', $pinnedRelease]]]);
assert(ComponentPackageRequirements::fromComposerManifests([$exact])->check($lock)['errors'] === []);

// missing: a package needs a set the lock does not pin.
$chat = componentConsumer('larena/chat', ['chat' => ['releases' => '*']]);
$missing = ComponentPackageRequirements::fromComposerManifests([$admin, $chat])->check($lock);
assert($missing['status'] === 'failed' && $missing['errors'] === ['component_package_missing:chat:larena/chat']);

// not accepted: the pinned release is outside the declared list.
$older = componentConsumer('larena/admin', ['pro' => ['releases' => ['pro-000000000000']]]);
$notAccepted = ComponentPackageRequirements::fromComposerManifests([$older])->check($lock);
assert($notAccepted['errors'] === ['component_package_release_not_accepted:pro:larena/admin']);

// The declaration is read as release repository and accepted sources.
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->declarations() === ['larena/admin' => ['pro' => [
    'releases' => '*',
    'release_repository' => 'simai/ui-pro',
    'source_repositories' => ['simai/larena-ui', 'simai/ui-admin', 'simai/ui-pro'],
]]]);

// release repository: the pinned set is released from another repository than the declaration names.
$elsewhere = componentConsumer('larena/admin', ['pro' => ['releases' => '*', 'release_repository' => 'simai/elsewhere']]);
assert(ComponentPackageRequirements::fromComposerManifests([$elsewhere])->check($lock)['errors']
    === ['component_package_release_repository_mismatch:pro:larena/admin']);
// `repository`, the key used before sources and releases were split, is the release repository.
$aliased = componentConsumer('larena/admin', ['pro' => ['releases' => '*', 'repository' => 'simai/ui-pro']]);
assert(ComponentPackageRequirements::fromComposerManifests([$aliased])->declarations()['larena/admin']['pro']
    === ['releases' => '*', 'release_repository' => 'simai/ui-pro', 'source_repositories' => null]);
assert(ComponentPackageRequirements::fromComposerManifests([$aliased])->check($lock)['errors'] === []);
$aliasedElsewhere = componentConsumer('larena/admin', ['pro' => ['releases' => '*', 'repository' => 'simai/elsewhere']]);
assert(ComponentPackageRequirements::fromComposerManifests([$aliasedElsewhere])->check($lock)['errors']
    === ['component_package_release_repository_mismatch:pro:larena/admin']);

// source repositories: a set built from simai/ui-admin and released from simai/larena-ui passes; a declaration that
// accepts only larena-ui refuses it; a lock that names an unknown source or release repository fails for everyone.
$adminSourced = $lock;
$adminSourced['packages']['pro']['repository'] = 'simai/ui-admin';
$adminSourced['packages']['pro']['release_repository'] = 'simai/larena-ui';
$onLarenaUi = componentConsumer('larena/admin', ['pro' => ['releases' => '*', 'release_repository' => 'simai/larena-ui',
    'source_repositories' => ['simai/larena-ui', 'simai/ui-admin', 'simai/ui-pro']]]);
$adminSourcedCheck = ComponentPackageRequirements::fromComposerManifests([$onLarenaUi])->check($adminSourced);
assert($adminSourcedCheck['status'] === 'passed' && $adminSourcedCheck['errors'] === []);
assert(ComponentPackageRequirements::releaseRepository($adminSourced['packages']['pro']) === 'simai/larena-ui');
// The SIMAI Chart set, built from and released on simai/ui-chart next to pro, passes for a package that declares it;
// pinned from any other repository it fails.
$withChart = $lock;
$withChart['packages']['chart'] = [...$lock['packages']['pro'], 'release' => 'chart-b9d62c6d81a0',
    'commit' => 'b9d62c6d81a0028ae16487583f2732cb38ff0852', 'repository' => 'simai/ui-chart', 'package_path' => 'package'];
unset($withChart['packages']['chart']['release_repository']);
$charts = componentConsumer('larena/dashboard', ['chart' => ['releases' => '*', 'release_repository' => 'simai/ui-chart',
    'source_repositories' => ['simai/ui-chart']]]);
$chartCheck = ComponentPackageRequirements::fromComposerManifests([$admin, $charts])->check($withChart);
assert($chartCheck['errors'] === [] && $chartCheck['pinned'] === ['pro' => $pinnedRelease, 'chart' => 'chart-b9d62c6d81a0']);
$chartElsewhere = $withChart;
$chartElsewhere['packages']['chart']['repository'] = 'simai/ui-chat';
assert(ComponentPackageRequirements::fromComposerManifests([])->check($chartElsewhere)['errors']
    === ['component_package_source_repository_invalid:chart', 'component_package_release_repository_invalid:chart']);

// Without release_repository a set is released from its own repository (locks written before the split).
$unsplit = $lock['packages']['pro'];
unset($unsplit['release_repository']);
assert(ComponentPackageRequirements::releaseRepository($unsplit) === $unsplit['repository']);
$larenaUiOnly = componentConsumer('larena/admin', ['pro' => ['releases' => '*', 'source_repositories' => ['simai/larena-ui']]]);
assert(ComponentPackageRequirements::fromComposerManifests([$larenaUiOnly])->check($adminSourced)['errors']
    === ['component_package_source_repository_not_accepted:pro:larena/admin']);
$larenaUiSourced = $lock;
$larenaUiSourced['packages']['pro']['repository'] = 'simai/larena-ui';
unset($larenaUiSourced['packages']['pro']['release_repository']);
assert(ComponentPackageRequirements::fromComposerManifests([$larenaUiOnly])->check($larenaUiSourced)['errors'] === []);
$unknownSource = $lock;
$unknownSource['packages']['pro']['repository'] = 'simai/elsewhere';
$unknownSource['packages']['pro']['release_repository'] = 'simai/ui-pro';
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check($unknownSource)['errors'] === [
    'component_package_source_repository_invalid:pro',
    'component_package_source_repository_not_accepted:pro:larena/admin',
]);
// Released from where it is built (no release_repository) works only for a release repository.
$unreleased = $adminSourced;
unset($unreleased['packages']['pro']['release_repository']);
assert(ComponentPackageRequirements::fromComposerManifests([])->check($unreleased)['errors']
    === ['component_package_release_repository_invalid:pro']);

// framework mismatch: a pinned set built over another Framework release.
$drifted = $lock;
$drifted['packages']['pro']['requires_framework'] = 'ui-000000000000';
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check($drifted)['errors']
    === ['component_package_framework_mismatch:pro']);

// pair receipt (Framework decision 2026-11-07): a set built over another pair passes with a pair receipt of the
// same release for the pinned pair, and only so.
$receipted = $drifted;
$receipted['packages']['pro']['pair_receipt'] = ['pair' => $lock['ui']['release'], 'sha256' => str_repeat('a', 64), 'asset' => 'pair-receipt-' . $lock['ui']['release'] . '.json'];
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check($receipted)['errors'] === []);
assert(ComponentPackageRequirements::pairError($receipted['packages']['pro'], $lock['ui']['release']) === null);
$thirdPair = $receipted;
$thirdPair['packages']['pro']['pair_receipt'] = ['pair' => 'ui-111111111111', 'sha256' => str_repeat('a', 64), 'asset' => 'pair-receipt-ui-111111111111.json'];
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check($thirdPair)['errors'] === ['component_package_framework_mismatch:pro']);
$ownPair = $receipted;
$ownPair['packages']['pro']['pair_receipt'] = ['pair' => 'ui-000000000000', 'sha256' => str_repeat('a', 64), 'asset' => 'pair-receipt-ui-000000000000.json'];
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check($ownPair)['errors'] === ['component_package_pair_receipt_invalid:pro']);
foreach ([['sha256' => 'abc'], ['asset' => 'receipt.json'], ['extra' => 1]] as $broken) {
    $bad = $receipted;
    $bad['packages']['pro']['pair_receipt'] = $broken + $receipted['packages']['pro']['pair_receipt'];
    assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check($bad)['errors'] === ['component_package_pair_receipt_invalid:pro']);
}
$bad = $receipted;
$bad['packages']['pro']['pair_receipt']['extra'] = 1;
assert(ComponentPackageRequirements::pairError($bad['packages']['pro'], $lock['ui']['release']) === 'pair_receipt_invalid');

// pair range (SIMAI Framework decision 2026-11-09): a set built over another pair passes with a pair range of the same
// release checked over the pinned pair, and only so; an entry carries a pair receipt or a pair range, not both.
$ranged = $drifted;
$ranged['packages']['pro']['pair_range'] = ['sha256' => str_repeat('a', 64), 'asset' => 'pair-range.json', 'interface_sha256' => str_repeat('c', 64), 'checked_over' => $lock['ui']['release']];
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check($ranged)['errors'] === []);
assert(ComponentPackageRequirements::pairError($ranged['packages']['pro'], $lock['ui']['release']) === null);
$otherPair = $ranged;
$otherPair['packages']['pro']['pair_range']['checked_over'] = 'ui-111111111111';
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check($otherPair)['errors'] === ['component_package_framework_mismatch:pro']);
$both = $ranged;
$both['packages']['pro']['pair_receipt'] = $receipted['packages']['pro']['pair_receipt'];
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check($both)['errors'] === ['component_package_pair_range_invalid:pro']);
foreach ([['sha256' => 'abc'], ['interface_sha256' => null], ['asset' => 'pair-range-ui-111111111111.json'], ['checked_over' => 'ui-1'], ['extra' => 1]] as $broken) {
    $bad = $ranged;
    $bad['packages']['pro']['pair_range'] = $broken + $ranged['packages']['pro']['pair_range'];
    assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check($bad)['errors'] === ['component_package_pair_range_invalid:pro']);
}
$bad = $ranged;
unset($bad['packages']['pro']['pair_range']['checked_over']);
assert(ComponentPackageRequirements::pairError($bad['packages']['pro'], $lock['ui']['release']) === 'pair_range_invalid');
$bad['packages']['pro']['pair_range'] = 'pair-range.json';
assert(ComponentPackageRequirements::pairError($bad['packages']['pro'], $lock['ui']['release']) === 'pair_range_invalid');

// receipt: every pinned set carries its archive, manifest and receipt hashes.
$unreceipted = $lock;
unset($unreceipted['packages']['pro']['receipt_sha256']);
$unreceipted['packages']['pro']['sha256'] = 'not-a-hash';
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check($unreceipted)['errors'] === [
    'component_package_receipt_missing:pro:sha256',
    'component_package_receipt_missing:pro:receipt_sha256',
]);

// extra set: a second pinned set nobody declares is allowed and reported; it is still checked.
$commit = str_repeat('c', 40);
$twoSets = $lock;
$twoSets['packages']['chat'] = [
    ...$lock['packages']['pro'],
    'release' => 'chat-' . substr($commit, 0, 12),
    'commit' => $commit,
    'mount' => 'chat',
];
$extra = ComponentPackageRequirements::fromComposerManifests([$admin])->check($twoSets);
assert($extra['status'] === 'passed' && $extra['unrequested'] === ['chart', 'chat']);
assert($extra['pinned'] === ['pro' => $pinnedRelease, 'chart' => $lock['packages']['chart']['release'], 'chat' => 'chat-cccccccccccc']);
$twoSets['packages']['chat']['requires_framework'] = 'ui-000000000000';
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check($twoSets)['errors'] === ['component_package_framework_mismatch:chat']);
$twoSets['packages']['chat']['requires_framework'] = $lock['ui']['release'];
$twoSets['packages']['chat']['release'] = 'chat-000000000000';
assert(ComponentPackageRequirements::fromComposerManifests([$admin])->check($twoSets)['errors'] === ['component_package_release_invalid:chat']);

// Declarations are data only: unknown keys, bad names, empty lists and foreign release names are rejected.
foreach ([
    ['pro' => ['releases' => '*', 'minimum' => 'larena-3f6c86da4dd6']],
    ['pro' => ['releases' => []]],
    ['pro' => ['releases' => ['chat-3f6c86da4dd6']]],
    ['pro' => ['releases' => 'latest']],
    ['Larena' => ['releases' => '*']],
    ['ui' => ['releases' => '*']],
    ['pro' => ['releases' => '*', 'repository' => 'not a repository']],
    ['pro' => ['releases' => '*', 'release_repository' => 'not a repository']],
    // The alias and its new name together are ambiguous.
    ['pro' => ['releases' => '*', 'repository' => 'simai/larena-ui', 'release_repository' => 'simai/larena-ui']],
    ['pro' => ['releases' => '*', 'source_repositories' => 'simai/ui-admin']],
    ['pro' => ['releases' => '*', 'source_repositories' => []]],
    ['pro' => ['releases' => '*', 'source_repositories' => ['simai/ui-admin', 'not a repository']]],
    ['pro' => ['releases' => '*', 'source_repositories' => ['admin' => 'simai/ui-admin']]],
    ['pro' => ['releases' => '*', 'source_repository' => 'simai/ui-admin']],
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
    'component_package_missing:pro:larena/admin',
]);
// A schema_version 5 lock holds its one set in a top-level larena slot.
$legacy = $lock;
// Its set is named larena, as the component package was before Framework renamed it pro.
$legacy['larena'] = [...$legacy['packages']['pro'], 'release' => 'larena-' . substr($legacy['packages']['pro']['commit'], 0, 12)];
unset($legacy['packages']);
$legacyConsumer = componentConsumer('larena/admin', ['larena' => ['releases' => '*']]);
assert(ComponentPackageRequirements::fromComposerManifests([$legacyConsumer])->check($legacy)['status'] === 'passed');

// Package directories: every composer.json under the roots is read (the workspace this package sits in today).
$root = sys_get_temp_dir() . '/larena-ui-component-packages-' . bin2hex(random_bytes(4));
mkdir($root . '/admin', 0700, true);
mkdir($root . '/broken', 0700, true);
file_put_contents($root . '/admin/composer.json', json_encode($older, JSON_THROW_ON_ERROR));
file_put_contents($root . '/broken/composer.json', '{');
$fromDirectories = ComponentPackageRequirements::fromPackageDirectories([$root, $root . '/absent'])->check($lock);
assert($fromDirectories['errors'] === [
    'component_package_manifest_unreadable:broken',
    'component_package_release_not_accepted:pro:larena/admin',
]);

// The CLI exits 1 with the codes on a mismatch and 0 on the bundled lock.
$cli = dirname(__DIR__, 2) . '/scripts/check-component-packages.php';
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($cli) . ' --json --packages-dir=' . escapeshellarg($root) . ' 2>&1', $output, $exitCode);
$report = json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR);
assert($exitCode === 1 && in_array('component_package_release_not_accepted:pro:larena/admin', $report['errors'], true));
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
// The cases below read the lock's pro alone (Chart is pinned next to it in the bundled lock).
$proLock = $lock;
unset($proLock['packages']['chart']);
$proLock['components'] = array_filter($proLock['components'], static fn (array $component): bool => $component['package'] !== 'chart');
assert(ComponentPackageRequirements::undeclaredBootPackages($proLock, '{chat:{base:"/b/chat/smart/"}}') === ['pro']);
assert(ComponentPackageRequirements::undeclaredBootPackages($proLock, 'packages: {pro: {base: "/b/pro/smart/"}}') === []);
assert(ComponentPackageRequirements::undeclaredBootPackages($proLock, '{xpro:{base:"/b/"}}') === ['pro']);
$twoSetLock = FrontendRuntimeLock::fromArray([
    ...$proLock,
    'packages' => [...$proLock['packages'], 'chat' => [...$twoSets['packages']['chat'], 'release' => 'chat-cccccccccccc', 'tree' => 'smart']],
    'pair_id' => FrontendRuntimeLock::composePairId($proLock['ui']['commit'], ['pro' => $proLock['packages']['pro']['commit'], 'chat' => $commit]),
    'bundle_id' => FrontendRuntimeLock::composeBundleId(
        FrontendRuntimeLock::composePairId($proLock['ui']['commit'], ['pro' => $proLock['packages']['pro']['commit'], 'chat' => $commit]),
        $proLock['framework_registry']['file_sha256'],
        $proLock['publication_profile'],
    ),
]);
$twoSetBoot = $twoSetLock->bootPackagesScript('/b/');
assert($twoSetBoot === '{pro:{base:"\/b\/pro\/smart\/"},chat:{base:"\/b\/chat\/smart\/"}}');
assert(ComponentPackageRequirements::undeclaredBootPackages($twoSetLock->toArray(), $twoSetBoot) === []);
assert(ComponentPackageRequirements::undeclaredBootPackages($twoSetLock->toArray(), $runtime->bootPackagesScript('/b/')) === ['chat']);

echo "ComponentPackageRequirementsTest passed.\n";
