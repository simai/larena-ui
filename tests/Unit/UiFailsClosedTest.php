<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Larena\Ui\Contracts\BackendRenderResult;
use Larena\Ui\Contracts\ComponentAtlasEntry;
use Larena\Ui\Contracts\DesignPackDescriptor;
use Larena\Ui\Contracts\HydrationContract;
use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Contracts\UiAssetGraph;
use Larena\Ui\Contracts\UiAssetRequirement;
use Larena\Ui\Contracts\UiResourcePackManifest;
use Larena\Ui\Enums\HydrationStrategy;
use Larena\Ui\Enums\RenderStrategy;
use Larena\Ui\Enums\UiAssetKind;

$manifestWithoutProps = new SmartComponentManifest('ui.button', [], [], RenderStrategy::Native);
assert(!$manifestWithoutProps->isValid());

$badAsset = new UiAssetRequirement('component.button.css', UiAssetKind::Css, true, false);
assert(!$badAsset->isValid());

$hydrationMissingHash = new HydrationContract(HydrationStrategy::Adopt);
assert(!$hydrationMissingHash->isValid());

$copiedFrontendRender = new BackendRenderResult('<div></div>', RenderStrategy::Native, HydrationContract::none(), [], true);
assert(!$copiedFrontendRender->isSafe());

$invalidGraph = new UiAssetGraph([$badAsset], []);
assert(!$invalidGraph->isValid());

$designWithContent = new DesignPackDescriptor('design.default', ['color.primary' => '#000000'], true);
assert(!$designWithContent->isPortableDesignOnly());

$atlasNotGenerated = new ComponentAtlasEntry(
    new SmartComponentManifest('ui.button', ['label' => ['type' => 'string']], [], RenderStrategy::Native),
    true,
    true,
    false,
);
assert(!$atlasNotGenerated->isTrustworthy());

$resourcePackActivatingItself = new UiResourcePackManifest('ui.default', ['ui.button'], ['dist/ui.css'], false);
assert(!$resourcePackActivatingItself->isValid());
