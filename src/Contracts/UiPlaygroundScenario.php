<?php

declare(strict_types=1);

namespace Larena\Ui\Contracts;

final readonly class UiPlaygroundScenario
{
    public function __construct(
        public string $scenarioKey,
        public SmartComponentManifest $manifest,
        public bool $fromCanonicalManifest = true,
        public bool $browserSmokeRequired = false,
    ) {
    }

    public function isValid(): bool
    {
        return SmartComponentManifest::isStableKey($this->scenarioKey)
            && $this->manifest->isValid()
            && $this->fromCanonicalManifest;
    }
}
