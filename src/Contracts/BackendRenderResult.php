<?php

declare(strict_types=1);

namespace Larena\Ui\Contracts;

use Larena\Ui\Enums\RenderStrategy;

final readonly class BackendRenderResult
{
    /**
     * @param list<UiAssetRequirement> $assetRequirements
     */
    public function __construct(
        public string $html,
        public RenderStrategy $strategy,
        public HydrationContract $hydration,
        public array $assetRequirements = [],
        public bool $copiedFrontendSource = false,
    ) {
    }

    public function isSafe(): bool
    {
        return trim($this->html) !== ''
            && !$this->copiedFrontendSource
            && (!$this->strategy->requiresFrontendRuntime() || $this->hydration->strategy->requiresPropsHash())
            && ($this->strategy->requiresFrontendRuntime() || $this->hydration->strategy === \Larena\Ui\Enums\HydrationStrategy::None);
    }
}
