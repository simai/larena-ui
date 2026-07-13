<?php

declare(strict_types=1);

namespace Larena\Ui\Registry;

use InvalidArgumentException;
use Larena\Ui\Frontend\FrameworkContractRegistry;

/** Larena-only adapter metadata. Upstream records are referenced, never merged. */
final class FrameworkAdapterRegistry
{
    private const ALLOWED_KEYS = [
        'id',
        'upstream_recipe',
        'renderer',
        'permission',
        'data',
        'effects',
        'asset_delivery',
        'support',
    ];

    private const FORBIDDEN_KEYS = [
        'schema_id',
        'schema_version',
        'compatibility',
        'source_manifests',
        'counts',
        'entries',
        'indexes',
        'nonclaims',
        'id',
        'kind',
        'name',
        'title',
        'description',
        'owner',
        'lifecycle',
        'readiness',
        'provenance',
        'documentation_refs',
        'example_refs',
        'runtime',
        'requires',
        'curated_for',
        'props',
        'events',
        'classes',
        'raw_html',
        'template',
        'callback',
        'handler',
        'examples',
    ];

    /** @var array<string, array<string, mixed>> */
    private array $adapters = [];

    public function __construct(private readonly FrameworkContractRegistry $upstream) {}

    /** @param array<string, mixed> $adapter */
    public function register(array $adapter): void
    {
        $id = (string) ($adapter['id'] ?? '');
        if (!self::isAdapterId($id)) {
            throw new InvalidArgumentException('ui_framework_adapter_id_invalid');
        }
        if (isset($this->adapters[$id])) {
            throw new InvalidArgumentException('ui_framework_adapter_collision:' . $id);
        }
        foreach (array_keys($adapter) as $key) {
            if (!in_array($key, self::ALLOWED_KEYS, true)) {
                throw new InvalidArgumentException('ui_framework_adapter_field_forbidden:' . $key);
            }
        }
        $this->assertNoCopiedFields($adapter, true);
        $this->assertShape($adapter);
        $this->adapters[$id] = $adapter;
    }

    /** @return array<string, mixed> */
    public function adapter(string $id): array
    {
        return $this->adapters[$id]
            ?? throw new InvalidArgumentException('ui_framework_adapter_unknown:' . $id);
    }

    /** @return list<array<string, mixed>> */
    public function adapters(): array
    {
        $adapters = $this->adapters;
        ksort($adapters, SORT_STRING);

        return array_values($adapters);
    }

    /** @return array<string, mixed> */
    public function plan(string $adapterId): array
    {
        $adapter = $this->adapter($adapterId);
        $recipeId = (string) $adapter['upstream_recipe'];
        $entries = $this->upstream->transitiveClosure($recipeId);
        $byId = [];
        $kinds = [];
        foreach ($entries as $entry) {
            if (($entry['readiness']['safe_to_suggest'] ?? null) !== true
                || !in_array($this->upstream->profile(), $entry['readiness']['profiles'] ?? [], true)
            ) {
                throw new InvalidArgumentException('ui_framework_adapter_plan_entry_not_ready:' . (string) ($entry['id'] ?? 'unknown'));
            }
            $byId[(string) $entry['id']] = $entry;
            $kinds[(string) $entry['kind']] = true;
        }
        foreach (['utility', 'component', 'smart-component', 'recipe'] as $kind) {
            if (!isset($kinds[$kind])) {
                throw new InvalidArgumentException('ui_framework_adapter_plan_kind_missing:' . $kind);
            }
        }
        $smartId = (string) $adapter['renderer']['smart_component'];
        if (!isset($byId[$smartId]) || $byId[$smartId]['kind'] !== 'smart-component') {
            throw new InvalidArgumentException('ui_framework_adapter_smart_component_not_in_recipe:' . $smartId);
        }

        $plan = [
            'schema' => 'larena.ui.framework_resolved_plan.v1',
            'adapter' => $adapter,
            'compatibility_id' => $this->upstream->compatibilityId(),
            'profile' => $this->upstream->profile(),
            'registry_sha256' => $this->upstream->sha256(),
            'entry_ids' => array_keys($byId),
            'kinds' => array_keys($kinds),
            'entries' => array_values($byId),
            'effects_allowed' => false,
            'production_ready' => false,
        ];
        sort($plan['entry_ids'], SORT_STRING);
        sort($plan['kinds'], SORT_STRING);
        $plan['plan_sha256'] = self::canonicalHash($plan);

        return $plan;
    }

