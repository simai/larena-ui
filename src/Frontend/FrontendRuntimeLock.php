<?php

declare(strict_types=1);

namespace Larena\Ui\Frontend;

use Larena\Core\Support\SchemaName;
use RuntimeException;

/**
 * The pinned SIMAI Framework runtime: the free release (simai/ui, `ui`), the component packages built over it
 * (`packages`, in lock order) and the release's contract registry.
 *
 * Lock format (schema_version 6):
 *   packages: {<name>: {release, repository, tag, commit, tree, mount, sha256, files, requires_framework,
 *              package_path, package_tree, framework_source, builder, manifest_sha256, receipt_sha256,
 *              licence: {ref, tier}}}
 * A lock written before packages were a list (schema_version 5) holds its one package in a top-level `larena` slot
 * and its Smart tree base in boot.larena_base; it is read as packages.larena (licence Apache-2.0, bundled-larena).
 *
 * Identity, composed in lock order:
 *   pair_id   = ui-<12 of ui commit> + for every package "-<name>-<12 of its commit>"
 *   bundle_id = <pair_id>-registry-<8 of registry sha256>-<publication profile>
 * One package named larena gives the id it always had: ui-<12>-larena-<12>-registry-<8>-exact-git-tree-v2.
 */
final readonly class FrontendRuntimeLock
{
    public const SCHEMA = 'larena.ui.frontend_runtime_lock';
    public const SCHEMA_VERSION = 6;
    /** The lock format whose one package sat in a top-level `larena` slot; still read during the transition. */
    private const LEGACY_SCHEMA_VERSION = 5;
    private const LEGACY_PACKAGE = 'larena';
    /** The licence the package of a legacy lock carries: the components package was Apache-2.0 when it was written. */
    private const LEGACY_LICENCE = ['ref' => 'Apache-2.0', 'tier' => 'bundled-larena'];
    /** Component packages are built from components/ of simai/larena-ui. */
    private const PACKAGE_REPOSITORIES = ['simai/larena-ui'];
    /** Mounts and component owners a package may not be named after. */
    private const RESERVED_NAMES = ['ui', 'contract', 'framework'];

    /** @param array<string, mixed> $data */
    private function __construct(private array $data) {}

    public static function bundled(): self
    {
        $path = dirname(__DIR__, 2) . '/resources/sf/runtime-lock.json';
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('ui_frontend_runtime_lock_invalid');
        }

        return self::fromArray($data);
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $lock = new self(self::normalized($data));
        $lock->assertValid();

        return $lock;
    }

    /**
     * The pair id of a free release and the packages built over it, in lock order.
     *
     * @param array<string, string> $packageCommits name => commit
     */
    public static function composePairId(string $uiCommit, array $packageCommits): string
    {
        $id = 'ui-' . substr($uiCommit, 0, 12);
        foreach ($packageCommits as $name => $commit) {
            $id .= '-' . self::packageRelease((string) $name, $commit);
        }

        return $id;
    }

    public static function composeBundleId(string $pairId, string $registrySha256, string $publicationProfile): string
    {
        return $pairId . '-registry-' . substr($registrySha256, 0, 8) . '-' . $publicationProfile;
    }

    /** A package release is named after the package and its commit: <name>-<12>. */
    public static function packageRelease(string $name, string $commit): string
    {
        return $name . '-' . substr($commit, 0, 12);
    }

    /** The free Framework release and the packages built over it, as one id: ui-<12>-<name>-<12>… */
    public function pairId(): string { return (string) $this->data['pair_id']; }
    /** The free Framework release (ui-<12>); the contract registry names it. */
    public function frameworkId(): string { return (string) $this->data['ui']['release']; }

    /** @return array<string, array<string, mixed>> the pinned component packages, in lock order */
    public function packages(): array { return $this->data['packages']; }

    /** @return array<string, mixed> */
    public function package(string $name): array
    {
        $package = $this->data['packages'][$name] ?? null;
        if (!is_array($package)) {
            throw new RuntimeException('ui_frontend_runtime_package_not_locked:' . $name);
        }

        return $package;
    }

    /** @return array<string, string> name => release (<name>-<12>), in lock order */
    public function packageIds(): array
    {
        return array_map(static fn (array $package): string => (string) $package['release'], $this->data['packages']);
    }

    /** Where a package's Smart tree sits in the published bundle (<mount>/<tree>/); the page declares it before core.js. */
    public function packageBase(string $name): string
    {
        $package = $this->package($name);

        return $package['mount'] . '/' . $package['tree'] . '/';
    }

    /** @return array<string, string> name => base, for SF_BOOT_CONFIG.packages, in lock order */
    public function packageBases(): array
    {
        $bases = [];
        foreach (array_keys($this->data['packages']) as $name) {
            $bases[$name] = $this->packageBase($name);
        }

        return $bases;
    }

    /** @return array{ref:string,tier:string} */
    public function packageLicence(string $name): array
    {
        $licence = $this->package($name)['licence'];

        return ['ref' => (string) $licence['ref'], 'tier' => (string) $licence['tier']];
    }

    /**
     * The `packages` part of SF_BOOT_CONFIG as a JavaScript object literal: {<name>:{base:"<url>"},…} for every
     * pinned package, in lock order. Names are plain identifiers ([a-z][a-z0-9]*), the URLs JSON strings.
     */
    public function bootPackagesScript(string $bundleUrl): string
    {
        $entries = [];
        foreach ($this->packageBases() as $name => $base) {
            $entries[] = $name . ':{base:' . json_encode($bundleUrl . $base, JSON_THROW_ON_ERROR) . '}';
        }

        return '{' . implode(',', $entries) . '}';
    }

    /** Transition alias: the release of the package named larena (step 3 renames it admin). */
    public function larenaId(): string { return (string) $this->package(self::LEGACY_PACKAGE)['release']; }
    /** Transition alias: the base of the package named larena. */
    public function larenaBase(): string { return $this->packageBase(self::LEGACY_PACKAGE); }
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

    /** @return array<string, mixed> the lock in the current shape (a legacy lock comes back with packages) */
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
        $sources = [$this->publicationReceipt($this->data['ui'])];
        foreach ($this->data['packages'] as $package) {
            $sources[] = $this->publicationReceipt($package);
        }
        $sources[] = $this->publicationReceipt($this->data['framework_registry']['source']);

        return [
            'schema' => 'larena.ui.frontend_runtime_artifact',
            'runtime' => 'simai-framework',
            'bundle_id' => $this->bundleId(),
            'publication_profile' => $this->publicationProfile(),
            'sources' => $sources,
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
     * The sources a bundle is built from: the free Framework runtime and its contract registry (exact Git trees of
     * simai/ui) and every component package (the builder's archive, pinned by its hash), in publication order.
     *
     * @param string|array<string, string> $packageArchives name => archive; one string when exactly one package is pinned
     * @return list<array{repository:string,commit:string,tree:string,mount:string,sha256:string}>
     */
    public function publicationSources(string $uiRepository, string|array $packageArchives): array
    {
        $names = array_keys($this->data['packages']);
        if (is_string($packageArchives)) {
            if (count($names) !== 1) {
                throw new RuntimeException('ui_frontend_runtime_package_archive_ambiguous');
            }
            $packageArchives = [$names[0] => $packageArchives];
        }
        $repositories = [$uiRepository];
        foreach ($names as $name) {
            $archive = $packageArchives[$name] ?? null;
            if (!is_string($archive) || $archive === '') {
                throw new RuntimeException('ui_frontend_runtime_package_archive_missing:' . $name);
            }
            $repositories[] = $archive;
        }
        $repositories[] = $uiRepository;

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

    /**
     * The lock in the current shape. A legacy lock (schema_version 5, its one package in a top-level `larena`
     * slot and the package base in boot.larena_base) is read as packages.larena.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function normalized(array $data): array
    {
        if (!SchemaName::is($data, self::SCHEMA, self::LEGACY_SCHEMA_VERSION)) {
            return $data;
        }
        $package = $data[self::LEGACY_PACKAGE] ?? null;
        $boot = $data['boot'] ?? null;
        if (array_key_exists('packages', $data) || !is_array($package) || !is_array($boot)
            || array_key_exists('licence', $package)
            || !is_string($package['mount'] ?? null) || !is_string($package['tree'] ?? null)
            || ($boot['larena_base'] ?? null) !== $package['mount'] . '/' . $package['tree'] . '/'
        ) {
            throw new RuntimeException('ui_frontend_runtime_legacy_lock_invalid');
        }
        unset($boot['larena_base']);
        $normalized = [];
        foreach ($data as $key => $value) {
            if ($key === 'schema') {
                $normalized['schema'] = self::SCHEMA;
                $normalized['schema_version'] = self::SCHEMA_VERSION;
            } elseif ($key === 'schema_version') {
                continue;
            } elseif ($key === self::LEGACY_PACKAGE) {
                $normalized['packages'] = [self::LEGACY_PACKAGE => [...$package, 'licence' => self::LEGACY_LICENCE]];
            } elseif ($key === 'boot') {
                $normalized['boot'] = $boot;
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
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
        if (!SchemaName::is($this->data, self::SCHEMA, self::SCHEMA_VERSION)
            || ($this->data['runtime'] ?? null) !== 'simai-framework'
            || ($this->data['publication_profile'] ?? null) !== 'exact-git-tree-v2'
            || !array_key_exists('tag', $this->data) || $this->data['tag'] !== null
            || preg_match('/^ui-[a-f0-9]{12}(?:-[a-z][a-z0-9]*-[a-f0-9]{12})+$/', (string) ($this->data['pair_id'] ?? '')) !== 1
            || !preg_match('/^[a-z0-9][a-z0-9._-]+$/', (string) ($this->data['bundle_id'] ?? ''))
            || array_key_exists(self::LEGACY_PACKAGE, $this->data)
        ) {
            throw new RuntimeException('ui_frontend_runtime_lock_invalid');
        }
        $ui = $this->data['ui'] ?? null;
        if (!is_array($ui)
            || !array_key_exists('tag', $ui) || $ui['tag'] !== null
            || ($ui['repository'] ?? null) !== 'simai/ui'
            || !$this->validPublicationSource($ui)
        ) {
            throw new RuntimeException('ui_frontend_runtime_source_invalid:ui');
        }
        // The free release lives in simai/ui under distr.
        if (($ui['tree'] ?? null) !== 'distr' || ($ui['mount'] ?? null) !== 'ui') {
            throw new RuntimeException('ui_frontend_runtime_source_layout_invalid');
        }
        $frameworkId = 'ui-' . substr((string) $ui['commit'], 0, 12);
        if (($ui['release'] ?? null) !== $frameworkId) {
            throw new RuntimeException('ui_frontend_runtime_pair_identity_mismatch');
        }

        $packages = $this->data['packages'] ?? null;
        if (!is_array($packages) || $packages === [] || array_is_list($packages)) {
            throw new RuntimeException('ui_frontend_runtime_packages_invalid');
        }
        $packageCommits = [];
        foreach ($packages as $name => $package) {
            $this->assertPackage($name, $package, $frameworkId);
            $packageCommits[$name] = (string) $package['commit'];
        }

        $expectedPairId = self::composePairId((string) $ui['commit'], $packageCommits);
        if ($this->data['pair_id'] !== $expectedPairId) {
            throw new RuntimeException('ui_frontend_runtime_pair_identity_mismatch');
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

        $packageMounts = array_map(static fn (array $package): string => (string) $package['mount'], $packages);
        $mounts = [(string) $ui['mount'], ...array_values($packageMounts), (string) $source['mount']];
        if (count(array_unique($mounts)) !== count($mounts)) {
            throw new RuntimeException('ui_frontend_runtime_source_mount_duplicate');
        }
        if (!$this->insideMount((string) $registry['relative_path'], (string) $source['mount'])) {
            throw new RuntimeException('ui_frontend_framework_registry_path_outside_mount');
        }

        $this->assertRuntimePaths((string) $ui['mount'], $packageMounts);

        $expectedBundleId = self::composeBundleId(
            $expectedPairId,
            (string) $registry['file_sha256'],
            (string) $this->data['publication_profile'],
        );
        if ($this->data['bundle_id'] !== $expectedBundleId) {
            throw new RuntimeException('ui_frontend_runtime_bundle_identity_mismatch');
        }
    }

    /**
     * A component package: the Smart tree the Framework builder made from components/ of its repository, built over
     * exactly the pinned free release, mounted under its own name.
     */
    private function assertPackage(mixed $name, mixed $package, string $frameworkId): void
    {
        if (!is_string($name) || preg_match('/^[a-z][a-z0-9]*$/', $name) !== 1 || in_array($name, self::RESERVED_NAMES, true)) {
            throw new RuntimeException('ui_frontend_runtime_package_name_invalid');
        }
        if (!is_array($package)
            || !array_key_exists('tag', $package) || $package['tag'] !== null
            || !in_array($package['repository'] ?? null, self::PACKAGE_REPOSITORIES, true)
            || !$this->validPublicationSource($package)
        ) {
            throw new RuntimeException('ui_frontend_runtime_source_invalid:' . $name);
        }
        if (($package['tree'] ?? null) !== 'smart' || ($package['mount'] ?? null) !== $name
            || ($package['package_path'] ?? null) !== 'components'
            || !preg_match('/^[a-f0-9]{40}$/', (string) ($package['package_tree'] ?? ''))
            || !preg_match('/^[a-f0-9]{40}$/', (string) ($package['framework_source']['revision'] ?? ''))
            || ($package['framework_source']['repository'] ?? null) !== 'simai/ui-source'
            || !preg_match('/^[a-f0-9]{40}$/', (string) ($package['builder']['revision'] ?? ''))
            || ($package['builder']['repository'] ?? null) !== 'simai/ui-builder'
            || !preg_match('/^[a-f0-9]{64}$/', (string) ($package['manifest_sha256'] ?? ''))
            || !preg_match('/^[a-f0-9]{64}$/', (string) ($package['receipt_sha256'] ?? ''))
        ) {
            throw new RuntimeException('ui_frontend_runtime_source_layout_invalid');
        }
        $licence = $package['licence'] ?? null;
        if (!is_array($licence) || array_keys($licence) !== ['ref', 'tier']
            || preg_match('/^[A-Za-z0-9][A-Za-z0-9.+-]*$/', (string) ($licence['ref'] ?? '')) !== 1
            || preg_match('/^[a-z][a-z0-9-]*$/', (string) ($licence['tier'] ?? '')) !== 1
        ) {
            throw new RuntimeException('ui_frontend_runtime_package_licence_invalid:' . $name);
        }
        if (($package['release'] ?? null) !== self::packageRelease($name, (string) $package['commit'])) {
            throw new RuntimeException('ui_frontend_runtime_pair_identity_mismatch');
        }
        // A package is built over exactly one free release; the two are always pinned together.
        if (($package['requires_framework'] ?? null) !== $frameworkId) {
            throw new RuntimeException('ui_frontend_runtime_package_framework_mismatch:' . $name);
        }
    }

    /** @param array<string, string> $packageMounts name => mount */
    private function assertRuntimePaths(string $uiMount, array $packageMounts): void
    {
        $boot = $this->data['boot'] ?? null;
        if (!is_array($boot) || array_key_exists('larena_base', $boot)) {
            throw new RuntimeException('ui_frontend_runtime_boot_invalid');
        }

        foreach (['css', 'javascript', 'smart_base'] as $field) {
            $path = $boot[$field] ?? null;
            if (!$this->safeRelativePath($path) || !$this->insideMount((string) $path, $uiMount)) {
                throw new RuntimeException('ui_frontend_runtime_boot_path_invalid:' . $field);
            }
        }
        foreach (['ui_base', 'smart_base_path'] as $field) {
            $path = $boot[$field] ?? null;
            if (!$this->safeRelativePath($path, true) || !$this->insideMount((string) $path, $uiMount, true)
                || !str_ends_with((string) $path, '/')
            ) {
                throw new RuntimeException('ui_frontend_runtime_boot_path_invalid:' . $field);
            }
        }

        $components = $this->data['components'] ?? null;
        if (!is_array($components) || $components === []) {
            throw new RuntimeException('ui_frontend_runtime_components_invalid');
        }
        $mounts = [$uiMount, ...array_values($packageMounts)];
        foreach ($components as $tag => $component) {
            // A free component lives in the free Smart tree, a package's one in that package's tree; nothing else.
            $package = is_array($component) ? ($component['package'] ?? null) : null;
            $base = match (true) {
                $package === 'framework' => (string) $boot['smart_base_path'],
                is_string($package) && isset($packageMounts[$package]) => $this->packageBase($package),
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
                    || array_filter($mounts, fn (string $mount): bool => $this->insideMount((string) $css, $mount)) === [])
            ) {
                throw new RuntimeException('ui_frontend_runtime_component_css_invalid:' . $tag);
            }
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
