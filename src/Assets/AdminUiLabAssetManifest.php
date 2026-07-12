<?php

declare(strict_types=1);

namespace Larena\Ui\Assets;

final class AdminUiLabAssetManifest
{
    public const CSS_KEY = 'admin.ui_lab.css';
    public const JS_KEY = 'admin.ui_lab.js';
    public const ASSET_REVISION = '20260712-ui-lab-9';

    /** @return list<array<string, mixed>> */
    public static function publicationAssets(): array
    {
        return [
            ['carrier_key'=>'larena/ui:admin-ui-lab','asset_key'=>self::CSS_KEY,'kind'=>'css','critical'=>false,'resource_path'=>'resources/css/admin-ui-lab.css','final_path_owned_by_core_assets'=>true],
            ['carrier_key'=>'larena/ui:admin-ui-lab','asset_key'=>self::JS_KEY,'kind'=>'javascript','critical'=>false,'resource_path'=>'resources/js/admin-ui-lab.js','final_path_owned_by_core_assets'=>true],
        ];
    }
}
