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
        $demonstrations = $this->utilityDemonstrations($byId);

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
                'demonstration' => $demonstrations[(string) $entry['id']] ?? null,
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
            'counts' => ['utilities' => count($utilities), 'recipes' => count($recipes), 'demonstrations' => count($demonstrations)],
            'source' => 'immutable_upstream_registry',
            'production_ready' => false,
        ];
    }

    /** @return array{utility: array<string, mixed>, demonstration: array<string, mixed>}|null */
    public function demonstration(string $entryId): ?array
    {
        foreach ($this->utilities()['utilities'] as $utility) {
            if ($utility['id'] !== $entryId || $utility['demonstration'] === null) {
                continue;
            }

            return [
                'utility' => $utility,
                'demonstration' => $utility['demonstration'],
            ];
        }

        return null;
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

    /**
     * Larena-local demonstrations bind an exact pinned framework source to a
     * small interactive scenario. A complete variant set is included only
     * when the pinned source enumerates it; Larena does not infer a grammar.
     *
     * @param array<string, array<string, mixed>> $byId
     * @return array<string, array<string, mixed>>
     */
    private function utilityDemonstrations(array $byId): array
    {
        $demonstrations = [];
        foreach (self::UTILITY_DEMONSTRATIONS as $utilityId => $demonstration) {
            foreach ($demonstration['utility_ids'] as $id) {
                if (($byId[$id]['kind'] ?? null) !== 'utility') {
                    continue 2;
                }
            }
            foreach ($demonstration['component_ids'] as $id) {
                if (($byId[$id]['kind'] ?? null) !== 'component') {
                    continue 2;
                }
            }
            $demonstrations[$utilityId] = $demonstration + [
                'source' => 'larena_demonstration_bound_to_pinned_framework_source',
            ];
        }

        return $demonstrations;
    }

    /** @var array<string, array<string, mixed>> */
    private const UTILITY_DEMONSTRATIONS = [
        'utility.gap' => [
            'id' => 'utility.gap.vertical-stack',
            'title_key' => 'gap_vertical_stack_title',
            'description_key' => 'gap_vertical_stack_description',
            'utility_ids' => ['utility.display', 'utility.flex-direction', 'utility.gap', 'utility.grid-template-columns', 'utility.background-color', 'utility.padding', 'utility.border-radius', 'utility.width'],
            'component_ids' => ['component.highlight'],
            'smart_component_keys' => ['ui.dropdown'],
            'base_classes' => 'flex flex-col',
            'initial_variant' => 'gap-1',
            'variant_contract' => [
                'scope' => 'base-spacing-scale',
                'complete' => true,
                'source_ref' => 'ui@7e836d8a9414d5da553fb1ab0404721e5b48769a:distr/utility/gap/default/css/default.css',
            ],
            'variants' => [
                ['id' => 'gap-0', 'classes' => 'gap-0'],
                ['id' => 'gap-1/4', 'classes' => 'gap-1/4'],
                ['id' => 'gap-1/3', 'classes' => 'gap-1/3'],
                ['id' => 'gap-1/2', 'classes' => 'gap-1/2'],
                ['id' => 'gap-1', 'classes' => 'gap-1'],
                ['id' => 'gap-2', 'classes' => 'gap-2'],
                ['id' => 'gap-3', 'classes' => 'gap-3'],
                ['id' => 'gap-4', 'classes' => 'gap-4'],
                ['id' => 'gap-5', 'classes' => 'gap-5'],
                ['id' => 'gap-6', 'classes' => 'gap-6'],
                ['id' => 'gap-7', 'classes' => 'gap-7'],
                ['id' => 'gap-8', 'classes' => 'gap-8'],
            ],
            'source_refs' => [
                'ui@7e836d8a9414d5da553fb1ab0404721e5b48769a:distr/utility/gap/default/css/default.css',
                'ui-play:examples/background/background-color/index.html',
                'ui-play:examples/grid/grid-template-columns/index.html',
                'ui:distr/component/highlight',
                'ui-smart:smart/dropdown',
            ],
        ],
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
