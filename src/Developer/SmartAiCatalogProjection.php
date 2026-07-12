<?php

declare(strict_types=1);

namespace Larena\Ui\Developer;

use InvalidArgumentException;

final readonly class SmartAiCatalogProjection
{
    public function __construct(private SmartCatalogProjection $catalog)
    {
    }

    /** @return array<string, mixed> */
    public function toArray(string $locale = 'en'): array
    {
        $projection = [
            'schema' => 'larena.ui.smart_ai_catalog.v1',
            'source' => 'SmartRegistry',
            'locale' => $locale,
            'components' => array_map(
                static fn ($entry): array => $entry->toArray(),
                $this->catalog->components($locale),
            ),
            'recipes' => [],
            'nonclaims' => [
                'production_ready' => false,
                'all_packages_ready' => false,
                'catalog_is_canonical_source' => false,
                'data_binding_authorized' => false,
                'effect_execution_authorized' => false,
            ],
        ];
        $this->assertSafe($projection, 'catalog');

        return $projection;
    }

    public function toJson(string $locale = 'en'): string
    {
        return json_encode(
            $this->toArray($locale),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }

    private function assertSafe(mixed $value, string $path): void
    {
        if (is_string($value)) {
            $normalized = str_replace('\\', '/', $value);
            if (str_contains($normalized, 'file://')
                || str_contains($normalized, '../')
                || str_contains($normalized, '/Users/')
                || str_contains($normalized, '/home/')
                || str_contains($normalized, '/private/')
                || preg_match('/[A-Za-z]:\//', $normalized) === 1) {
                throw new InvalidArgumentException('ui_smart_ai_catalog_unsafe_value:' . $path);
            }

            return;
        }
        if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
            return;
        }
        if (!is_array($value)) {
            throw new InvalidArgumentException('ui_smart_ai_catalog_non_json_value:' . $path);
        }
        foreach ($value as $key => $child) {
            $keyString = (string) $key;
            $normalizedKey = strtolower(str_replace('-', '_', $keyString));
            if (preg_match('/(^|_)(callback|callbacks|callable|class|classes|method|methods|template|templates|raw_html|php_callable|javascript_handler)($|_)/', $normalizedKey) === 1) {
                throw new InvalidArgumentException('ui_smart_ai_catalog_executable_key:' . $path . '.' . $keyString);
            }
            $this->assertSafe($child, $path . '.' . $keyString);
        }
    }
}
