<?php

declare(strict_types=1);

namespace Larena\Ui\Assets;

final class AdminSmartEventBridgeAssetManifest
{
    public const ASSET_KEY = 'ui.admin.smart_event_bridge.js';
    public const ASSET_REVISION = '20260921-composition-context-b0ddd75f41da';

    /** @return array<string, mixed> */
    public static function publicationAsset(): array
    {
        return [
            'carrier_key' => 'larena/ui:admin.smart-event-bridge',
            'asset_key' => self::ASSET_KEY,
            'kind' => 'javascript',
            'critical' => false,
            'resource_path' => 'resources/js/admin-smart-event-bridge.js',
            'final_path_owned_by_core_assets' => true,
        ];
    }
}
