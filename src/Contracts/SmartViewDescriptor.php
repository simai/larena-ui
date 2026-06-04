<?php

declare(strict_types=1);

namespace Larena\Ui\Contracts;

final readonly class SmartViewDescriptor
{
    /**
     * @param array<string, mixed> $constraints
     */
    public function __construct(
        public string $viewKey,
        public string $componentKey,
        public array $constraints = [],
    ) {
    }

    public function isValid(): bool
    {
        return SmartComponentManifest::isStableKey($this->viewKey)
            && SmartComponentManifest::isStableKey($this->componentKey);
    }
}
