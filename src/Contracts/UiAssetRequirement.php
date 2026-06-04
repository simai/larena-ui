<?php

declare(strict_types=1);

namespace Larena\Ui\Contracts;

use Larena\Ui\Enums\UiAssetKind;

final readonly class UiAssetRequirement
{
    public function __construct(
        public string $assetKey,
        public UiAssetKind $kind,
        public bool $critical = false,
        public bool $finalPathOwnedByCoreAssets = true,
    ) {
    }

    public function isValid(): bool
    {
        return SmartComponentManifest::isStableKey($this->assetKey)
            && $this->finalPathOwnedByCoreAssets;
    }
}