    /** @param array<string, mixed> $adapter */
    private function assertShape(array $adapter): void
    {
        foreach (self::ALLOWED_KEYS as $key) {
            if (!array_key_exists($key, $adapter)) {
                throw new InvalidArgumentException('ui_framework_adapter_field_missing:' . $key);
            }
        }
        $recipe = $this->upstream->entry((string) $adapter['upstream_recipe']);
        if ($recipe['kind'] !== 'recipe') {
            throw new InvalidArgumentException('ui_framework_adapter_recipe_kind_invalid');
        }
        $renderer = is_array($adapter['renderer']) ? $adapter['renderer'] : [];
        if (($renderer['layout_recipe'] ?? null) !== 'admin.collection'
            || !is_string($renderer['backend'] ?? null)
            || !is_string($renderer['smart_component'] ?? null)
            || ($this->upstream->entry((string) $renderer['smart_component'])['kind'] ?? null) !== 'smart-component'
        ) {
            throw new InvalidArgumentException('ui_framework_adapter_renderer_invalid');
        }
        $permission = is_array($adapter['permission']) ? $adapter['permission'] : [];
        if (!is_string($permission['operation'] ?? null) || trim((string) $permission['operation']) === '') {
            throw new InvalidArgumentException('ui_framework_adapter_permission_invalid');
        }
        $data = is_array($adapter['data']) ? $adapter['data'] : [];
        if (!is_string($data['source'] ?? null) || ($data['mode'] ?? null) !== 'read-only') {
            throw new InvalidArgumentException('ui_framework_adapter_data_invalid');
        }
        if (!is_array($adapter['effects']) || ($adapter['effects']['allowed'] ?? null) !== false) {
            throw new InvalidArgumentException('ui_framework_adapter_effects_invalid');
        }
        $delivery = is_array($adapter['asset_delivery']) ? $adapter['asset_delivery'] : [];
        if (($delivery['compatibility_id'] ?? null) !== $this->upstream->compatibilityId()
            || ($delivery['profile'] ?? null) !== $this->upstream->profile()
            || ($delivery['activation_owner'] ?? null) !== 'larena/core:core.assets'
        ) {
            throw new InvalidArgumentException('ui_framework_adapter_asset_delivery_invalid');
        }
        $support = is_array($adapter['support']) ? $adapter['support'] : [];
        if (!in_array($support['status'] ?? null, ['developer-testable', 'experimental', 'blocked'], true)) {
            throw new InvalidArgumentException('ui_framework_adapter_support_invalid');
        }
    }

    /** @param array<string, mixed> $value */
    private function assertNoCopiedFields(array $value, bool $root = false, string $path = 'adapter'): void
    {
        foreach ($value as $key => $child) {
            if (in_array($key, self::FORBIDDEN_KEYS, true)
                && !($root && $key === 'id')
            ) {
                throw new InvalidArgumentException('ui_framework_adapter_upstream_copy_forbidden:' . $path . '.' . $key);
            }
            if (is_array($child)) {
                $this->assertNoCopiedFields($child, false, $path . '.' . (string) $key);
            }
        }
    }

    /** @param array<string, mixed> $value */
    private static function canonicalHash(array $value): string
    {
        $normalize = function (mixed $item) use (&$normalize): mixed {
            if (!is_array($item)) {
                return $item;
            }
            if (array_is_list($item)) {
                return array_map($normalize, $item);
            }
            ksort($item, SORT_STRING);
            foreach ($item as $key => $child) {
                $item[$key] = $normalize($child);
            }

            return $item;
        };

        return hash('sha256', json_encode(
            $normalize($value),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ));
    }

    private static function isAdapterId(string $id): bool
    {
        $legacyVersionToken = implode('', ['s', 'f', '5']);

        if (preg_match('/^[a-z][a-z0-9-]*(?:\.[a-z][a-z0-9-]*)+$/', $id) !== 1
            || str_contains($id, '_')
            || str_contains($id, $legacyVersionToken)
        ) {
            return false;
        }
        foreach (explode('.', $id) as $segment) {
            if (preg_match('/^v\d+$/', $segment) === 1) {
                return false;
            }
        }

        return true;
    }
}
