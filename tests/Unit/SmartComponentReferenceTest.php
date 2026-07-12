<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Developer\SmartInvocationExampleBuilder;
use Larena\Ui\Contracts\UiAssetRequirement;
use Larena\Ui\Frontend\SourceBackedComponentRegistry;
use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Reference\SmartComponentReference;
use Larena\Ui\Registry\SmartRegistry;
use Larena\Ui\Runtime\SmartManager;
use Larena\Ui\Smart;

$activation = [
    'schema' => 'larena.core_assets.activation_contract.v1',
    'status' => 'ready',
    'activation_owner' => 'larena/core:core.assets',
    'activation_mode' => 'verified_immutable_bundle',
    'physical_publication_ready' => true,
    'writes_database' => false,
    'copies_to_root' => false,
    'uses_hardcoded_cdn' => false,
    'asset_count' => 1,
    'runtime_pair' => FrontendRuntimeLock::bundled()->pairId(),
    'renderable_tags' => ['<script src="/larena/assets/sf/core.js"></script>'],
];

$registry = SmartRegistry::withDefaults();
$manifest = $registry->manifest('ui.button');
assert($manifest->isCanonical());
assert($manifest->rendererId === 'ui.sf_element');
assert($manifest->frontendTag === 'sf-button');
assert($manifest->atlas['title'] === 'Button');
assert($manifest->atlas['i18n']['en']['title'] === 'Button');
assert($manifest->atlas['i18n']['ru']['title'] === 'Кнопка');
assert($manifest->atlas['description'] === 'Starts an action such as save, create, or navigate.');
assert($manifest->provenance['runtime_lock'] === 'resources/sf/runtime-lock.json');
assert(array_keys($manifest->atlas['i18n']) === ['en', 'ru']);
assert(array_keys($manifest->atlas['i18n']['en']['controls']) === array_keys($manifest->atlas['i18n']['ru']['controls']));
$manifestAssetKeys = array_map(static fn (UiAssetRequirement $asset): string => $asset->assetKey, $manifest->assetRequirements);
$runtimeAssetKeys = array_map(static fn (UiAssetRequirement $asset): string => $asset->assetKey, Smart::assetGraph('sf-button')->requirements);
assert($manifestAssetKeys === $runtimeAssetKeys);
foreach (['click', 'focusin', 'focusout', 'mouseenter', 'mouseleave', 'sf-connected', 'sf-before-render', 'sf-after-render', 'sf-updated', 'sf-props-change', 'sf-disconnected'] as $event) {
    assert(isset($manifest->eventSchema[$event]));
}

$runtime = SourceBackedComponentRegistry::bundled()->get('sf-button');
$runtimeAttributes = $runtime['attributes'] ?? [];
foreach (array_keys($manifest->propsSchema['properties']) as $prop) {
    assert(in_array($prop, $runtimeAttributes, true));
}

$reference = new SmartComponentReference($manifest);
$default = $reference->resolve();
assert($default['controls']['variant'] === 'primary');
assert($default['props']['type'] === 'default');
assert($default['props']['scheme'] === 'primary');

$configured = $reference->resolve([
    'text' => 'Publish',
    'variant' => 'secondary',
    'size' => '2',
    'radius' => 'rounded',
    'loading' => '1',
], true);
assert($configured['props']['type'] === 'tonal');
assert($configured['props']['scheme'] === 'secondary');
assert($configured['props']['loading'] === true);
assert($configured['props']['disabled'] === true);
assert($configured['props']['aria-label'] === 'Publish');
assert($configured['controls']['disabled'] === true);

$laravelNormalized = $reference->resolve([
    'text' => 'Save',
    'variant' => 'secondary',
    'size' => '1',
    'radius' => null,
], true);
assert($laravelNormalized['controls']['radius'] === '');
assert(!array_key_exists('radius', $laravelNormalized['props']));
assert($laravelNormalized['props']['scheme'] === 'secondary');

$artifact = (new SmartManager($registry))->render('ui.button', $configured['props'], $activation);
assert($artifact->isRenderable());
assert(str_contains($artifact->html(), '<sf-button'));
assert(str_contains($artifact->html(), 'text="Publish"'));
assert(str_contains($artifact->html(), 'loading'));
assert(str_contains($artifact->html(), 'disabled'));

