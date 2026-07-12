<?php

declare(strict_types=1);

namespace Larena\Ui\Contracts;

use InvalidArgumentException;
use Larena\Ui\Enums\RenderStrategy;
use Larena\Ui\Enums\UiAssetKind;

final readonly class SmartComponentManifest
{
    /**
     * @param array<string, mixed> $propsSchema
     * @param list<string> $slotKeys
     * @param list<UiAssetRequirement> $assetRequirements
     * @param array<string, mixed> $eventSchema
     * @param array<string, mixed> $views
     * @param array<string, mixed> $presets
     * @param array<string, mixed> $constraints
     * @param array<string, mixed> $atlas
     * @param array<string, mixed> $provenance
     */
    public function __construct(
        public string $componentKey,
        public array $propsSchema,
        public array $slotKeys,
        public RenderStrategy $renderStrategy,
        public array $assetRequirements = [],
        public string $version = '1.0.0',
        public string $ownerPackage = 'larena/ui',
        public string $kind = 'smart',
        public array $eventSchema = [],
        public array $views = [],
        public array $presets = [],
        public array $constraints = [],
        public string $rendererId = '',
        public ?string $frontendRuntime = null,
        public ?string $frontendTag = null,
        public array $atlas = [],
        public array $provenance = [],
    ) {
    }

    public static function fromJsonFile(string $path): self
    {
        $realPath = realpath($path);
        if ($realPath === false || !is_file($realPath)) {
            throw new InvalidArgumentException('ui_smart_manifest_file_missing');
        }
        $decoded = json_decode((string) file_get_contents($realPath), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new InvalidArgumentException('ui_smart_manifest_json_invalid');
        }

        return self::fromArray($decoded, $realPath);
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, ?string $sourcePath = null): self
    {
        if (($data['schema'] ?? null) !== 'larena.ui.smart_manifest.v1') {
            throw new InvalidArgumentException('ui_smart_manifest_schema_unknown');
        }
        $render = is_array($data['render'] ?? null) ? $data['render'] : [];
        $frontend = is_array($data['frontend'] ?? null) ? $data['frontend'] : [];
        $assets = [];
        foreach (is_array($data['assets'] ?? null) ? $data['assets'] : [] as $asset) {
            if (!is_array($asset)) {
                throw new InvalidArgumentException('ui_smart_manifest_asset_invalid');
            }
            $assets[] = new UiAssetRequirement(
                (string) ($asset['key'] ?? ''),
                UiAssetKind::tryFrom((string) ($asset['kind'] ?? ''))
                    ?? throw new InvalidArgumentException('ui_smart_manifest_asset_kind_invalid'),
                (bool) ($asset['critical'] ?? false),
            );
        }
        $provenance = is_array($data['provenance'] ?? null) ? $data['provenance'] : [];
        if ($sourcePath !== null) {
            $provenance['manifest_path'] = $sourcePath;
            $provenance['manifest_sha256'] = hash_file('sha256', $sourcePath);
        }

        $manifest = new self(
            (string) ($data['key'] ?? ''),
            is_array($data['props'] ?? null) ? $data['props'] : [],
            array_map('strval', is_array($data['slots'] ?? null) ? array_keys($data['slots']) : []),
            RenderStrategy::tryFrom((string) ($render['strategy'] ?? ''))
                ?? throw new InvalidArgumentException('ui_smart_manifest_render_strategy_invalid'),
            $assets,
            (string) ($data['version'] ?? ''),
            (string) ($data['owner_package'] ?? ''),
            (string) ($data['kind'] ?? 'smart'),
            is_array($data['events'] ?? null) ? $data['events'] : [],
            is_array($data['views'] ?? null) ? $data['views'] : [],
            is_array($data['presets'] ?? null) ? $data['presets'] : [],
            is_array($data['constraints'] ?? null) ? $data['constraints'] : [],
            (string) ($render['renderer'] ?? ''),
            isset($frontend['runtime']) ? (string) $frontend['runtime'] : null,
            isset($frontend['tag']) ? (string) $frontend['tag'] : null,
            is_array($data['atlas'] ?? null) ? $data['atlas'] : [],
            $provenance,
        );
        if (!$manifest->isCanonical()) {
            throw new InvalidArgumentException('ui_smart_manifest_invalid:' . $manifest->componentKey);
        }

        return $manifest;
    }

    public static function isStableKey(string $key): bool
    {
        return preg_match('/^[a-z][a-z0-9_]*(\\.[a-z][a-z0-9_]*)*$/', $key) === 1;
    }

    public function isValid(): bool
    {
        foreach ($this->assetRequirements as $assetRequirement) {
            if (!$assetRequirement->isValid()) {
                return false;
            }
        }

        return self::isStableKey($this->componentKey)
            && $this->propsSchema !== [];
    }

    public function isCanonical(): bool
    {
        return $this->isValid()
            && preg_match('/^\d+\.\d+\.\d+$/', $this->version) === 1
            && preg_match('/^[a-z][a-z0-9-]*\/[a-z][a-z0-9-]*$/', $this->ownerPackage) === 1
            && in_array($this->kind, ['element', 'smart', 'composite'], true)
            && self::isStableKey($this->rendererId)
            && ($this->frontendTag === null || preg_match('/^sf-[a-z0-9-]+$/', $this->frontendTag) === 1)
            && ($this->frontendTag === null || $this->frontendRuntime === 'simai-framework');
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'larena.ui.smart_manifest.v1',
            'key' => $this->componentKey,
            'version' => $this->version,
            'owner_package' => $this->ownerPackage,
            'kind' => $this->kind,
            'props' => $this->propsSchema,
            'slots' => array_fill_keys($this->slotKeys, []),
            'events' => $this->eventSchema,
            'views' => $this->views,
            'presets' => $this->presets,
            'constraints' => $this->constraints,
            'render' => ['strategy' => $this->renderStrategy->value, 'renderer' => $this->rendererId],
            'frontend' => ['runtime' => $this->frontendRuntime, 'tag' => $this->frontendTag],
            'assets' => array_map(static fn (UiAssetRequirement $asset): array => [
                'key' => $asset->assetKey,
                'kind' => $asset->kind->value,
                'critical' => $asset->critical,
            ], $this->assetRequirements),
            'atlas' => $this->atlas,
            'provenance' => $this->provenance,
        ];
    }
}
