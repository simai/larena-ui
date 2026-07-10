<?php

declare(strict_types=1);

namespace Larena\Ui\Components;

use Larena\Ui\Assets\AdminDataviewAssetManifest;
use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Contracts\UiAssetRequirement;
use Larena\Ui\Enums\RenderStrategy;
use Larena\Ui\Enums\UiAssetKind;

final class AdminComponentCatalog
{
    /** @return array<string, SmartComponentManifest> */
    public function manifests(): array
    {
        $asset = [new UiAssetRequirement(AdminDataviewAssetManifest::ASSET_KEY, UiAssetKind::Css, true)];
        $schema = ['type' => 'object'];

        return [
            'button' => new SmartComponentManifest('admin.button', $schema, [], RenderStrategy::Native, $asset),
            'badge' => new SmartComponentManifest('admin.badge', $schema, [], RenderStrategy::Native, $asset),
            'toolbar' => new SmartComponentManifest('admin.toolbar', $schema, ['actions'], RenderStrategy::Native, $asset),
            'empty_state' => new SmartComponentManifest('admin.empty_state', $schema, ['actions'], RenderStrategy::Native, $asset),
            'pagination' => new SmartComponentManifest('admin.pagination', $schema, [], RenderStrategy::Native, $asset),
            'dataview' => new SmartComponentManifest('admin.dataview', $schema, ['toolbar', 'table', 'empty_state', 'pagination'], RenderStrategy::Native, $asset),
        ];
    }
}
