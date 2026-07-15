<?php

declare(strict_types=1);

namespace Larena\Ui\Frontend;

use RuntimeException;

final readonly class SourceBackedAssetProvenanceLock
{
    public const RELATIVE_PATH = 'resources/assets/source-backed-sf/provenance-lock.json';

    /** @param array<string, mixed> $data */
    private function __construct(private array $data) {}

    public static function bundled(): self
    {
        $path = dirname(__DIR__, 2) . '/' . self::RELATIVE_PATH;
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('ui_source_backed_asset_provenance_lock_invalid');
        }

        return self::fromArray($data);
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $lock = new self($data);
        $lock->assertValid();

        return $lock;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }

    /** @return array<string, array<string, mixed>> */
    public function assets(string $scope = '', string $slice = ''): array
    {
        return $this->filterRecords($this->data['assets'], $scope, $slice);
    }

    /** @return array<string, array<string, mixed>> */
    public function examples(string $scope = '', string $slice = '', string $surface = ''): array
    {
        $records = $this->filterRecords($this->data['examples'], $scope, $slice);

        if ($surface === '') {
            return $records;
        }

        return array_filter(
            $records,
            static fn (array $record): bool => ($record['surface'] ?? null) === $surface,
        );
    }

    /** @return array<string, mixed> */
    public function source(string $source): array
    {
        $record = $this->data['sources'][$source] ?? null;
        if (!is_array($record)) {
            throw new RuntimeException('ui_source_backed_asset_provenance_source_unknown:' . $source);
        }

        return $record;
    }

    /**
     * Required, portable package verification. It never reads sibling source
     * repositories and succeeds only when every vendored byte matches this lock.
     *
     * @return array<string, mixed>
     */
    public function verifyBundledAssets(?string $packageRoot = null): array
    {
        $packageRoot ??= dirname(__DIR__, 2);
        $checks = [];
        $matched = 0;

        foreach ($this->assets() as $key => $asset) {
            $path = rtrim($packageRoot, '/') . '/' . $asset['package_resource_path'];
            $exists = is_file($path);
            $actualSha = $exists ? hash_file('sha256', $path) : null;
            $matches = is_string($actualSha) && hash_equals((string) $asset['sha256'], $actualSha);
            if ($matches) {
                $matched++;
            }

            $checks[$key] = [
                'package_resource_path' => $asset['package_resource_path'],
                'source_ref' => $this->sourceReference($asset),
                'expected_sha256' => $asset['sha256'],
                'actual_sha256' => $actualSha,
                'exists' => $exists,
                'matches_provenance' => $matches,
            ];
        }

        return [
            'schema' => 'larena.ui.source_backed_asset_package_verification.v1',
            'status' => $matched === count($checks) && $checks !== [] ? 'passed' : 'failed',
            'provenance_lock' => self::RELATIVE_PATH,
            'asset_count' => count($checks),
            'matched_count' => $matched,
            'checks' => $checks,
        ];
    }

    /**
     * Optional developer audit. Roots come only from the explicit environment
     * contract (or an explicit test argument); no workspace/sibling guessing is
     * allowed. Missing roots are reported as unavailable and never invalidate
     * the required vendored-asset contract.
     *
     * @param array<string, string>|null $roots
     * @return array<string, mixed>
     */
    public function auditExternalSources(?array $roots = null): array
    {
        $roots ??= $this->environmentRoots();
        $sourceChecks = [];
        $requested = false;
        $available = true;

        foreach ($this->data['sources'] as $key => $source) {
            $root = trim((string) ($roots[$key] ?? ''));
            $configured = $root !== '';
            $requested = $requested || $configured;
            $rootExists = $configured && is_dir($root);
            $available = $available && $rootExists;
            $sourceChecks[$key] = [
                'repository' => $source['repository'],
                'revision' => $source['revision'],
                'environment' => $source['environment'],
                'configured' => $configured,
                'available' => $rootExists,
            ];
        }

        if (!$requested || !$available) {
            return [
                'schema' => 'larena.ui.source_backed_external_source_audit.v1',
                'status' => 'source_audit_unavailable',
                'requested' => $requested,
                'reason' => !$requested ? 'not_requested' : 'one_or_more_roots_unavailable',
                'sources' => $sourceChecks,
                'asset_checks' => [],
                'example_checks' => [],
            ];
        }

        $assetChecks = $this->externalRecordChecks($this->assets(), $roots);
        $exampleChecks = $this->externalRecordChecks($this->examples(), $roots);
        $passed = $this->checksPassed($assetChecks) && $this->checksPassed($exampleChecks);

        return [
            'schema' => 'larena.ui.source_backed_external_source_audit.v1',
            'status' => $passed ? 'passed' : 'failed',
            'requested' => true,
            'reason' => $passed ? 'all_locked_sources_match' : 'source_drift_detected',
            'sources' => $sourceChecks,
            'asset_checks' => $assetChecks,
            'example_checks' => $exampleChecks,
        ];
    }

    /** @return array<string, string> */
    private function environmentRoots(): array
    {
        $roots = [];

        foreach ($this->data['sources'] as $key => $source) {
            $value = getenv((string) $source['environment']);
            $roots[$key] = is_string($value) ? $value : '';
        }

        return $roots;
    }

    /**
     * @param array<string, array<string, mixed>> $records
     * @param array<string, string> $roots
     * @return array<string, array<string, mixed>>
     */
    private function externalRecordChecks(array $records, array $roots): array
    {
        $checks = [];

        foreach ($records as $key => $record) {
            $root = rtrim((string) ($roots[$record['source']] ?? ''), '/');
            $path = $root . '/' . $record['source_path'];
            $exists = is_file($path);
            $actualSha = $exists ? hash_file('sha256', $path) : null;
            $matches = is_string($actualSha) && hash_equals((string) $record['sha256'], $actualSha);
            $checks[$key] = [
                'source_ref' => $this->sourceReference($record),
                'expected_sha256' => $record['sha256'],
                'actual_sha256' => $actualSha,
                'exists' => $exists,
                'matches_provenance' => $matches,
            ];
        }

        return $checks;
    }

    /**
     * @param array<string, array<string, mixed>> $checks
     */
    private function checksPassed(array $checks): bool
    {
        if ($checks === []) {
            return false;
        }

        foreach ($checks as $check) {
            if (($check['exists'] ?? null) !== true || ($check['matches_provenance'] ?? null) !== true) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, mixed> $record */
    public function sourceReference(array $record): string
    {
        $source = $this->source((string) $record['source']);

        return $source['repository'] . '@' . $source['revision'] . ':' . $record['source_path'];
    }

    /**
     * @param array<string, array<string, mixed>> $records
     * @return array<string, array<string, mixed>>
     */
    private function filterRecords(array $records, string $scope, string $slice): array
    {
        return array_filter(
            $records,
            static fn (array $record): bool => ($scope === '' || ($record['scope'] ?? null) === $scope)
                && ($slice === '' || ($record['slice'] ?? null) === $slice),
        );
    }

    private function assertValid(): void
    {
        if (($this->data['schema'] ?? null) !== 'larena.ui.source_backed_asset_provenance_lock.v1'
            || ($this->data['runtime'] ?? null) !== 'simai-framework'
            || ($this->data['contract']['required_validation'] ?? null) !== 'vendored_assets_match_tracked_provenance'
            || ($this->data['contract']['external_source_audit'] ?? null) !== 'opt_in'
            || ($this->data['contract']['missing_external_sources'] ?? null) !== 'source_audit_unavailable'
        ) {
            throw new RuntimeException('ui_source_backed_asset_provenance_lock_invalid');
        }

        $sources = $this->data['sources'] ?? null;
        if (!is_array($sources) || array_keys($sources) !== ['ui', 'ui_smart', 'ui_play']) {
            throw new RuntimeException('ui_source_backed_asset_provenance_sources_invalid');
        }
        foreach ($sources as $key => $source) {
            if (!is_array($source)
                || !preg_match('#^https://github\.com/simai/[a-z0-9-]+\.git$#', (string) ($source['repository'] ?? ''))
                || !preg_match('/^[a-f0-9]{40}$/', (string) ($source['revision'] ?? ''))
                || !preg_match('/^LARENA_SIMAI_FRAMEWORK_[A-Z_]+_ROOT$/', (string) ($source['environment'] ?? ''))
            ) {
                throw new RuntimeException('ui_source_backed_asset_provenance_source_invalid:' . $key);
            }
        }

        $assets = $this->data['assets'] ?? null;
        $examples = $this->data['examples'] ?? null;
        if (!is_array($assets) || $assets === [] || !is_array($examples) || $examples === []) {
            throw new RuntimeException('ui_source_backed_asset_provenance_records_missing');
        }

        $packagePaths = [];
        foreach ($assets as $key => $asset) {
            $this->assertRecord($key, $asset, true, $sources);
            $packagePath = (string) $asset['package_resource_path'];
            if (isset($packagePaths[$packagePath])) {
                throw new RuntimeException('ui_source_backed_asset_provenance_package_path_duplicate:' . $key);
            }
            $packagePaths[$packagePath] = true;
        }
        foreach ($examples as $key => $example) {
            $this->assertRecord($key, $example, false, $sources);
            if (($example['source'] ?? null) !== 'ui_play') {
                throw new RuntimeException('ui_source_backed_asset_provenance_example_source_invalid:' . $key);
            }
        }
    }

    /**
     * @param mixed $record
     * @param array<string, mixed> $sources
     */
    private function assertRecord(string $key, mixed $record, bool $asset, array $sources): void
    {
        if (!is_array($record)
            || !in_array($record['scope'] ?? null, ['button_proof', 'catalog'], true)
            || !in_array($record['slice'] ?? null, ['buttons', 'tags', 'pagination'], true)
            || !in_array($record['surface'] ?? null, ['component', 'smart_component'], true)
            || !isset($sources[$record['source'] ?? ''])
            || !$this->safeRelativePath((string) ($record['source_path'] ?? ''))
            || !preg_match('/^[a-f0-9]{64}$/', (string) ($record['sha256'] ?? ''))
        ) {
            throw new RuntimeException('ui_source_backed_asset_provenance_record_invalid:' . $key);
        }

        if ($asset && (!preg_match('/^[a-z_]+$/', (string) ($record['asset_id'] ?? ''))
            || !$this->safeRelativePath((string) ($record['package_resource_path'] ?? ''))
            || !str_starts_with((string) $record['package_resource_path'], 'resources/assets/source-backed-sf/'))
        ) {
            throw new RuntimeException('ui_source_backed_asset_provenance_asset_invalid:' . $key);
        }
    }

    private function safeRelativePath(string $path): bool
    {
        return $path !== ''
            && !str_starts_with($path, '/')
            && !str_contains($path, '\\')
            && !in_array('..', explode('/', $path), true);
    }
}
