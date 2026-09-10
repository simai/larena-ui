<?php

declare(strict_types=1);

namespace Larena\Ui\Developer;

use InvalidArgumentException;
use Larena\Ui\Contracts\SmartComponentManifest;

final readonly class SourceSmartContractCatalog
{
    public function __construct(private ?string $directory = null) {}

    /** @return array<string, mixed> */
    public function contract(string $id): array
    {
        if (preg_match('/^smart\.[a-z][a-z0-9-]*(?:\.[a-z][a-z0-9-]*)*$/', $id) !== 1) {
            throw new InvalidArgumentException('ui_source_contract_id_invalid');
        }
        $directory = $this->contractDirectory();
        $materializationContent = $this->read($directory . '/materialization.json', 'materialization');
        $materialization = $this->decode($materializationContent, 'materialization');
        if (($materialization['schema'] ?? null) !== 'larena.ui.source_contract_materialization.v1') {
            throw new InvalidArgumentException('ui_source_contract_materialization_invalid');
        }

        $registryContent = $this->read($directory . '/smart-component-contract-registry.json', 'registry');
        $schemaContent = $this->read($directory . '/smart-component-manifest.schema.json', 'schema');
        if (!hash_equals((string) ($materialization['registry_sha256'] ?? ''), hash('sha256', $registryContent))
            || !hash_equals((string) ($materialization['registry_schema_sha256'] ?? ''), hash('sha256', $schemaContent))
        ) {
            throw new InvalidArgumentException('ui_source_contract_materialization_drift');
        }
        $registry = $this->decode($registryContent, 'registry');
        if (($registry['schema'] ?? null) !== 'simai.smart-component-contract-registry.v1'
            || ($registry['schema_id'] ?? null) !== 'https://simai.io/contracts/smart-component-manifest.v1.schema.json'
            || !hash_equals((string) ($registry['schema_sha256'] ?? ''), hash('sha256', $schemaContent))
        ) {
            throw new InvalidArgumentException('ui_source_contract_registry_invalid');
        }

        $record = null;
        foreach (is_array($materialization['contracts'] ?? null) ? $materialization['contracts'] : [] as $candidate) {
            if (is_array($candidate) && ($candidate['id'] ?? null) === $id) {
                $record = $candidate;
                break;
            }
        }
        if (!is_array($record)
            || ($record['file'] ?? null) !== $id . '.json'
            || preg_match('/^\d+\.\d+\.\d+$/', (string) ($record['version'] ?? '')) !== 1
            || preg_match('/^[a-f0-9]{64}$/', (string) ($record['manifest_sha256'] ?? '')) !== 1
        ) {
            throw new InvalidArgumentException('ui_source_contract_record_invalid:' . $id);
        }
        $registryEntry = null;
        foreach (is_array($registry['entries'] ?? null) ? $registry['entries'] : [] as $candidate) {
            if (is_array($candidate) && ($candidate['id'] ?? null) === $id) {
                $registryEntry = $candidate;
                break;
            }
        }
        if (!is_array($registryEntry)
            || ($registryEntry['manifest_sha256'] ?? null) !== $record['manifest_sha256']
            || ($registryEntry['contract_schema']['version'] ?? null) !== 1
        ) {
            throw new InvalidArgumentException('ui_source_contract_registry_entry_invalid:' . $id);
        }
        $manifestContent = $this->read($directory . '/' . $record['file'], $id);
        if (!hash_equals($record['manifest_sha256'], hash('sha256', $manifestContent))) {
            throw new InvalidArgumentException('ui_source_contract_manifest_drift:' . $id);
        }
        $manifest = $this->decode($manifestContent, $id);
        if (($manifest['id'] ?? null) !== $id
            || ($manifest['version'] ?? null) !== $record['version']
            || ($manifest['compatibility']['contract_schema']['id'] ?? null) !== 'https://simai.io/contracts/smart-component-manifest.v1.schema.json'
            || ($manifest['compatibility']['contract_schema']['version'] ?? null) !== 1
        ) {
            throw new InvalidArgumentException('ui_source_contract_manifest_invalid:' . $id);
        }

        return $manifest + ['manifest_sha256' => $record['manifest_sha256']];
    }

    /**
     * @param array<string, mixed> $adapter
     * @return array<string, mixed>
     */
    public function validateAdapter(string $adapterId, array $adapter, SmartComponentManifest $hostManifest): array
    {
        $sourceRef = is_array($adapter['source_contract'] ?? null) ? $adapter['source_contract'] : [];
        $sourceId = (string) ($sourceRef['id'] ?? '');
        $source = $this->contract($sourceId);
        if (($sourceRef['version'] ?? null) !== ($source['version'] ?? null)) {
            throw new InvalidArgumentException('ui_source_contract_version_mismatch:' . $adapterId);
        }
        if (($source['reuse_mode'] ?? null) !== 'whole'
            || ($source['custom_element'] ?? null) !== $hostManifest->frontendTag
            || !in_array('larena:' . $adapterId, $source['backend']['adapters'] ?? [], true)
        ) {
            throw new InvalidArgumentException('ui_source_contract_identity_mismatch:' . $adapterId);
        }

        $sourceEvents = array_keys(is_array($source['events'] ?? null) ? $source['events'] : []);
        $adapterEvents = array_keys(is_array($adapter['allowed_events'] ?? null) ? $adapter['allowed_events'] : []);
        $hostEvents = array_values(array_filter(
            array_keys($hostManifest->eventSchema),
            static fn (string $event): bool => $event !== 'sf-connected',
        ));
        foreach (array_unique([...$adapterEvents, ...$hostEvents]) as $event) {
            if (!in_array($event, $sourceEvents, true)) {
                throw new InvalidArgumentException('ui_source_contract_event_mismatch:' . $adapterId . ':' . $event);
            }
        }

        return [
            'id' => $source['id'],
            'version' => $source['version'],
            'manifest_sha256' => $source['manifest_sha256'],
            'contract_schema' => $source['compatibility']['contract_schema'],
            'reuse_mode' => $source['reuse_mode'],
            'lifecycle' => $source['lifecycle'],
            'custom_element' => $source['custom_element'],
        ];
    }

    private function contractDirectory(): string
    {
        return $this->directory ?? __DIR__ . '/../../resources/contracts';
    }

    private function read(string $path, string $kind): string
    {
        $content = is_file($path) ? file_get_contents($path) : false;
        if (!is_string($content)) {
            throw new InvalidArgumentException('ui_source_contract_' . $kind . '_missing');
        }

        return $content;
    }

    /** @return array<string, mixed> */
    private function decode(string $content, string $kind): array
    {
        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new InvalidArgumentException('ui_source_contract_' . $kind . '_invalid');
        }

        return $decoded;
    }
}
