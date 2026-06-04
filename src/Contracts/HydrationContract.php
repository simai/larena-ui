<?php

declare(strict_types=1);

namespace Larena\Ui\Contracts;

use Larena\Ui\Enums\HydrationStrategy;

final readonly class HydrationContract
{
    public function __construct(
        public HydrationStrategy $strategy,
        public ?string $propsHash = null,
        public string $domStrategy = 'stable',
        public bool $frontendRuntimeAvailable = false,
    ) {
    }

    public static function none(): self
    {
        return new self(HydrationStrategy::None);
    }

    public function isValid(): bool
    {
        return (!$this->strategy->requiresPropsHash() || ($this->propsHash !== null && trim($this->propsHash) !== ''))
            && trim($this->domStrategy) !== '';
    }

    public function gracefullyDegrades(): bool
    {
        return $this->strategy === HydrationStrategy::None || $this->frontendRuntimeAvailable;
    }
}
