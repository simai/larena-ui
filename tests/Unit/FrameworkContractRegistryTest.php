<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Larena\Ui\Developer\FrameworkCatalogProjection;
use Larena\Ui\Frontend\FrameworkContractRegistry;
use Larena\Ui\Registry\FrameworkAdapterRegistry;

function frameworkRegistryFixture(): array
{
    $entries = [
        entry('component.buttons', 'component'),
        entry('recipe.admin.collection', 'recipe', [
            'smart.table',
            'utility.display',
            'utility.flex-direction',
            'utility.gap',
            'utility.overflow',
            'utility.width',
        ]),
        entry('smart.table', 'smart-component', ['component.buttons']),
        entry('utility.display', 'utility'),
        entry('utility.align-items', 'utility'),
        entry('utility.container', 'utility'),
        entry('utility.flex-direction', 'utility'),
        entry('utility.gap', 'utility'),
        entry('utility.grid-template-columns', 'utility'),
        entry('utility.justify-content', 'utility'),
        entry('utility.margin', 'utility'),
        entry('utility.overflow', 'utility'),
        entry('utility.padding', 'utility'),
        entry('utility.width', 'utility'),
    ];
    $byKind = ['utility' => [], 'component' => [], 'smart-component' => [], 'recipe' => []];
    foreach ($entries as $entry) {
        $byKind[$entry['kind']][] = $entry['id'];
    }
    foreach ($byKind as &$ids) {
        sort($ids);
    }
    unset($ids);

    return [
        'schema_id' => 'simai.framework.contract-registry',
        'schema_version' => 1,
        'compatibility' => [
            'id' => 'sf-v5.3.2-7e836d8a-dd786bba',
            'status' => 'bounded',
            'profile' => 'plain-assets-v1',
            'runtime_sources' => [],
            'exclusions' => [],
            'claims' => ['full_compatible' => false, 'production_ready' => false, 'all_items_ready' => false],
        ],
        'source_manifests' => array_map(static fn (string $kind): array => [
            'kind' => $kind,
            'owner' => $kind === 'smart-component' ? 'simai/ui-smart' : 'simai/ui',
            'path' => 'contracts/owners/' . $kind . '.manifest.json',
            'sha256' => str_repeat(match ($kind) { 'utility' => '1', 'component' => '2', 'smart-component' => '3', default => '4' }, 64),
        ], ['utility', 'component', 'smart-component', 'recipe']),
        'counts' => ['utility' => 11, 'component' => 1, 'smart-component' => 1, 'recipe' => 1, 'total' => 14],
        'entries' => $entries,
        'indexes' => [
            'by_kind' => $byKind,
            'safe_to_suggest' => (static function () use ($entries): array { $ids = array_column($entries, 'id'); sort($ids); return $ids; })(),
            'blocked' => [],
            'recipe_closure' => [
                'recipe.admin.collection' => [
                    'component.buttons',
                    'smart.table',
                    'utility.display',
                    'utility.flex-direction',
                    'utility.gap',
                    'utility.overflow',
                    'utility.width',
                ],
            ],
        ],
        'nonclaims' => ['production_ready' => false, 'full_compatibility' => false, 'all_items_ready' => false],
    ];
}

/** @return array<string, mixed> */
function entry(string $id, string $kind, array $requires = []): array
{
    return [
        'id' => $id,
        'kind' => $kind,
        'owner' => $kind === 'smart-component' ? 'simai/ui-smart' : 'simai/ui',
        'lifecycle' => 'released',
        'readiness' => [
            'status' => 'ready',
            'safe_to_suggest' => true,
            'profiles' => ['plain-assets-v1'],
            'blockers' => [],
        ],
        'provenance' => ['source' => 'fixture'],
        'documentation_refs' => [],
        'example_refs' => [],
        'runtime' => $id === 'smart.table' ? ['tags' => ['sf-table']] : [],
        'requires' => $requires,
        'curated_for' => ['admin.collection'],
    ];
}

