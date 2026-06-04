<?php

declare(strict_types=1);

use Larena\Ui\Contracts\BackendRenderResult;
use Larena\Ui\Contracts\ComponentAtlasEntry;
use Larena\Ui\Contracts\DesignPackDescriptor;
use Larena\Ui\Contracts\HydrationContract;
use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Contracts\SmartViewDescriptor;
use Larena\Ui\Contracts\UiAssetGraph;
use Larena\Ui\Contracts\UiAssetRequirement;
use Larena\Ui\Contracts\UiPlaygroundScenario;
use Larena\Ui\Contracts\UiResourcePackManifest;
use Larena\Ui\Enums\RenderStrategy;
use Larena\Ui\Enums\UiAssetKind;

$asset = new UiAssetRequirement('component.button.css', UiAssetKind::Css, true);
assert($asset->isValid());

$manifest = new SmartComponentManifest('ui.button', ['label' => ['type' => 'string']], ['default'], RenderStrategy::Native, [$asset]);
assert($manifest->isValid());

$view = new SmartViewDescriptor('ui.button.primary', 'ui.button');
assert($view->isValid());

$render = new BackendRenderResult('<button>Save</button>', RenderStrategy::Native, HydrationContract::none(), [$asset]);
assert($render->isSafe());

$graph = new UiAssetGraph([$asset], ['component:ui.button']);
assert($graph->isValid());
assert(count($graph->criticalRequirements()) === 1);

$designPack = new DesignPackDescriptor('design.default', ['color.primary' => '#000000']);
assert($designPack->isPortableDesignOnly());

$atlas = new ComponentAtlasEntry($manifest, true, false);
assert($atlas->isTrustworthy());

$scenario = new UiPlaygroundScenario('ui.button.default', $manifest);
assert($scenario->isValid());

$resourcePack = new UiResourcePackManifest('ui.default', ['ui.button'], ['dist/ui.css']);
assert($resourcePack->isValid());