$invalidCombinationRejected = false;
try {
    (new SmartManager($registry))->render('ui.button', array_replace($default['props'], [
        'type' => 'default', 'scheme' => 'secondary',
    ]), $activation);
} catch (InvalidArgumentException $exception) {
    $invalidCombinationRejected = $exception->getMessage() === 'ui_smart_constraint_combination_invalid:ui.button:type,scheme';
}
assert($invalidCombinationRejected);

$unknownControlRejected = false;
try {
    $reference->resolve(['onclick' => 'alert(1)'], true);
} catch (InvalidArgumentException $exception) {
    $unknownControlRejected = $exception->getMessage() === 'ui_smart_reference_control_unknown:ui.button:onclick';
}
assert($unknownControlRejected);

$emptyTextRejected = false;
try {
    $reference->resolve(['text' => ''], true);
} catch (InvalidArgumentException $exception) {
    $emptyTextRejected = $exception->getMessage() === 'ui_smart_reference_text_too_short:ui.button:text';
}
assert($emptyTextRejected);

$whitespaceTextRejected = false;
try {
    $reference->resolve(['text' => '   '], true);
} catch (InvalidArgumentException $exception) {
    $whitespaceTextRejected = $exception->getMessage() === 'ui_smart_reference_text_too_short:ui.button:text';
}
assert($whitespaceTextRejected);

$directBlankNameRejected = false;
try {
    (new SmartManager($registry))->render('ui.button', array_replace($default['props'], [
        'text' => '', 'aria-label' => '',
    ]), $activation);
} catch (InvalidArgumentException $exception) {
    $directBlankNameRejected = $exception->getMessage() === 'ui_smart_prop_min_length_invalid:ui.button:text';
}
assert($directBlankNameRejected);

$directWhitespaceNameRejected = false;
try {
    (new SmartManager($registry))->render('ui.button', array_replace($default['props'], [
        'text' => '   ', 'aria-label' => '   ',
    ]), $activation);
} catch (InvalidArgumentException $exception) {
    $directWhitespaceNameRejected = $exception->getMessage() === 'ui_smart_prop_pattern_invalid:ui.button:text';
}
assert($directWhitespaceNameRejected);

$builder = new SmartInvocationExampleBuilder();
$php = $builder->php($manifest, array_replace($default['props'], ['text' => "O'Reilly"]));
assert(str_contains($php, 'use Larena\\Ui\\Facades\\Smart;'));
assert(str_contains($php, 'Smart::render('));
assert(str_contains($php, "'ui.button'"));
assert(str_contains($php, "'O\\'Reilly'"));
assert(!str_contains($php, 'eval'));
$frontend = $builder->frontend($manifest, array_replace($default['props'], ['text' => 'Save & continue']));
assert(str_contains($frontend, '<sf-button'));
assert(str_contains($frontend, 'Save &amp; continue'));

$objectRejected = false;
try {
    $builder->php($manifest, array_replace($default['props'], ['text' => new stdClass()]));
} catch (InvalidArgumentException $exception) {
    $objectRejected = $exception->getMessage() === 'ui_smart_prop_type_invalid:ui.button:text:string';
}
assert($objectRejected);

$builderUnknownPropRejected = false;
try {
    $builder->frontend($manifest, $default['props'] + ['onclick' => 'alert(1)']);
} catch (InvalidArgumentException $exception) {
    $builderUnknownPropRejected = $exception->getMessage() === 'ui_smart_prop_unknown:ui.button:onclick';
}
assert($builderUnknownPropRejected);

$missingRuntimePairRejected = false;
$incompleteActivation = $activation;
unset($incompleteActivation['runtime_pair']);
try {
    (new SmartManager($registry))->render('ui.button', $default['props'], $incompleteActivation);
} catch (InvalidArgumentException $exception) {
    $missingRuntimePairRejected = $exception->getMessage() === 'ui_smart_asset_runtime_pair_missing:ui.button';
}
assert($missingRuntimePairRejected);

echo "SmartComponentReferenceTest passed.\n";
