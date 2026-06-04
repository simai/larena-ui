<?php

declare(strict_types=1);

namespace Larena\Ui\Contracts;

final readonly class DesignPackDescriptor
{
    /**
     * @param array<string, mixed> $tokens
     */
    public function __construct(
        public string $designPackKey,
        public array $tokens,
        public bool $containsContentRecords = false,
        public bool $containsPlatformSpecificIds = false,
        public bool $containsRuntimeCache = false,
    ) {
    }

    public function isPortableDesignOnly(): bool
    {
        return SmartComponentManifest::isStableKey($this->designPackKey)
            && $this->tokens !== []
            && !$this->containsContentRecords
            && !$this->containsPlatformSpecificIds
            && !$this->containsRuntimeCache;
    }
}