function adapter(FrameworkContractRegistry $registry): array
{
    return [
        'id' => 'docara.pages.admin.collection',
        'upstream_recipe' => 'recipe.admin.collection',
        'renderer' => ['layout_recipe' => 'admin.collection', 'backend' => 'ui.sf.element', 'smart_component' => 'smart.table'],
        'permission' => ['operation' => 'docara.page.read'],
        'data' => ['source' => 'docara.pages', 'mode' => 'read-only'],
        'effects' => ['allowed' => false],
        'asset_delivery' => ['compatibility_id' => $registry->compatibilityId(), 'profile' => $registry->profile(), 'activation_owner' => 'larena/core:core.assets'],
        'support' => ['status' => 'developer-testable'],
    ];
}

$source = frameworkRegistryFixture();
$registry = FrameworkContractRegistry::fromArray($source, str_repeat('a', 64));
assert($registry->compatibilityId() === 'sf-v5.3.2-7e836d8a-dd786bba');
assert(array_column($registry->transitiveClosure('recipe.admin.collection'), 'id') === [
    'component.buttons',
    'recipe.admin.collection',
    'smart.table',
    'utility.display',
    'utility.flex-direction',
    'utility.gap',
    'utility.overflow',
    'utility.width',
]);

$adapters = new FrameworkAdapterRegistry($registry);
$adapters->register(adapter($registry));
$plan = $adapters->plan('docara.pages.admin.collection');
assert($plan['kinds'] === ['component', 'recipe', 'smart-component', 'utility']);
assert($plan['effects_allowed'] === false);
assert(strlen($plan['plan_sha256']) === 64);

$projection = new FrameworkCatalogProjection($registry, $adapters);
$projected = $projection->toArray();
assert($projected['upstream'] === $source);
assert($projected['upstream_merged_into_adapters'] === false);
assert($projected['larena_adapters'][0] === adapter($registry));

$explorer = $projection->explorer();
assert($explorer['schema'] === 'larena.ui.framework_catalog_explorer.v1');
assert($explorer['source'] === 'immutable_upstream_registry');
assert($explorer['counts'] === ['utility' => 11, 'component' => 1, 'smart-component' => 1, 'recipe' => 1, 'total' => 14]);
assert(array_column($explorer['entries'], 'id') === [
    'component.buttons',
    'recipe.admin.collection',
    'smart.table',
    'utility.align-items',
    'utility.container',
    'utility.display',
    'utility.flex-direction',
    'utility.gap',
    'utility.grid-template-columns',
    'utility.justify-content',
    'utility.margin',
    'utility.overflow',
    'utility.padding',
    'utility.width',
]);

$utilityExplorer = $projection->utilities();
assert($utilityExplorer['schema'] === 'larena.ui.framework_utility_explorer.v1');
assert($utilityExplorer['source'] === 'immutable_upstream_registry');
assert($utilityExplorer['counts'] === ['utilities' => 11, 'recipes' => 6, 'demonstrations' => 1]);
assert(array_column($utilityExplorer['recipes'], 'id') === [
    'layout.vertical-stack',
    'layout.balanced-toolbar',
    'layout.two-column-grid',
    'layout.card-grid',
    'layout.centered-container',
    'layout.scroll-safe-region',
]);
assert($utilityExplorer['recipes'][0]['utility_ids'] === ['utility.display', 'utility.flex-direction', 'utility.gap']);
assert($utilityExplorer['utilities'][0]['constraints']['value_grammar'] === 'not_enumerated_by_registry');
$gap = array_values(array_filter($utilityExplorer['utilities'], static fn (array $utility): bool => $utility['id'] === 'utility.gap'))[0];
assert($gap['demonstration']['id'] === 'utility.gap.vertical-stack');
assert($gap['demonstration']['title_key'] === 'gap_vertical_stack_title');
assert($gap['demonstration']['utility_ids'] === ['utility.display', 'utility.flex-direction', 'utility.gap']);
assert(array_column($gap['demonstration']['variants'], 'classes') === ['gap-1', 'gap-2', 'gap-3']);

