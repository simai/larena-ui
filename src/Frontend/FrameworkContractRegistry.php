<?php

declare(strict_types=1);

namespace Larena\Ui\Frontend;

use InvalidArgumentException;
use RuntimeException;

/**
 * Immutable reader for the exact upstream Simai Framework contract artifact.
 *
 * The registry is never merged with Larena metadata. Application-specific
 * adapters live in FrameworkAdapterRegistry and reference these entries by ID.
 */
final readonly class FrameworkContractRegistry
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, array<string, mixed>> $entriesById
     */
    private function __construct(
        private array $data,
        private string $sha256,
        private array $entriesById,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, ?string $sha256 = null): self
    {
        $entries = self::validate($data);
        $sha256 ??= hash(
            'sha256',
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );
        if (preg_match('/^[a-f0-9]{64}$/', $sha256) !== 1) {
            throw new InvalidArgumentException('ui_framework_registry_sha256_invalid');
        }

        return new self($data, $sha256, $entries);
    }

    public static function fromPublishedBundle(FrontendRuntimeLock $lock, string $publicRoot): self
    {
        $contract = $lock->frameworkRegistry();
        $root = self::absoluteDirectory($publicRoot, 'ui_framework_registry_public_root_invalid');
        $bundleRoot = realpath($root . DIRECTORY_SEPARATOR . $lock->bundleId());
        if ($bundleRoot === false || !is_dir($bundleRoot)) {
            throw new RuntimeException('ui_framework_registry_bundle_missing');
        }

        $marker = self::readJson($bundleRoot . DIRECTORY_SEPARATOR . '.larena-bundle.json');
        if (($marker['schema'] ?? null) !== 'larena.core_assets.immutable_bundle.v2'
            || ($marker['publication_profile'] ?? null) !== $lock->publicationProfile()
            || ($marker['bundle_id'] ?? null) !== $lock->bundleId()
        ) {
            throw new RuntimeException('ui_framework_registry_bundle_marker_invalid');
        }
        self::assertContractReceipt($marker, $contract);

        $relativePath = (string) ($contract['relative_path'] ?? '');
        if (!self::isSafeRelativePath($relativePath)) {
            throw new RuntimeException('ui_framework_registry_path_invalid');
        }
        $path = realpath($bundleRoot . DIRECTORY_SEPARATOR . $relativePath);
        if ($path === false
            || !is_file($path)
            || !str_starts_with($path, $bundleRoot . DIRECTORY_SEPARATOR)
        ) {
            throw new RuntimeException('ui_framework_registry_file_missing');
        }

        $expected = (string) ($contract['file_sha256'] ?? '');
        $actual = hash_file('sha256', $path);
        if (!is_string($actual)
            || preg_match('/^[a-f0-9]{64}$/', $expected) !== 1
            || !hash_equals($expected, $actual)
        ) {
            throw new RuntimeException('ui_framework_registry_file_checksum_mismatch');
        }
        $data = self::readJson($path);
        $registry = self::fromArray($data, $actual);
        if ($registry->compatibilityId() !== $lock->pairId()
            || $registry->profile() !== (string) ($contract['profile'] ?? '')
            || ($data['schema_id'] ?? null) !== ($contract['schema_id'] ?? null)
        ) {
            throw new RuntimeException('ui_framework_registry_lock_identity_mismatch');
        }

        return $registry;
    }

    public function compatibilityId(): string
    {
        return (string) $this->data['compatibility']['id'];
    }

    public function profile(): string
    {
        return (string) $this->data['compatibility']['profile'];
    }

    public function sha256(): string
    {
        return $this->sha256;
    }

    /** @return array<string, mixed> */
    public function entry(string $id): array
    {
        return $this->entriesById[$id]
            ?? throw new InvalidArgumentException('ui_framework_registry_entry_unknown:' . $id);
    }

    /** @return list<array<string, mixed>> */
    public function entries(): array
    {
        return array_values($this->entriesById);
    }

    /** @return list<array<string, mixed>> */
    public function transitiveClosure(string $rootId): array
    {
        $this->entry($rootId);
        $seen = [];
        $visiting = [];
        $visit = function (string $id) use (&$visit, &$seen, &$visiting): void {
            if (isset($seen[$id])) {
                return;
            }
            if (isset($visiting[$id])) {
                throw new InvalidArgumentException('ui_framework_registry_relation_cycle:' . $id);
            }
            $visiting[$id] = true;
            $entry = $this->entry($id);
            foreach ($entry['requires'] as $required) {
                $visit((string) $required);
            }
            unset($visiting[$id]);
            $seen[$id] = $entry;
        };
        $visit($rootId);
        ksort($seen, SORT_STRING);

        return array_values($seen);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, array<string, mixed>>
     */
    private static function validate(array $data): array
    {
        if (($data['schema_id'] ?? null) !== 'simai.framework.contract-registry'
            || ($data['schema_version'] ?? null) !== 1
        ) {
            throw new InvalidArgumentException('ui_framework_registry_schema_unknown');
        }
        $compatibility = is_array($data['compatibility'] ?? null) ? $data['compatibility'] : [];
        if (!self::isCompatibilityId((string) ($compatibility['id'] ?? ''))
            || ($compatibility['status'] ?? null) !== 'bounded'
            || ($compatibility['profile'] ?? null) !== 'plain-assets-v1'
            || !is_array($compatibility['runtime_sources'] ?? null)
            || !is_array($compatibility['exclusions'] ?? null)
        ) {
            throw new InvalidArgumentException('ui_framework_registry_compatibility_invalid');
        }
        $claims = is_array($compatibility['claims'] ?? null) ? $compatibility['claims'] : [];
        $nonclaims = is_array($data['nonclaims'] ?? null) ? $data['nonclaims'] : [];
        foreach (['full_compatible', 'production_ready', 'all_items_ready'] as $claim) {
            if (($claims[$claim] ?? null) !== false) {
                throw new InvalidArgumentException('ui_framework_registry_claim_invalid:' . $claim);
            }
        }
        foreach (['production_ready', 'full_compatibility', 'all_items_ready'] as $claim) {
            if (($nonclaims[$claim] ?? null) !== false) {
                throw new InvalidArgumentException('ui_framework_registry_nonclaim_invalid:' . $claim);
            }
        }

        $sourceManifests = $data['source_manifests'] ?? null;
        if (!is_array($sourceManifests) || !array_is_list($sourceManifests)) {
            throw new InvalidArgumentException('ui_framework_registry_source_manifests_invalid');
        }
        $manifestKinds = [];
        foreach ($sourceManifests as $manifest) {
            if (!is_array($manifest)
                || !in_array($manifest['kind'] ?? null, ['utility', 'component', 'smart-component', 'recipe'], true)
                || !self::isOwner((string) ($manifest['owner'] ?? ''))
                || !self::isSafeRelativePath((string) ($manifest['path'] ?? ''))
                || preg_match('/^[a-f0-9]{64}$/', (string) ($manifest['sha256'] ?? '')) !== 1
            ) {
                throw new InvalidArgumentException('ui_framework_registry_source_manifest_invalid');
            }
            $manifestKinds[(string) $manifest['kind']] = true;
        }
        $kinds = array_keys($manifestKinds);
        sort($kinds, SORT_STRING);
        if ($kinds !== ['component', 'recipe', 'smart-component', 'utility']) {
            throw new InvalidArgumentException('ui_framework_registry_source_manifest_kinds_incomplete');
        }

        $entries = $data['entries'] ?? null;
        if (!is_array($entries) || !array_is_list($entries) || $entries === []) {
            throw new InvalidArgumentException('ui_framework_registry_entries_invalid');
        }
        $byId = [];
        $kindCounts = array_fill_keys(['utility', 'component', 'smart-component', 'recipe'], 0);
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                throw new InvalidArgumentException('ui_framework_registry_entry_invalid');
            }
            $id = (string) ($entry['id'] ?? '');
            $kind = (string) ($entry['kind'] ?? '');
            $readiness = $entry['readiness'] ?? null;
            if (!self::isEntryId($id)
                || !isset($kindCounts[$kind])
                || !str_starts_with($id, match ($kind) {
                    'smart-component' => 'smart.',
                    default => $kind . '.',
                })
                || !self::isOwner((string) ($entry['owner'] ?? ''))
                || !in_array($entry['lifecycle'] ?? null, ['released', 'experimental', 'deprecated', 'retired'], true)
                || !is_array($readiness)
                || !in_array($readiness['status'] ?? null, ['ready', 'discoverable', 'blocked'], true)
                || !is_bool($readiness['safe_to_suggest'] ?? null)
                || !is_array($readiness['profiles'] ?? null)
                || !array_is_list($readiness['profiles'])
                || !in_array('plain-assets-v1', $readiness['profiles'], true)
                || !is_array($readiness['blockers'] ?? null)
                || !array_is_list($readiness['blockers'])
                || !is_array($entry['provenance'] ?? null)
                || !is_array($entry['documentation_refs'] ?? null)
                || !array_is_list($entry['documentation_refs'])
                || !is_array($entry['example_refs'] ?? null)
                || !array_is_list($entry['example_refs'])
                || !is_array($entry['runtime'] ?? null)
                || !is_array($entry['requires'] ?? null)
                || !array_is_list($entry['requires'])
                || !is_array($entry['curated_for'] ?? null)
                || !array_is_list($entry['curated_for'])
            ) {
                throw new InvalidArgumentException('ui_framework_registry_entry_invalid:' . $id);
            }
            if (($readiness['status'] !== 'ready' && $readiness['safe_to_suggest'] === true)
                || ($readiness['status'] === 'ready' && $readiness['blockers'] !== [])
            ) {
                throw new InvalidArgumentException('ui_framework_registry_entry_readiness_invalid:' . $id);
            }
            if (isset($byId[$id])) {
                throw new InvalidArgumentException('ui_framework_registry_entry_duplicate:' . $id);
            }
            foreach ($entry['requires'] as $required) {
                if (!is_string($required) || !self::isEntryId($required)) {
                    throw new InvalidArgumentException('ui_framework_registry_relation_invalid:' . $id);
                }
            }
            $byId[$id] = $entry;
            ++$kindCounts[$kind];
        }
        ksort($byId, SORT_STRING);
        foreach ($byId as $id => $entry) {
            foreach ($entry['requires'] as $required) {
                if (!isset($byId[$required])) {
                    throw new InvalidArgumentException('ui_framework_registry_relation_unknown:' . $id . ':' . $required);
                }
            }
        }

        $counts = is_array($data['counts'] ?? null) ? $data['counts'] : [];
        foreach ($kindCounts as $kind => $count) {
            if (($counts[$kind] ?? null) !== $count) {
                throw new InvalidArgumentException('ui_framework_registry_count_mismatch:' . $kind);
            }
        }
        if (($counts['total'] ?? null) !== count($byId)) {
            throw new InvalidArgumentException('ui_framework_registry_count_mismatch:total');
        }

        $indexes = is_array($data['indexes'] ?? null) ? $data['indexes'] : [];
        $byKind = is_array($indexes['by_kind'] ?? null) ? $indexes['by_kind'] : [];
        foreach ($kindCounts as $kind => $_count) {
            $expected = array_keys(array_filter($byId, static fn (array $entry): bool => $entry['kind'] === $kind));
            $actual = $byKind[$kind] ?? null;
            if (!is_array($actual) || !array_is_list($actual) || $actual !== $expected) {
                throw new InvalidArgumentException('ui_framework_registry_index_mismatch:' . $kind);
            }
        }
        foreach (['safe_to_suggest', 'blocked'] as $index) {
            $values = $indexes[$index] ?? null;
            if (!is_array($values) || !array_is_list($values)) {
                throw new InvalidArgumentException('ui_framework_registry_index_invalid:' . $index);
            }
            foreach ($values as $id) {
                if (!is_string($id) || !isset($byId[$id])) {
                    throw new InvalidArgumentException('ui_framework_registry_index_unknown:' . $index);
                }
            }
        }
        $expectedSafe = array_keys(array_filter(
            $byId,
            static fn (array $entry): bool => $entry['readiness']['safe_to_suggest'] === true,
        ));
        $expectedBlocked = array_keys(array_filter(
            $byId,
            static fn (array $entry): bool => $entry['readiness']['status'] === 'blocked',
        ));
        if ($indexes['safe_to_suggest'] !== $expectedSafe) {
            throw new InvalidArgumentException('ui_framework_registry_index_mismatch:safe_to_suggest');
        }
        if ($indexes['blocked'] !== $expectedBlocked) {
            throw new InvalidArgumentException('ui_framework_registry_index_mismatch:blocked');
        }

        $recipeClosures = is_array($indexes['recipe_closure'] ?? null) ? $indexes['recipe_closure'] : [];
        $recipeIds = array_keys(array_filter(
            $byId,
            static fn (array $entry): bool => $entry['kind'] === 'recipe',
        ));
        $indexedRecipeIds = array_keys($recipeClosures);
        sort($indexedRecipeIds, SORT_STRING);
        if ($indexedRecipeIds !== $recipeIds) {
            throw new InvalidArgumentException('ui_framework_registry_recipe_closure_index_incomplete');
        }
        foreach ($recipeClosures as $recipeId => $closure) {
            if (!is_string($recipeId)
                || !isset($byId[$recipeId])
                || $byId[$recipeId]['kind'] !== 'recipe'
                || !is_array($closure)
                || !array_is_list($closure)
            ) {
                throw new InvalidArgumentException('ui_framework_registry_recipe_closure_invalid');
            }
            $seen = [];
            $pending = $byId[$recipeId]['requires'];
            while ($pending !== []) {
                $required = (string) array_pop($pending);
                if (isset($seen[$required])) {
                    continue;
                }
                $seen[$required] = true;
                $pending = [...$pending, ...$byId[$required]['requires']];
            }
            $expectedClosure = array_keys($seen);
            sort($expectedClosure, SORT_STRING);
            if ($closure !== $expectedClosure) {
                throw new InvalidArgumentException('ui_framework_registry_recipe_closure_mismatch:' . $recipeId);
            }
            foreach ($closure as $requiredId) {
                if (($byId[$requiredId]['readiness']['safe_to_suggest'] ?? null) !== true) {
                    throw new InvalidArgumentException('ui_framework_registry_recipe_dependency_not_ready:' . $recipeId . ':' . $requiredId);
                }
            }
        }

        return $byId;
    }

    /** @param array<string, mixed> $marker @param array<string, mixed> $contract */
    private static function assertContractReceipt(array $marker, array $contract): void
    {
        $source = is_array($contract['source'] ?? null) ? $contract['source'] : [];
        $receipts = is_array($marker['sources'] ?? null) ? $marker['sources'] : [];
        foreach ($receipts as $receipt) {
            if (!is_array($receipt) || ($receipt['mount'] ?? null) !== ($source['mount'] ?? null)) {
                continue;
            }
            foreach (['commit', 'tree', 'mount', 'sha256'] as $field) {
                if (($receipt[$field] ?? null) !== ($source[$field] ?? null)) {
                    throw new RuntimeException('ui_framework_registry_bundle_receipt_mismatch:' . $field);
                }
            }

            return;
        }

        throw new RuntimeException('ui_framework_registry_bundle_receipt_missing');
    }

    /** @return array<string, mixed> */
    private static function readJson(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException('ui_framework_registry_json_missing');
        }
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('ui_framework_registry_json_invalid');
        }

        return $data;
    }

    private static function absoluteDirectory(string $path, string $error): string
    {
        $path = realpath($path);
        if ($path === false || !is_dir($path) || !str_starts_with($path, DIRECTORY_SEPARATOR)) {
            throw new InvalidArgumentException($error);
        }

        return $path;
    }

    private static function isCompatibilityId(string $id): bool
    {
        return preg_match('/^sf-v\d+\.\d+\.\d+-[a-f0-9]{8}-[a-f0-9]{8}$/', $id) === 1;
    }

    private static function isEntryId(string $id): bool
    {
        if (preg_match('/^[a-z][a-z0-9-]*(?:\.[a-z][a-z0-9-]*)+$/', $id) !== 1
            || str_contains($id, '_')
            || str_contains($id, 'sf5')
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

    private static function isOwner(string $owner): bool
    {
        return preg_match('/^[a-z0-9][a-z0-9-]*\/[a-z0-9][a-z0-9-]*$/', $owner) === 1;
    }

    private static function isSafeRelativePath(string $path): bool
    {
        return $path !== ''
            && $path === trim($path, '/')
            && !str_contains($path, '..')
            && !str_contains($path, '\\')
            && preg_match('#^[a-zA-Z0-9._/-]+$#', $path) === 1;
    }
}
