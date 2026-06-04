<?php

declare(strict_types=1);

namespace Larena\Ui\Contracts;

use Larena\Ui\Enums\RenderStrategy;

final readonly class SmartComponentManifest
{
    /**
     * @param array<string, mixed> $propsSchema
     * @param list<string> $slotKeys
     * @param list<UiAssetRequirement> $assetRequirements
     */
    public function __construct(
        public string $componentKey,
        public array $propsSchema,
        public array $slotKeys,
        public RenderStrategy $renderStrategy,
        public array $assetRequirements = [],
    ) {
    }

    public static function isStableKey(string $key): bool
    {
        return preg_match('/^[a-z][a-z0-9_]*(\\.[a-z][a-z0-9_]*)*$/', $key) === 1;
    }

    public function isValid(): bool
    {
        foreach ($this->assetRequirements as $assetRequirement) {
            if (!$assetRequirement->isValid()) {
                return false;
            }
        }

        return self::isStableKey($this->componentKey)
            && $this->propsSchema !== [];
    }
}
