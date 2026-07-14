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

    /**
     * Read-only utility projection plus Larena-local, source-backed layout
     * recipes. The recipes never introduce a Framework utility: each listed
     * identifier is resolved from the immutable upstream registry first.
     *
     * @return array<string, mixed>
     */
    public function utilities(): array
    {
        $upstreamEntries = $this->upstream->entries();
        $byId = [];
        foreach ($upstreamEntries as $entry) {
            $byId[(string) $entry['id']] = $entry;
        }

        $utilities = [];
        foreach ($upstreamEntries as $entry) {
            if (($entry['kind'] ?? null) !== 'utility') {
                continue;
            }

            $utilities[] = [
                'id' => (string) $entry['id'],
                'title' => (string) ($entry['title'] ?? $entry['id']),
                'purpose' => sprintf('Framework utility family: %s.', (string) ($entry['title'] ?? $entry['id'])),
                'owner' => (string) $entry['owner'],
                'lifecycle' => (string) $entry['lifecycle'],
                'readiness' => [
                    'status' => (string) $entry['readiness']['status'],
                    'safe_to_suggest' => (bool) $entry['readiness']['safe_to_suggest'],
                    'blockers' => $entry['readiness']['blockers'],
                ],
                'parameters' => [
                    'rule_names' => $entry['runtime']['rule_names'] ?? [],
                    'asset_root' => (string) ($entry['runtime']['asset_root'] ?? ''),
                ],
                'constraints' => [
                    'requires' => $entry['requires'] ?? [],
                    'value_grammar' => 'not_enumerated_by_registry',
                ],
                'references' => array_values(array_unique(array_merge(
                    $entry['documentation_refs'] ?? [],
                    $entry['example_refs'] ?? [],
                    $entry['provenance']['source_refs'] ?? [],
                ))),
                'search_text' => strtolower(implode(' ', [
                    (string) $entry['id'],
                    (string) ($entry['title'] ?? $entry['id']),
                    (string) $entry['owner'],
                    (string) ($entry['readiness']['status'] ?? ''),
                ])),
            ];
        }
        usort($utilities, static fn (array $left, array $right): int => strcmp($left['id'], $right['id']));

        $recipes = [];
        foreach (self::LAYOUT_RECIPES as $recipe) {
            $utilityEntries = [];
            foreach ($recipe['utility_ids'] as $id) {
                $entry = $byId[$id] ?? null;
                if (($entry['kind'] ?? null) !== 'utility') {
                    continue 2;
                }
                $utilityEntries[] = $entry;
            }

            $references = [];
            foreach ($utilityEntries as $entry) {
                $references = array_merge(
                    $references,
                    $entry['documentation_refs'] ?? [],
                    $entry['example_refs'] ?? [],
                    $entry['provenance']['source_refs'] ?? [],
                );
            }
            $recipes[] = $recipe + [
                'source_refs' => array_values(array_unique($references)),
                'source' => 'larena_curated_composition_of_immutable_registry',
            ];
        }

        return [
            'schema' => 'larena.ui.framework_utility_explorer.v1',
            'compatibility_id' => $this->upstream->compatibilityId(),
            'registry_sha256' => $this->upstream->sha256(),
            'profile' => $this->upstream->profile(),
            'utilities' => $utilities,
            'recipes' => $recipes,
            'counts' => ['utilities' => count($utilities), 'recipes' => count($recipes)],
            'source' => 'immutable_upstream_registry',
            'production_ready' => false,
        ];
    }

    /** @var list<array{id: string, title: string, description: string, utility_ids: list<string>, classes: string}> */
    private const LAYOUT_RECIPES = [
        ['id' => 'layout.vertical-stack', 'title' => 'Vertical stack', 'description' => 'A compact vertical group with consistent spacing.', 'utility_ids' => ['utility.display', 'utility.flex-direction', 'utility.gap'], 'classes' => 'flex flex-col gap-2'],
        ['id' => 'layout.balanced-toolbar', 'title' => 'Balanced toolbar', 'description' => 'Actions stay aligned at opposite ends of one row.', 'utility_ids' => ['utility.display', 'utility.align-items', 'utility.justify-content', 'utility.gap'], 'classes' => 'flex items-center justify-between gap-2'],
        ['id' => 'layout.two-column-grid', 'title' => 'Two-column grid', 'description' => 'Two equal content regions with a stable gap.', 'utility_ids' => ['utility.display', 'utility.grid-template-columns', 'utility.gap'], 'classes' => 'grid grid-col-2 gap-3'],
        ['id' => 'layout.card-grid', 'title' => 'Card grid', 'description' => 'A compact three-column group for comparable items.', 'utility_ids' => ['utility.display', 'utility.grid-template-columns', 'utility.gap'], 'classes' => 'grid grid-col-3 gap-2'],
        ['id' => 'layout.centered-container', 'title' => 'Centered container', 'description' => 'A bounded reading area centered inside its parent.', 'utility_ids' => ['utility.container', 'utility.margin', 'utility.padding'], 'classes' => 'container m-inline-auto p-3'],
        ['id' => 'layout.scroll-safe-region', 'title' => 'Scroll-safe region', 'description' => 'Wide content stays within its parent without widening the page.', 'utility_ids' => ['utility.overflow', 'utility.width'], 'classes' => 'overflow-x-auto w-full'],
    ];

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