$copied = adapter($registry);
$copied['props'] = ['text' => ['type' => 'string']];
try {
    (new FrameworkAdapterRegistry($registry))->register($copied);
    throw new RuntimeException('Expected copied upstream metadata to fail.');
} catch (InvalidArgumentException $exception) {
    assert($exception->getMessage() === 'ui_framework_adapter_field_forbidden:props');
}

foreach (['schema_id', 'compatibility', 'source_manifests', 'entries', 'indexes', 'id', 'kind', 'name', 'owner', 'lifecycle', 'readiness', 'provenance', 'documentation_refs', 'example_refs', 'runtime', 'requires', 'curated_for'] as $upstreamKey) {
    $nestedCopy = adapter($registry);
    $nestedCopy['support'][$upstreamKey] = $upstreamKey === 'requires' ? [] : 'copied';
    try {
        (new FrameworkAdapterRegistry($registry))->register($nestedCopy);
        throw new RuntimeException('Expected nested copied upstream metadata to fail: ' . $upstreamKey);
    } catch (InvalidArgumentException $exception) {
        assert($exception->getMessage() === 'ui_framework_adapter_upstream_copy_forbidden:adapter.support.' . $upstreamKey);
    }
}

$unknown = frameworkRegistryFixture();
$unknown['entries'][1]['requires'][] = 'smart.unknown';
try {
    FrameworkContractRegistry::fromArray($unknown);
    throw new RuntimeException('Expected unknown relation to fail.');
} catch (InvalidArgumentException $exception) {
    assert(str_starts_with($exception->getMessage(), 'ui_framework_registry_relation_unknown:'));
}

$badId = frameworkRegistryFixture();
$badId['entries'][0]['id'] = 'component.bad_name';
try {
    FrameworkContractRegistry::fromArray($badId);
    throw new RuntimeException('Expected underscore ID to fail.');
} catch (InvalidArgumentException $exception) {
    assert(str_starts_with($exception->getMessage(), 'ui_framework_registry_entry_invalid:'));
}

$legacyVersionToken = implode('', ['s', 'f', '5']);
foreach (['component.' . $legacyVersionToken . '-button', 'component.v5'] as $invalidEntryId) {
    $badId = frameworkRegistryFixture();
    $badId['entries'][0]['id'] = $invalidEntryId;
    try {
        FrameworkContractRegistry::fromArray($badId);
        throw new RuntimeException('Expected versioned entry ID to fail: ' . $invalidEntryId);
    } catch (InvalidArgumentException $exception) {
        assert(str_starts_with($exception->getMessage(), 'ui_framework_registry_entry_invalid:'));
    }
}

foreach (['docara.pages_admin.collection', 'docara.' . $legacyVersionToken . '.collection', 'docara.v5.collection'] as $invalidAdapterId) {
    $badAdapter = adapter($registry);
    $badAdapter['id'] = $invalidAdapterId;
    try {
        (new FrameworkAdapterRegistry($registry))->register($badAdapter);
        throw new RuntimeException('Expected noncanonical adapter ID to fail: ' . $invalidAdapterId);
    } catch (InvalidArgumentException $exception) {
        assert($exception->getMessage() === 'ui_framework_adapter_id_invalid');
    }
}

$missingClosure = frameworkRegistryFixture();
unset($missingClosure['indexes']['recipe_closure']['recipe.admin.collection']);
try {
    FrameworkContractRegistry::fromArray($missingClosure);
    throw new RuntimeException('Expected missing recipe closure index to fail.');
} catch (InvalidArgumentException $exception) {
    assert($exception->getMessage() === 'ui_framework_registry_recipe_closure_index_incomplete');
}

echo "FrameworkContractRegistryTest passed.\n";
