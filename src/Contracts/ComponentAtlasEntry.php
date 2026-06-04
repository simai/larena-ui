<?php

declare(strict_types=1);

namespace Larena\Ui\Contracts;

final readonly class ComponentAtlasEntry
{
    public function __construct(
        public SmartComponentManifest $manifest,
        public bool $backendCoverage,
        public bool $frontendCoverage,
        public bool $generatedFromManifest = true,
    ) {
    }

    public function isTrustworthy(): bool
    {
        return $this->manifest->isValid()
            && $this->backendCoverage
            && $this->generatedFromManifest;
    }
}
