<?php

declare(strict_types=1);

namespace Larena\Ui\Contracts;

final readonly class SmartCatalogEntry
{
    /**
     * @param list<string> $states
     * @param list<string> $accessibility
     * @param array<string, mixed> $exampleProps
     * @param list<array<string, mixed>> $controls
     * @param array{safe_to_suggest: bool, safe_to_render: bool, safe_to_bind_data: bool, safe_to_execute_effect: bool} $readiness
     * @param array{backend: array{available: bool, value?: string, reason?: string}, frontend: array{available: bool, value?: string, reason?: string}} $examples
     * @param list<array{key: string, kind: string, critical: bool}> $assets
     * @param array<string, string> $provenance
     * @param array<string, mixed> $manifestProjection
     */
    public function __construct(
        public SmartComponentManifest $manifest,
        public string $key,
        public string $title,
        public string $description,
        public string $category,
        public int $order,
        public string $status,
        public array $states,
        public array $accessibility,
        public string $productHref,
        public array $exampleProps,
        public array $controls,
        public array $readiness,
        public array $examples,
        public array $assets,
        public array $provenance,
        public array $manifestProjection,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'larena.ui.smart_catalog_entry.v1',
            'key' => $this->key,
            'version' => $this->manifest->version,
            'owner_package' => $this->manifest->ownerPackage,
            'kind' => $this->manifest->kind,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'order' => $this->order,
            'status' => $this->status,
            'states' => $this->states,
            'accessibility' => $this->accessibility,
            'product_href' => $this->productHref,
            'example_props' => $this->exampleProps,
            'controls' => $this->controls,
            'readiness' => $this->readiness,
            'examples' => $this->examples,
            'assets' => $this->assets,
            'provenance' => $this->provenance,
            'manifest' => $this->manifestProjection,
        ];
    }
}
