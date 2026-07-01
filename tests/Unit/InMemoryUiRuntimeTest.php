<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Larena\Ui\Contracts\DesignPackDescriptor;
use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Contracts\UiAssetRequirement;
use Larena\Ui\Contracts\UiResourcePackManifest;
use Larena\Ui\Enums\RenderStrategy;
use Larena\Ui\Enums\UiAssetKind;
use Larena\Ui\Runtime\InMemoryUiRuntime;

$runtime = new InMemoryUiRuntime([
    'admin.menu.smart',
    'navigation.breadcrumbs.smart',
    'data.table.smart',
    'admin.shell.read_only_route.css',
]);

$manifest = new SmartComponentManifest(
    'sf.table',
    [
        'columns' => ['type' => 'list'],
        'rows' => ['type' => 'list'],
        'caption' => ['type' => 'string', 'required' => false],
    ],
    [],
    RenderStrategy::Native,
    [
        new UiAssetRequirement('data.table.smart', UiAssetKind::Module, true),
        new UiAssetRequirement('admin.shell.read_only_route.css', UiAssetKind::Css, true),
    ],
);

assert($runtime->validateManifest($manifest));

$render = $runtime->renderBackend($manifest, [
    'columns' => ['Stage', 'Status'],
    'rows' => [['layout runtime', 'ready']],
    'caption' => 'Frontend conveyor',
]);

assert($render->isSafe());
assert(str_contains($render->html, '<sf-table '));
assert(str_contains($render->html, 'data-larena-ui-runtime="larena/ui:in_memory_ui_runtime"'));
assert(str_contains($render->html, 'Frontend conveyor'));
assert($render->hydration->isValid());
assert(count($render->assetRequirements) === 2);

$graph = $runtime->collectAssetGraph($manifest);
assert($graph->isValid());
assert(count($graph->criticalRequirements()) === 2);
assert($graph->explain === [
    'ui-runtime:in-memory',
    'component:sf.table',
    'asset-requirements:2',
]);

$missingProps = false;
try {
    $runtime->renderBackend($manifest, ['columns' => ['Stage']]);
} catch (InvalidArgumentException $exception) {
    $missingProps = $exception->getMessage() === 'ui_runtime_missing_required_prop:rows';
}
assert($missingProps);

$invalidType = false;
try {
    $runtime->renderBackend($manifest, ['columns' => 'Stage', 'rows' => []]);
} catch (InvalidArgumentException $exception) {
    $invalidType = $exception->getMessage() === 'ui_runtime_invalid_prop_type:columns';
}
assert($invalidType);

$unknownAssetManifest = new SmartComponentManifest(
    'sf.table',
    ['rows' => ['type' => 'list']],
    [],
    RenderStrategy::Native,
    [new UiAssetRequirement('unknown.asset', UiAssetKind::Module, true)],
);
assert(!$runtime->validateManifest($unknownAssetManifest));

$unknownAsset = false;
try {
    $runtime->collectAssetGraph($unknownAssetManifest);
} catch (InvalidArgumentException $exception) {
    $unknownAsset = $exception->getMessage() === 'ui_runtime_invalid_or_missing_manifest';
}
assert($unknownAsset);

$missingManifest = false;
try {
    $runtime->renderBackend(
        new SmartComponentManifest('sf.table', [], [], RenderStrategy::Native),
        ['rows' => []],
    );
} catch (InvalidArgumentException $exception) {
    $missingManifest = $exception->getMessage() === 'ui_runtime_invalid_or_missing_manifest';
}
assert($missingManifest);

assert($runtime->validateDesignPack(new DesignPackDescriptor('design.admin', ['color.primary' => '#111111'])));
assert($runtime->validateResourcePack(new UiResourcePackManifest('ui.admin', ['sf.table'], ['resources/simai/smart/table/table.js'])));

echo "InMemoryUiRuntimeTest passed.\n";
