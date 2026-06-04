<?php

declare(strict_types=1);

namespace Larena\Ui\Contracts;

final readonly class UiAssetGraph
{
    /**
     * @param list<UiAssetRequirement> $requirements
     * @param list<string> $explain
     */
    public function __construct(
        public array $requirements,
        public array $explain,
    ) {
    }

    public function isValid(): bool
    {
        if ($this->requirements === [] || $this->explain === []) {
            return false;
        }

        foreach ($this->requirements as $requirement) {
            if (!$requirement->isValid()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<UiAssetRequirement>
     */
    public function criticalRequirements(): array
    {
        return array_values(array_filter($this->requirements, static fn (UiAssetRequirement $asset): bool => $asset->critical));
    }
}
