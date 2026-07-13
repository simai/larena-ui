<?php

declare(strict_types=1);

namespace Larena\Ui\Frontend;

use RuntimeException;

final readonly class FrontendRuntimeLock
{
    /** @param array<string, mixed> $data */
    private function __construct(private array $data) {}

    public static function bundled(): self
    {
        $path = dirname(__DIR__, 2) . '/resources/sf/runtime-lock.json';
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('ui_frontend_runtime_lock_invalid');
        }
        $lock = new self($data);
        $lock->assertValid();
        return $lock;
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $lock = new self($data);
        $lock->assertValid();

        return $lock;
    }

    public function pairId(): string { return (string) $this->data['pair_id']; }
    public function bundleId(): string { return (string) $this->data['bundle_id']; }
    public function publicationProfile(): string { return (string) $this->data['publication_profile']; }
    public function tag(): string { return (string) $this->data['tag']; }

    /** @return array<string, mixed> */
    public function frameworkRegistry(): array { return $this->data['framework_registry']; }

    /** @return array<string, mixed> */
    public function component(string $tag): array
    {
        $component = $this->data['components'][$tag] ?? null;
        if (!is_array($component)) {
            throw new RuntimeException('ui_frontend_component_not_locked:' . $tag);
        }
        return $component;
    }

    /** @return array<string, mixed> */
    public function toArray(): array { return $this->data; }

    /** @return list<array{repository:string,commit:string,tree:string,mount:string,sha256:string}> */
    public function publicationSources(string $uiRepository, string $smartRepository): array
    {
        return [
            $this->source($this->data['ui'], $uiRepository),
            $this->source($this->data['ui_smart'], $smartRepository),
            $this->source($this->data['framework_registry']['source'], $uiRepository),
        ];
    }

    /** @return array{repository:string,commit:string,tree:string,mount:string,sha256:string} */
    private function source(mixed $data, string $repository): array
    {
        if (!is_array($data)) {
            throw new RuntimeException('ui_frontend_runtime_source_invalid');
        }
        return [
            'repository' => $repository,
            'commit' => (string) $data['commit'],
            'tree' => (string) $data['tree'],
            'mount' => (string) $data['mount'],
            'sha256' => (string) $data['sha256'],
        ];
    }

    private function assertValid(): void
    {
        if (($this->data['schema'] ?? null) !== 'larena.ui.frontend_runtime_lock.v3'
            || !preg_match('/^[a-z0-9][a-z0-9._-]+$/', (string) ($this->data['pair_id'] ?? ''))
            || !preg_match('/^[a-z0-9][a-z0-9._-]+$/', (string) ($this->data['bundle_id'] ?? ''))
            || ($this->data['publication_profile'] ?? null) !== 'exact-git-tree-v2'
            || !preg_match('/^v\d+\.\d+\.\d+$/', (string) ($this->data['tag'] ?? ''))
        ) {
            throw new RuntimeException('ui_frontend_runtime_lock_invalid');
        }
        foreach (['ui', 'ui_smart'] as $source) {
            $value = $this->data[$source] ?? null;
            if (!is_array($value)
                || !preg_match('/^v\d+\.\d+\.\d+$/', (string) ($value['tag'] ?? ''))
                || !preg_match('/^[a-f0-9]{40}$/', (string) ($value['commit'] ?? ''))
                || !preg_match('/^[a-f0-9]{64}$/', (string) ($value['sha256'] ?? ''))
                || !is_int($value['files'] ?? null)
                || $value['files'] < 1
            ) {
                throw new RuntimeException('ui_frontend_runtime_source_invalid:' . $source);
            }
        }
        if (($this->data['ui']['tag'] ?? null) !== ($this->data['tag'] ?? null)
            || ($this->data['ui']['tree'] ?? null) !== 'distr'
            || ($this->data['ui']['mount'] ?? null) !== 'ui'
            || ($this->data['ui_smart']['tree'] ?? null) !== 'smart'
            || ($this->data['ui_smart']['mount'] ?? null) !== 'smart'
        ) {
            throw new RuntimeException('ui_frontend_runtime_source_layout_invalid');
        }

        $expectedPairId = sprintf(
            'sf-%s-%s-%s',
            (string) $this->data['tag'],
            substr((string) $this->data['ui']['commit'], 0, 8),
            substr((string) $this->data['ui_smart']['commit'], 0, 8),
        );
        if ($this->data['pair_id'] !== $expectedPairId) {
            throw new RuntimeException('ui_frontend_runtime_pair_identity_mismatch');
        }

        $registry = $this->data['framework_registry'] ?? null;
        $source = is_array($registry) && is_array($registry['source'] ?? null)
            ? $registry['source']
            : null;
        if (!is_array($registry)
            || ($registry['schema_id'] ?? null) !== 'simai.framework.contract-registry'
            || ($registry['compatibility_id'] ?? null) !== $expectedPairId
            || ($registry['profile'] ?? null) !== 'plain-assets-v1'
            || ($registry['relative_path'] ?? null) !== 'contract/contracts/generated/framework-contract-registry.json'
            || !preg_match('/^[a-f0-9]{64}$/', (string) ($registry['file_sha256'] ?? ''))
            || !is_array($source)
            || !preg_match('/^[a-f0-9]{40}$/', (string) ($source['commit'] ?? ''))
            || ($source['tree'] ?? null) !== 'contracts/generated'
            || !preg_match('/^[a-f0-9]{40}$/', (string) ($source['tree_oid'] ?? ''))
            || ($source['mount'] ?? null) !== 'contract'
            || !preg_match('/^[a-f0-9]{64}$/', (string) ($source['sha256'] ?? ''))
            || ($source['files'] ?? null) !== 1
        ) {
            throw new RuntimeException('ui_frontend_framework_registry_lock_invalid');
        }
        $expectedBundleId = $expectedPairId
            . '-registry-' . substr((string) $registry['file_sha256'], 0, 8)
            . '-' . $this->data['publication_profile'];
        if ($this->data['bundle_id'] !== $expectedBundleId) {
            throw new RuntimeException('ui_frontend_runtime_bundle_identity_mismatch');
        }
    }
}
