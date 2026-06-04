<?php

declare(strict_types=1);

namespace Larena\Ui\Contracts;

final readonly class UiResourcePackManifest
{
    /**
     * @param list<string> $smartComponentRefs
     * @param list<string> $distAssetRefs
     */
    public function __construct(
        public string $resourcePackKey,
        public array $smartComponentRefs,
        public array $distAssetRefs,
        public bool $activationOwnedByCoreAssets = true,
    ) {
    }

    public function isValid(): bool
    {
        return SmartComponentManifest::isStableKey($this->resourcePackKey)
            && $this->smartComponentRefs !== []
            && $this->activationOwnedByCoreAssets;
    }
}
