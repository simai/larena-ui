<?php

declare(strict_types=1);

namespace Larena\Ui\Assets;

final class AdminDataviewAssetManifest
{
    public const ASSET_KEY = 'ui.admin.dataview.css';
    public const ASSET_REVISION = '20260711-1';

    /** @return array<string, mixed> */
    public static function publicationAsset(): array
    {
        return [
            'carrier_key' => 'larena/ui:admin.dataview',
            'asset_key' => self::ASSET_KEY,
            'kind' => 'css',
            'critical' => true,
            'resource_path' => 'resources/css/admin-dataview.css',
            'final_path_owned_by_core_assets' => true,
        ];
    }
}
