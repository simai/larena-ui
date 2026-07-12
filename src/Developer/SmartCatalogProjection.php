<?php

declare(strict_types=1);

namespace Larena\Ui\Developer;

use InvalidArgumentException;
use Larena\Ui\Contracts\SmartCatalogEntry;
use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Contracts\UiAssetRequirement;
use Larena\Ui\Reference\SmartComponentReference;
use Larena\Ui\Registry\SmartRegistry;

final readonly class SmartCatalogProjection
{
    /** @var list<string> */
    private const READINESS_KEYS = [
        'safe_to_suggest',
        'safe_to_render',
        'safe_to_bind_data',
        'safe_to_execute_effect',
    ];

    /** @var list<string> */
    private const SAFE_PROVENANCE_KEYS = [
        'source',
        'runtime_lock',
        'upstream_component',
        'upstream_revision',
        'reference_status',
        'evidence',
        'manifest_path',
        'manifest_sha256',
    ];

    public function __construct(
        private SmartRegistry $registry,
        private SmartInvocationExampleBuilder $examples,
    ) {
    }

    /** @return list<SmartCatalogEntry> */
    public function components(string $locale = 'en'): array
    {
        $this->assertLocale($locale);

        return array_map(
            fn (SmartComponentManifest $manifest): SmartCatalogEntry => $this->project($manifest, $locale),
            $this->registry->atlasManifests(),
        );
    }

    public function component(string $key, string $locale = 'en'): SmartCatalogEntry
    {
        $this->assertLocale($locale);
        $manifest = $this->registry->manifest($key);
        if (($manifest->atlas['visible'] ?? false) !== true) {
            throw new InvalidArgumentException('ui_smart_catalog_component_not_visible:' . $key);
        }

        return $this->project($manifest, $locale);
    }

    private function project(SmartComponentManifest $manifest, string $locale): SmartCatalogEntry
    {
        $this->registry->renderer($manifest->rendererId);
        $atlas = $manifest->atlas;
        $this->assertAtlas($manifest, $atlas);
        $localized = $this->localizedAtlas($manifest, $atlas, $locale);
        $reference = new SmartComponentReference($manifest);
        $resolved = $reference->resolve();
        $exampleProps = $this->safeData($resolved['props'], $manifest, 'example_props');
        if (!is_array($exampleProps)) {
            $this->invalid($manifest, 'example_props');
        }
        $controls = $this->localizedControls($manifest, $reference, $localized);
        $readiness = $this->readiness($manifest, $atlas);
        $provenance = $this->safeProvenance($manifest);

        return new SmartCatalogEntry(
            $manifest,
            $manifest->componentKey,
            $this->safeText($manifest, 'title', $localized['title']),
            $this->safeText($manifest, 'description', $localized['description'], 1000),
            (string) $atlas['category'],
            (int) $atlas['order'],
            (string) $atlas['status'],
            $this->stringList($manifest, 'states', $atlas['states'], false),
            $this->stringList($manifest, 'accessibility', $atlas['accessibility'], false),
            (string) $atlas['product_href'],
            $exampleProps,
            $controls,
            $readiness,
            [
                'backend' => $this->example(
                    fn (): string => $this->examples->php($manifest, $exampleProps),
                    'ui_smart_catalog_backend_example_unavailable',
                ),
                'frontend' => $this->example(
                    fn (): string => $this->examples->frontend($manifest, $exampleProps),
                    'ui_smart_catalog_frontend_example_unavailable',
                ),
            ],
            array_map(static fn (UiAssetRequirement $asset): array => [
                'key' => $asset->assetKey,
                'kind' => $asset->kind->value,
                'critical' => $asset->critical,
            ], $manifest->assetRequirements),
            $provenance,
            $this->manifestProjection($manifest, $provenance),
        );
    }

    /** @param array<string, mixed> $atlas */
    private function assertAtlas(SmartComponentManifest $manifest, array $atlas): void
    {
        foreach (['title', 'description', 'category', 'status', 'product_href'] as $field) {
            if (!is_string($atlas[$field] ?? null) || trim((string) $atlas[$field]) === '') {
                $this->invalid($manifest, $field);
            }
        }
        $this->safeText($manifest, 'title', $atlas['title']);
        $this->safeText($manifest, 'description', $atlas['description'], 1000);
        if (!is_int($atlas['order'] ?? null) || $atlas['order'] < 0) {
            $this->invalid($manifest, 'order');
        }
        if (preg_match('/^[a-z][a-z0-9_-]*$/', (string) $atlas['category']) !== 1) {
            $this->invalid($manifest, 'category');
        }
        if (preg_match('/^[a-z][a-z0-9_-]*$/', (string) $atlas['status']) !== 1) {
            $this->invalid($manifest, 'status');
        }
        $href = (string) $atlas['product_href'];
        if (!str_starts_with($href, '/') || str_contains($href, '..') || preg_match('/[\\\\<>"\x00-\x1F]/', $href) === 1) {
            $this->invalid($manifest, 'product_href');
        }
        $this->stringList($manifest, 'states', $atlas['states'] ?? null, false);
        $this->stringList($manifest, 'accessibility', $atlas['accessibility'] ?? null, false);
        if (!is_array($atlas['example_props'] ?? null)) {
            $this->invalid($manifest, 'example_props');
        }
        if (!is_array($atlas['controls'] ?? null) || !array_is_list($atlas['controls']) || $atlas['controls'] === []) {
            $this->invalid($manifest, 'controls');
        }
        $controlKeys = [];
        foreach ($atlas['controls'] as $control) {
            $key = is_array($control) ? ($control['key'] ?? null) : null;
            if (!is_string($key) || preg_match('/^[a-z][a-z0-9_-]*$/', $key) !== 1 || isset($controlKeys[$key])) {
                $this->invalid($manifest, 'controls');
            }
            $controlKeys[$key] = true;
        }
        if (!is_array($atlas['i18n'] ?? null)) {
            $this->invalid($manifest, 'i18n');
        }
        foreach (['en', 'ru'] as $requiredLocale) {
            $localized = $atlas['i18n'][$requiredLocale] ?? null;
            if (!is_array($localized)
                || !is_string($localized['title'] ?? null)
                || !is_string($localized['description'] ?? null)
                || !is_array($localized['controls'] ?? null)) {
                $this->invalid($manifest, 'i18n.' . $requiredLocale);
            }
            $this->safeText($manifest, 'i18n.' . $requiredLocale . '.title', $localized['title']);
            $this->safeText($manifest, 'i18n.' . $requiredLocale . '.description', $localized['description'], 1000);
            foreach (array_keys($controlKeys) as $controlKey) {
                $this->safeText(
                    $manifest,
                    'i18n.' . $requiredLocale . '.controls.' . $controlKey,
                    $localized['controls'][$controlKey] ?? null,
                );
            }
            $localizedOptions = $localized['options'] ?? [];
            if (!is_array($localizedOptions)) {
                $this->invalid($manifest, 'i18n.' . $requiredLocale . '.options');
            }
            foreach ($localizedOptions as $controlKey => $options) {
                if (!is_string($controlKey) || !isset($controlKeys[$controlKey]) || !is_array($options)) {
                    $this->invalid($manifest, 'i18n.' . $requiredLocale . '.options');
                }
                foreach ($options as $option => $label) {
                    $this->safeText(
                        $manifest,
                        'i18n.' . $requiredLocale . '.options.' . $controlKey . '.' . (string) $option,
                        $label,
                    );
                }
            }
        }
    }

    /** @param array<string, mixed> $atlas @return array<string, mixed> */
    private function localizedAtlas(SmartComponentManifest $manifest, array $atlas, string $locale): array
    {
        $i18n = $atlas['i18n'];
        $localized = $i18n[$locale] ?? $i18n['en'] ?? null;
        if (!is_array($localized)) {
            $this->invalid($manifest, 'i18n.' . $locale);
        }

        return $localized;
    }

    /** @return list<array<string, mixed>> */
    private function localizedControls(
        SmartComponentManifest $manifest,
        SmartComponentReference $reference,
        array $localized,
    ): array {
        $controlLabels = $localized['controls'] ?? null;
        $optionLabels = $localized['options'] ?? [];
        if (!is_array($controlLabels) || !is_array($optionLabels)) {
            $this->invalid($manifest, 'i18n.controls');
        }

        $controls = [];
        foreach ($reference->controls() as $control) {
            $key = (string) $control['key'];
            $label = $controlLabels[$key] ?? null;
            if (!is_string($label) || trim($label) === '') {
                $this->invalid($manifest, 'i18n.controls.' . $key);
            }
            $labels = [];
            foreach ($control['options'] as $option) {
                $candidate = is_array($optionLabels[$key] ?? null) ? ($optionLabels[$key][$option] ?? null) : null;
                $labels[(string) $option] = is_string($candidate) && trim($candidate) !== ''
                    ? $this->safeText($manifest, 'i18n.options.' . $key, $candidate)
                    : $this->humanize((string) $option);
            }
            $normalized = array_replace($control, [
                'label' => $this->safeText($manifest, 'i18n.controls.' . $key, $label),
                'option_labels' => $labels,
            ]);
            $safe = $this->safeData($normalized, $manifest, 'controls.' . $key);
            if (!is_array($safe)) {
                $this->invalid($manifest, 'controls.' . $key);
            }
            $controls[] = $safe;
        }

        return $controls;
    }

    /**
     * @param array<string, mixed> $atlas
     * @return array{safe_to_suggest: bool, safe_to_render: bool, safe_to_bind_data: bool, safe_to_execute_effect: bool}
     */
    private function readiness(SmartComponentManifest $manifest, array $atlas): array
    {
        $readiness = $atlas['readiness'] ?? null;
        if (!is_array($readiness)) {
            $this->invalid($manifest, 'readiness');
        }
        $normalized = [];
        foreach (self::READINESS_KEYS as $key) {
            if (!array_key_exists($key, $readiness) || !is_bool($readiness[$key])) {
                $this->invalid($manifest, 'readiness.' . $key);
            }
            $normalized[$key] = $readiness[$key];
        }
        foreach (array_keys($readiness) as $key) {
            if (!is_string($key) || !in_array($key, self::READINESS_KEYS, true)) {
                $this->invalid($manifest, 'readiness.unknown');
            }
        }

        /** @var array{safe_to_suggest: bool, safe_to_render: bool, safe_to_bind_data: bool, safe_to_execute_effect: bool} $normalized */
        return $normalized;
    }

    /** @return array<string, string> */
    private function safeProvenance(SmartComponentManifest $manifest): array
    {
        $safe = [];
        foreach (self::SAFE_PROVENANCE_KEYS as $key) {
            $value = $manifest->provenance[$key] ?? null;
            if (!is_string($value) || !$this->safeProvenanceValue($value)) {
                continue;
            }
            if ($key === 'manifest_path'
                && preg_match('#^resources/smart/[a-z0-9][a-z0-9._-]*(?:/[a-z0-9][a-z0-9._-]*)*/manifest\.json$#', $value) !== 1) {
                continue;
            }
            if ($key === 'manifest_sha256' && preg_match('/^[a-f0-9]{64}$/', $value) !== 1) {
                continue;
            }
            $safe[$key] = $value;
        }
        if (!isset($safe['manifest_path'], $safe['manifest_sha256'])) {
            $this->invalid($manifest, 'provenance');
        }

        return $safe;
    }

    private function safeProvenanceValue(string $value): bool
    {
        $normalized = str_replace('\\', '/', trim($value));

        return $normalized !== ''
            && strlen($normalized) <= 512
            && preg_match('/[\x00-\x1F\x7F<>]/', $normalized) !== 1
            && !str_contains($normalized, '://')
            && !str_contains($normalized, '../')
            && !str_starts_with($normalized, '/')
            && preg_match('/^[A-Za-z]:\//', $normalized) !== 1;
    }

    /** @param array<string, string> $provenance @return array<string, mixed> */
    private function manifestProjection(SmartComponentManifest $manifest, array $provenance): array
    {
        $events = [];
        foreach ($manifest->eventSchema as $name => $event) {
            if (!$this->isSafeEventName($name) || !is_array($event)) {
                $this->invalid($manifest, 'events');
            }
            $kind = $event['kind'] ?? null;
            $backendBinding = $event['backend_handler_binding'] ?? null;
            if (!is_string($kind) || preg_match('/^[a-z][a-z0-9_-]*$/', $kind) !== 1 || !is_bool($backendBinding)) {
                $this->invalid($manifest, 'events.' . $name);
            }
            $events[$name] = ['kind' => $kind, 'backend_handler_binding' => $backendBinding];
        }

        $views = [];
        foreach (array_keys($manifest->views) as $view) {
            if (preg_match('/^[a-z][a-z0-9_-]*$/', $view) !== 1) {
                $this->invalid($manifest, 'views');
            }
            $views[] = $view;
        }

        return [
            'schema' => 'larena.ui.smart_manifest_projection.v1',
            'key' => $manifest->componentKey,
            'version' => $manifest->version,
            'owner_package' => $manifest->ownerPackage,
            'kind' => $manifest->kind,
            'props' => $this->safeData($manifest->propsSchema, $manifest, 'props'),
            'slots' => $manifest->slotKeys,
            'events' => $events,
            'views' => $views,
            'presets' => $this->safeData($manifest->presets, $manifest, 'presets'),
            'constraints' => $this->safeData($manifest->constraints, $manifest, 'constraints'),
            'render' => [
                'strategy' => $manifest->renderStrategy->value,
                'renderer' => $manifest->rendererId,
            ],
            'frontend' => [
                'runtime' => $manifest->frontendRuntime,
                'tag' => $manifest->frontendTag,
            ],
            'assets' => array_map(static fn (UiAssetRequirement $asset): array => [
                'key' => $asset->assetKey,
                'kind' => $asset->kind->value,
                'critical' => $asset->critical,
            ], $manifest->assetRequirements),
            'provenance' => $provenance,
        ];
    }

    private function isSafeEventName(mixed $name): bool
    {
        return is_string($name)
            && preg_match('/^[a-z][a-z0-9-]*(?::[a-z][a-z0-9-]*)*$/', $name) === 1
            && preg_match('/^on[a-z0-9-]*(?::|$)/', $name) !== 1;
    }

    /** @return array{available: bool, value?: string, reason?: string} */
    private function example(callable $builder, string $fallbackReason): array
    {
        try {
            $value = $builder();
            if (!is_string($value) || $value === '') {
                return ['available' => false, 'reason' => $fallbackReason];
            }

            return ['available' => true, 'value' => $value];
        } catch (InvalidArgumentException $exception) {
            $reason = preg_replace('/[^a-z0-9_.:,\/-]/', '_', strtolower($exception->getMessage()));

            return [
                'available' => false,
                'reason' => is_string($reason) && $reason !== '' ? substr($reason, 0, 240) : $fallbackReason,
            ];
        }
    }

    /** @return list<string> */
    private function stringList(SmartComponentManifest $manifest, string $field, mixed $value, bool $allowEmpty): array
    {
        if (!is_array($value) || !array_is_list($value) || (!$allowEmpty && $value === [])) {
            $this->invalid($manifest, $field);
        }
        $normalized = [];
        foreach ($value as $item) {
            if (!is_string($item) || preg_match('/^[a-z][a-z0-9_-]*$/', $item) !== 1 || in_array($item, $normalized, true)) {
                $this->invalid($manifest, $field);
            }
            $normalized[] = $item;
        }

        return $normalized;
    }

    private function safeText(SmartComponentManifest $manifest, string $field, mixed $value, int $maxLength = 240): string
    {
        if (!is_string($value)) {
            $this->invalid($manifest, $field);
        }
        $value = trim($value);
        $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
        if ($value === '' || $length > $maxLength || preg_match('/[\x00-\x1F\x7F<>]/u', $value) === 1) {
            $this->invalid($manifest, $field);
        }

        return $value;
    }

    private function safeData(mixed $value, SmartComponentManifest $manifest, string $path): mixed
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }
        if (is_string($value)) {
            if (strlen($value) > 10000 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) === 1) {
                $this->invalid($manifest, $path);
            }

            return $value;
        }
        if (!is_array($value)) {
            $this->invalid($manifest, $path);
        }
        $safe = [];
        foreach ($value as $key => $child) {
            if (is_string($key) && $this->isExecutableKey($key)) {
                $this->invalid($manifest, $path . '.' . $key);
            }
            $safe[$key] = $this->safeData($child, $manifest, $path . '.' . (string) $key);
        }

        return $safe;
    }

    private function isExecutableKey(string $key): bool
    {
        $normalized = strtolower(str_replace('-', '_', $key));

        return preg_match('/(^|_)(callback|callbacks|callable|class|classes|method|methods|template|templates|raw_html|php_callable|javascript_handler)($|_)/', $normalized) === 1;
    }

    private function assertLocale(string $locale): void
    {
        if (preg_match('/^[a-z]{2}(?:_[A-Z]{2})?$/', $locale) !== 1) {
            throw new InvalidArgumentException('ui_smart_catalog_locale_invalid');
        }
    }

    private function humanize(string $value): string
    {
        $value = $value === '' ? 'default' : $value;

        return ucfirst(str_replace(['_', '-'], ' ', $value));
    }

    private function invalid(SmartComponentManifest $manifest, string $field): never
    {
        throw new InvalidArgumentException('ui_smart_catalog_manifest_invalid:' . $manifest->componentKey . ':' . $field);
    }
}
