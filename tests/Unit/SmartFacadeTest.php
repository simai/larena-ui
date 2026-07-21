<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Facades\Smart;
use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Registry\SmartRegistry;
use Larena\Ui\Runtime\SmartManager;

$activation = [
    'schema' => 'larena.core_assets.activation_contract.v1',
    'status' => 'ready',
    'activation_owner' => 'larena/core:core.assets',
    'activation_mode' => 'verified_immutable_bundle',
    'physical_publication_ready' => true,
    'writes_database' => false,
    'copies_to_root' => false,
    'uses_hardcoded_cdn' => false,
    'runtime_pair' => FrontendRuntimeLock::bundled()->pairId(),
    'renderable_tags' => ['<script src="/larena/assets/sf/core.js"></script>'],
];

Smart::forgetResolver();
$missingManagerRejected = false;
try {
    Smart::render('ui.button');
} catch (InvalidArgumentException $exception) {
    $missingManagerRejected = $exception->getMessage() === 'ui_smart_facade_manager_not_configured';
}
assert($missingManagerRejected);

Smart::resolveUsing(static fn (): string => 'not-a-manager');
$invalidManagerRejected = false;
try {
    Smart::render('ui.button');
} catch (InvalidArgumentException $exception) {
    $invalidManagerRejected = $exception->getMessage() === 'ui_smart_facade_manager_invalid';
}
assert($invalidManagerRejected);

$registry = SmartRegistry::withDefaults();
$manager = new SmartManager($registry);
Smart::resolveUsing(static fn (): SmartManager => $manager);
$artifact = Smart::render('ui.button', [
    'text' => 'Facade',
    'size' => '1',
    'type' => 'default',
    'scheme' => 'primary',
    'loading' => false,
    'disabled' => false,
    'native-type' => 'button',
    'aria-label' => 'Facade',
], $activation);
assert($artifact->isRenderable());
assert(str_contains($artifact->html(), 'text="Facade"'));

$modalArtifact = Smart::render('ui.modal', [
    'modal-id' => 'profile-modal',
    'title' => 'Profile',
    'text' => 'Profile details',
    'open' => false,
    'overlay' => true,
    'display' => 'modal',
    'aria-label' => 'Profile details',
    'id' => 'profile-modal',
], $activation, []);
assert($modalArtifact->isRenderable());
assert(str_contains($modalArtifact->html(), '<sf-modal'));
assert(str_contains($modalArtifact->html(), 'aria-label="Profile details"'));
assert(Smart::manager() === $manager);
Smart::forgetResolver();

echo "SmartFacadeTest passed.\n";
