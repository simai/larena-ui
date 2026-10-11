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

    /** The free Framework release and the Pro release built against it, as one id: ui-<12>-pro-<12>. */
    public function pairId(): string { return (string) $this->data['pair_id']; }
    /** The free Framework release (ui-<12>); the contract registry names it. */
    public function frameworkId(): string { return (string) $this->data['ui']['release']; }
    /** The SIMAI Framework Pro release (pro-<12>): sf-table, sf-data-view, sf-property-view, sf-admin-menu. */
    public function proId(): string { return (string) $this->data['pro']['release']; }
    /** Where the Pro package's Smart tree sits in the published bundle; the page declares it before core.js. */
    public function proBase(): string { return (string) $this->data['boot']['pro_base']; }
    /** Where the free Framework's Smart tree sits in the published bundle. */
    public function smartBase(): string { return (string) $this->data['boot']['smart_base_path']; }
    /** Where the free Framework's runtime (core, components, utilities) sits in the published bundle. */
    public function uiBase(): string { return (string) $this->data['boot']['ui_base']; }
    public function bundleId(): string { return (string) $this->data['bundle_id']; }
    public function publicationProfile(): string { return (string) $this->data['publication_profile']; }
    public function tag(): ?string
    {
        $tag = $this->data['tag'] ?? null;

        return is_string($tag) ? $tag : null;
    }

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

    /**
     * @return array{
     *   schema:string,
     *   runtime:string,
     *   bundle_id:string,
     *   publication_profile:string,
     *   sources:list<array{commit:string,tree:string,mount:string,archive_sha256:string,files:int}>
     * }
     */
    public function publicationExpectation(): array
    {
        return [
            'schema' => 'larena.ui.frontend_runtime_artifact.v1',
            'runtime' => 'simai-framework',
            'bundle_id' => $this->bundleId(),
            'publication_profile' => $this->publicationProfile(),
            'sources' => [
                $this->publicationReceipt($this->data['ui']),
                $this->publicationReceipt($this->data['pro']),
                $this->publicationReceipt($this->data['framework_registry']['source']),
            ],
        ];
    }

    /** @return list<string> */
    public function requiredRuntimeFiles(): array
    {
        $boot = $this->data['boot'];
        $registry = $this->data['framework_registry'];
        $files = [
            (string) $boot['css'],
            (string) $boot['javascript'],
            (string) $boot['smart_base'],
            (string) $registry['relative_path'],
        ];

        foreach ($this->data['components'] as $component) {
            $files[] = (string) $component['javascript'];
            if ($component['css'] !== null) {
                $files[] = (string) $component['css'];
            }
        }

        return array_values(array_unique($files));
    }

    /**
     * The three exact Git trees a bundle is built from: the free Framework runtime, the Pro Smart tree and
     * the free Framework's contract registry. The Pro repository is private; only the build reads it.
     *
     * @return list<array{repository:string,commit:string,tree:string,mount:string,sha256:string}>
     */
    public function publicationSources(string $uiRepository, string $proRepository): array
    {
        $repositories = [$uiRepository, $proRepository, $uiRepository];

        return array_map(
            static fn (array $source, int $index): array => [
                'repository' => $repositories[$index],
                'commit' => $source['commit'],
                'tree' => $source['tree'],
                'mount' => $source['mount'],
                'sha256' => $source['archive_sha256'],
            ],
            $this->publicationExpectation()['sources'],
            array_keys($repositories),
        );
    }

    /** @return array{commit:string,tree:string,mount:string,archive_sha256:string,files:int} */
    private function publicationReceipt(mixed $data): array
    {
        if (!is_array($data)) {
            throw new RuntimeException('ui_frontend_runtime_source_invalid');
        }

        return [
            'commit' => (string) $data['commit'],
            'tree' => (string) $data['tree'],
            'mount' => (string) $data['mount'],
            'archive_sha256' => (string) $data['sha256'],
            'files' => (int) $data['files'],
        ];
    }

    private function assertValid(): void
    {
        if (($this->data['schema'] ?? null) !== 'larena.ui.frontend_runtime_lock.v4'
            || ($this->data['runtime'] ?? null) !== 'simai-framework'
            || ($this->data['publication_profile'] ?? null) !== 'exact-git-tree-v2'
            || !array_key_exists('tag', $this->data) || $this->data['tag'] !== null
            || preg_match('/^ui-[a-f0-9]{12}-pro-[a-f0-9]{12}$/', (string) ($this->data['pair_id'] ?? '')) !== 1
            || !preg_match('/^[a-z0-9][a-z0-9._-]+$/', (string) ($this->data['bundle_id'] ?? ''))
        ) {
            throw new RuntimeException('ui_frontend_runtime_lock_invalid');
        }
        foreach (['ui' => 'simai/ui', 'pro' => 'simai/ui-pro'] as $source => $repository) {
            $value = $this->data[$source] ?? null;
            if (!is_array($value)
                || !array_key_exists('tag', $value) || $value['tag'] !== null
                || ($value['repository'] ?? null) !== $repository
                || !$this->validPublicationSource($value)
            ) {
                throw new RuntimeException('ui_frontend_runtime_source_invalid:' . $source);
            }
        }
        $ui = $this->data['ui'];
        $pro = $this->data['pro'];
        // The free release lives in simai/ui under distr; Pro is a Smart tree of its own.
        if (($ui['tree'] ?? null) !== 'distr' || ($ui['mount'] ?? null) !== 'ui'
            || ($pro['tree'] ?? null) !== 'smart' || ($pro['mount'] ?? null) !== 'pro'
        ) {
            throw new RuntimeException('ui_frontend_runtime_source_layout_invalid');
        }

        $frameworkId = 'ui-' . substr((string) $ui['commit'], 0, 12);
        $proId = 'pro-' . substr((string) $pro['commit'], 0, 12);
        $expectedPairId = $frameworkId . '-' . $proId;
        if (($ui['release'] ?? null) !== $frameworkId
            || ($pro['release'] ?? null) !== $proId
            || $this->data['pair_id'] !== $expectedPairId
        ) {
            throw new RuntimeException('ui_frontend_runtime_pair_identity_mismatch');
        }
        // A Pro release is built against exactly one free release; the two are always pinned together.
        if (($pro['requires_framework'] ?? null) !== $frameworkId) {
            throw new RuntimeException('ui_frontend_runtime_pro_framework_mismatch');
        }

        $registry = $this->data['framework_registry'] ?? null;
        $source = is_array($registry) && is_array($registry['source'] ?? null)
            ? $registry['source']
            : null;
        if (!is_array($registry)
            || ($registry['schema_id'] ?? null) !== 'simai.framework.contract-registry'
            || ($registry['compatibility_id'] ?? null) !== $frameworkId
            || ($registry['profile'] ?? null) !== 'plain-assets-v1'
            || ($registry['relative_path'] ?? null) !== 'contract/contracts/generated/framework-contract-registry.json'
            || !$this->safeRelativePath($registry['relative_path'])
            || !preg_match('/^[a-f0-9]{64}$/', (string) ($registry['file_sha256'] ?? ''))
            || !is_array($source)
            || !$this->validPublicationSource($source)
            || ($source['tree'] ?? null) !== 'contracts/generated'
            || !preg_match('/^[a-f0-9]{40}$/', (string) ($source['tree_oid'] ?? ''))
            || ($source['mount'] ?? null) !== 'contract'
        ) {
            throw new RuntimeException('ui_frontend_framework_registry_lock_invalid');
        }

        $mounts = [(string) $ui['mount'], (string) $pro['mount'], (string) $source['mount']];
        if (count(array_unique($mounts)) !== count($mounts)) {
            throw new RuntimeException('ui_frontend_runtime_source_mount_duplicate');
        }
        if (!$this->insideMount((string) $registry['relative_path'], (string) $source['mount'])) {
            throw new RuntimeException('ui_frontend_framework_registry_path_outside_mount');
        }

        $this->assertRuntimePaths((string) $ui['mount'], (string) $pro['mount']);

        $expectedBundleId = $expectedPairId
            . '-registry-' . substr((string) $registry['file_sha256'], 0, 8)
            . '-' . $this->data['publication_profile'];
        if ($this->data['bundle_id'] !== $expectedBundleId) {
            throw new RuntimeException('ui_frontend_runtime_bundle_identity_mismatch');
        }
    }

    /** @param array<string, mixed> $source */
    private function validPublicationSource(array $source): bool
    {
        return preg_match('/^[a-f0-9]{40}$/', (string) ($source['commit'] ?? '')) === 1
            && $this->safeRelativePath($source['tree'] ?? null)
            && $this->safeMount($source['mount'] ?? null)
            && preg_match('/^[a-f0-9]{64}$/', (string) ($source['sha256'] ?? '')) === 1
            && is_int($source['files'] ?? null)
            && $source['files'] > 0;
    }

    private function assertRuntimePaths(string $uiMount, string $proMount): void
    {
        $boot = $this->data['boot'] ?? null;
        if (!is_array($boot)) {
            throw new RuntimeException('ui_frontend_runtime_boot_invalid');
        }

        foreach (['css', 'javascript', 'smart_base'] as $field) {
            $path = $boot[$field] ?? null;
            if (!$this->safeRelativePath($path) || !$this->insideMount((string) $path, $uiMount)) {
                throw new RuntimeException('ui_frontend_runtime_boot_path_invalid:' . $field);
            }
        }
        foreach (['ui_base' => $uiMount, 'smart_base_path' => $uiMount, 'pro_base' => $proMount] as $field => $mount) {
            $path = $boot[$field] ?? null;
            if (!$this->safeRelativePath($path, true) || !$this->insideMount((string) $path, $mount, true)
                || !str_ends_with((string) $path, '/')
            ) {
                throw new RuntimeException('ui_frontend_runtime_boot_path_invalid:' . $field);
            }
        }

        $components = $this->data['components'] ?? null;
        if (!is_array($components) || $components === []) {
            throw new RuntimeException('ui_frontend_runtime_components_invalid');
        }
        foreach ($components as $tag => $component) {
            // A free component lives in the free Smart tree, a Pro one in the Pro tree; nothing else.
            $package = is_array($component) ? ($component['package'] ?? null) : null;
            $base = match ($package) {
                'framework' => (string) $boot['smart_base_path'],
                'pro' => (string) $boot['pro_base'],
                default => null,
            };
            if (!is_string($tag)
                || preg_match('/^sf-[a-z0-9]+(?:-[a-z0-9]+)*$/', $tag) !== 1
                || !is_array($component)
                || $base === null
                || !$this->safeRelativePath($component['source'] ?? null)
                || !str_starts_with((string) ($component['source'] ?? ''), $base)
                || !$this->safeRelativePath($component['javascript'] ?? null)
                || !str_starts_with((string) ($component['javascript'] ?? ''), $base)
                || !array_key_exists('css', $component)
            ) {
                throw new RuntimeException('ui_frontend_runtime_component_invalid:' . (string) $tag);
            }

            $css = $component['css'];
            if ($css !== null
                && (!$this->safeRelativePath($css)
                    || (!$this->insideMount((string) $css, $proMount)
                        && !$this->insideMount((string) $css, $uiMount)))
            ) {
                throw new RuntimeException('ui_frontend_runtime_component_css_invalid:' . $tag);
            }
        }
    }

    private function safeMount(mixed $mount): bool
    {
        return is_string($mount)
            && preg_match('/^[a-z0-9][a-z0-9._-]*$/', $mount) === 1;
    }

    private function safeRelativePath(mixed $path, bool $allowTrailingSlash = false): bool
    {
        if (!is_string($path)
            || $path === ''
            || str_starts_with($path, '/')
            || str_contains($path, '\\')
            || str_contains($path, "\0")
            || str_contains($path, '//')
            || (!$allowTrailingSlash && str_ends_with($path, '/'))
            || preg_match('#^[A-Za-z0-9._/-]+$#', $path) !== 1
        ) {
            return false;
        }

        $normalized = rtrim($path, '/');
        if ($normalized === '') {
            return false;
        }
        foreach (explode('/', $normalized) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }

    private function insideMount(string $path, string $mount, bool $allowMountRoot = false): bool
    {
        $normalized = rtrim($path, '/');

        return ($allowMountRoot && $normalized === $mount)
            || str_starts_with($normalized, $mount . '/');
    }
}
