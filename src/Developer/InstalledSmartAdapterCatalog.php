<?php

declare(strict_types=1);

namespace Larena\Ui\Developer;

use InvalidArgumentException;
use Larena\Ui\Registry\SmartRegistry;

final readonly class InstalledSmartAdapterCatalog
{
    public function __construct(
        private SmartRegistry $registry,
        private ?SourceSmartContractCatalog $sourceContracts = null,
    ) {}

    /** @return list<array<string, mixed>> */
    public function components(): array
    {
        $items = [];
        foreach ($this->registry->manifests() as $manifest) {
            $adapterAvailable = is_file($this->adapterPath($manifest->componentKey));
            $sourceContract = null;
            if ($adapterAvailable) {
                $descriptor = $this->readAdapter($manifest->componentKey);
                $sourceContract = $this->sourceCatalog()->validateAdapter($manifest->componentKey, $descriptor, $manifest);
            }
            $items[] = [
                'id' => $manifest->componentKey,
                'kind' => $manifest->kind,
                'version' => $manifest->version,
                'owner_package' => $manifest->ownerPackage,
                'renderer' => $manifest->rendererId,
                'frontend' => ['runtime' => $manifest->frontendRuntime, 'tag' => $manifest->frontendTag],
                'adapter_available' => $adapterAvailable,
                'source_contract' => $sourceContract,
            ];
        }

        return $items;
    }

    /** @return array<string, mixed> */
    public function describe(string $id, bool $forAi = false): array
    {
        $manifest = $this->registry->manifest($id);
        $descriptor = $this->readAdapter($id);
        $sourceContract = $this->sourceCatalog()->validateAdapter($id, $descriptor, $manifest);
        $sourceManifest = $this->sourceCatalog()->contract($sourceContract['id']);
        $result = [
            'schema' => $forAi ? 'larena.ui.smart_adapter_technology_packet.v1' : 'larena.ui.smart_adapter_description.v1',
            'identity' => [
                'id' => $manifest->componentKey,
                'version' => $manifest->version,
                'kind' => $manifest->kind,
                'owner_package' => $manifest->ownerPackage,
                'source_contract' => $sourceContract,
            ],
            'render' => [
                'renderer' => $manifest->rendererId,
                'frontend_runtime' => $manifest->frontendRuntime,
                'custom_element' => $manifest->frontendTag,
                'props_schema' => $manifest->propsSchema,
                'assets' => array_map(static fn ($asset): array => [
                    'key' => $asset->assetKey,
                    'kind' => $asset->kind->value,
                    'critical' => $asset->critical,
                ], $manifest->assetRequirements),
            ],
            'events' => $manifest->eventSchema,
            'host_adapter' => $descriptor,
            'public_abi' => [
                'inputs' => $sourceManifest['inputs'],
                'methods' => $sourceManifest['methods'],
                'events' => $sourceManifest['events'],
                'state' => $sourceManifest['state'],
                'composition' => $sourceManifest['composition'],
                'compatibility' => $sourceManifest['compatibility'],
            ],
        ];
        if ($forAi) {
            $result['decision'] = [
                'reuse_mode' => $sourceContract['reuse_mode'],
                'required_action' => match ($id) {
                    'ui.admin_menu' => 'Render the complete adapter through Larena\\Ui\\Facades\\Smart.',
                    'ui.dataview' => 'Render the complete dataview.table composition; keep query and access semantics in larena/dataview.',
                    default => 'Use the registered Smart adapter.',
                },
            ];
            $result['checks'] = [
                'source contract hash verified',
                'source contract version matched',
                'custom element matched',
                'semantic events matched',
                'host manifest registered',
                'props validated',
                'asset graph resolved',
            ];
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function readAdapter(string $id): array
    {
        $path = $this->adapterPath($id);
        if (!is_file($path)) {
            throw new InvalidArgumentException('ui_smart_adapter_descriptor_missing:' . $id);
        }
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)
            || ($data['schema'] ?? null) !== 'larena.ui.smart_adapter.v1'
            || ($data['id'] ?? null) !== $id
            || ($data['component_key'] ?? null) !== $id
        ) {
            throw new InvalidArgumentException('ui_smart_adapter_descriptor_invalid:' . $id);
        }

        return $data;
    }

    private function adapterPath(string $id): string
    {
        if (preg_match('/^[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)+$/', $id) !== 1) {
            throw new InvalidArgumentException('ui_smart_adapter_id_invalid');
        }

        return __DIR__ . '/../../resources/adapters/' . $id . '.json';
    }

    private function sourceCatalog(): SourceSmartContractCatalog
    {
        return $this->sourceContracts ?? new SourceSmartContractCatalog();
    }
}
