<?php

declare(strict_types=1);

namespace Larena\Ui\Developer;

use Larena\Ui\Frontend\FrameworkContractRegistry;
use Larena\Ui\Registry\FrameworkAdapterRegistry;

final readonly class FrameworkCatalogProjection
{
    public function __construct(
        private FrameworkContractRegistry $upstream,
        private FrameworkAdapterRegistry $adapters,
    ) {
    }

    /** @return array<string, mixed> */
    public function plan(string $adapterId): array
    {
        return $this->adapters->plan($adapterId);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $plans = [];
        foreach ($this->adapters->adapters() as $adapter) {
            $plans[] = $this->adapters->plan((string) $adapter['id']);
        }

        return [
            'schema' => 'larena.ui.framework_catalog_projection.v1',
            'upstream' => $this->upstream->toArray(),
            'registry_sha256' => $this->upstream->sha256(),
            'larena_adapters' => $this->adapters->adapters(),
            'resolved_plans' => $plans,
            'upstream_merged_into_adapters' => false,
            'production_ready' => false,
        ];
    }
}
