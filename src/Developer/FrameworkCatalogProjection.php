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

    /**
     * Read-only developer projection of the pinned upstream contract.
     *
     * This deliberately exposes no Larena adapter fields: the Explorer needs
     * framework discovery, while adapters remain application-specific.
     *
     * @return array<string, mixed>
     */
    public function explorer(): array
    {
        $entries = $this->upstream->entries();
        usort($entries, static fn (array $left, array $right): int => strcmp((string) $left['id'], (string) $right['id']));

        $counts = array_fill_keys(['utility', 'component', 'smart-component', 'recipe'], 0);
        $records = [];
        foreach ($entries as $entry) {
            $kind = (string) $entry['kind'];
            $counts[$kind]++;
            $records[] = [
                'id' => (string) $entry['id'],
                'kind' => $kind,
                'title' => (string) ($entry['title'] ?? $entry['id']),
                'owner' => (string) $entry['owner'],
                'lifecycle' => (string) $entry['lifecycle'],
                'readiness' => [
                    'status' => (string) $entry['readiness']['status'],
                    'safe_to_suggest' => (bool) $entry['readiness']['safe_to_suggest'],
                    'blockers' => $entry['readiness']['blockers'],
                ],
                'requires' => $entry['requires'],
                'references' => array_merge($entry['documentation_refs'], $entry['example_refs']),
                'runtime' => $entry['runtime'],
                'search_text' => strtolower(implode(' ', [
                    (string) $entry['id'],
                    (string) ($entry['title'] ?? $entry['id']),
                    (string) $entry['owner'],
                    $kind,
                ])),
            ];
        }

        return [
            'schema' => 'larena.ui.framework_catalog_explorer.v1',
            'compatibility_id' => $this->upstream->compatibilityId(),
            'registry_sha256' => $this->upstream->sha256(),
            'profile' => $this->upstream->profile(),
            'counts' => $counts + ['total' => count($records)],
            'entries' => $records,
            'source' => 'immutable_upstream_registry',
            'production_ready' => false,
        ];
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
