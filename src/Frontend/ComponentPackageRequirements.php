<?php

declare(strict_types=1);

namespace Larena\Ui\Frontend;

/**
 * Which external smart-component sets (Framework component packages: `larena` today, a chat set next) the installed
 * Larena packages need, checked against the frontend runtime lock that larena/ui pins.
 *
 * A Larena package declares the sets it needs in its composer.json, next to its other install-time wiring:
 *
 *   "extra": {"larena": {"component_packages": {"<set>": {"releases": "*",
 *       "release_repository": "simai/larena-ui", "source_repositories": ["simai/larena-ui", "simai/ui-admin"]}}}}
 *
 *   releases             "*" accepts any release the lock pins; a list (["larena-3f6c86da4dd6", …]) accepts only
 *                        those. Releases are <set>-<12 of commit>: commits have no order, so there is no minimum
 *                        or range.
 *   release_repository   optional; where the pinned set's GitHub release (the archive) must come from
 *                        (component_package_release_repository_mismatch). `repository`, the key declarations used
 *                        before sources and releases were split, is read as release_repository; a declaration
 *                        names one of the two.
 *   source_repositories  optional; the repositories the pinned set may be built from
 *                        (component_package_source_repository_not_accepted).
 *
 * The check fails when a declared set is not pinned, its pinned release is not accepted, comes from another release
 * repository or is built from a source the declaration does not accept, a pinned set is built from a repository
 * outside SOURCE_REPOSITORIES or released outside RELEASE_REPOSITORIES, requires another Framework release than the
 * lock pins, or lacks its archive, manifest or receipt hash. Sets pinned but declared by nobody are allowed and
 * reported (`unrequested`).
 *
 * The lock fields come from the kit's package-receipt.json (simai.framework.product-package-receipt):
 *   repository         package_source.repository                   (component_package_source_repository_invalid)
 *   release_repository where the release is published; absent, the set is released from `repository`
 *                                                                  (component_package_release_repository_invalid)
 *   release            <package>-<12 of package_source.revision>   (component_package_release_invalid)
 *   requires_framework framework.release                           (component_package_framework_mismatch)
 *   sha256             output.archive_sha256                       (component_package_receipt_missing:<set>:sha256)
 *   manifest_sha256    output.manifest_sha256                      (component_package_receipt_missing:<set>:manifest_sha256)
 *   receipt_sha256     the hash of package-receipt.json itself     (component_package_receipt_missing:<set>:receipt_sha256)
 *
 * The class depends on nothing but PHP: Root scripts that run before (or without) Composer's autoloader require
 * this file directly or run scripts/check-component-packages.php.
 */
final class ComponentPackageRequirements
{
    public const SCHEMA = 'larena.ui.component_package_check';
    public const ANY_RELEASE = '*';
    /**
     * The repositories a component set may be built from: package_source.repository of its receipt, `repository` in
     * the lock. The Larena admin set moved its source from components/ of simai/larena-ui to simai/ui-admin (owner
     * decision 2026-10-22); a later set (the chat kit) adds its repository here. FrontendRuntimeLock and the Root
     * release scripts read this one list.
     */
    public const SOURCE_REPOSITORIES = ['simai/larena-ui', 'simai/ui-admin'];
    /**
     * Where the sets are released: the GitHub release <set>-<12> with package-<set>.tar, .sha256 and
     * package-receipt.json. The lock names it in `release_repository`; a lock without it is released from its
     * `repository`, as every lock was before sources and releases were split.
     */
    public const RELEASE_REPOSITORIES = ['simai/larena-ui'];
    private const NAME = '/\A[a-z][a-z0-9]*\z/';
    private const RESERVED_NAMES = ['ui', 'contract', 'framework'];
    private const DECLARATION_KEYS = ['releases', 'repository', 'release_repository', 'source_repositories'];
    private const REPOSITORY = '#\A[a-z0-9][a-z0-9._-]*/[a-z0-9][a-z0-9._-]*\z#';
    private const HASH_FIELDS = ['sha256', 'manifest_sha256', 'receipt_sha256'];

    /**
     * @param array<string, array<string, array{releases:string|list<string>,release_repository:?string,source_repositories:?list<string>}>> $declarations
     *        consumer package => set => declaration
     * @param list<string> $declarationErrors
     */
    private function __construct(private array $declarations, private array $declarationErrors) {}

    /**
     * The declarations of the given Larena packages, read from their composer.json.
     *
     * @param array<mixed> $manifests decoded composer.json documents; packages not named larena/* are skipped
     */
    public static function fromComposerManifests(array $manifests): self
    {
        $declarations = [];
        $errors = [];
        foreach ($manifests as $manifest) {
            if (!is_array($manifest)) {
                continue;
            }
            $consumer = $manifest['name'] ?? null;
            if (!is_string($consumer) || !str_starts_with($consumer, 'larena/')) {
                continue;
            }
            $extra = $manifest['extra'] ?? null;
            $larena = is_array($extra) ? ($extra['larena'] ?? null) : null;
            if (!is_array($larena) || !array_key_exists('component_packages', $larena)) {
                continue;
            }
            $declared = self::parsedDeclarations($larena['component_packages']);
            if ($declared === null) {
                $errors[] = 'component_package_declaration_invalid:' . $consumer;
                continue;
            }
            if ($declared !== []) {
                $declarations[$consumer] = $declared;
            }
        }
        ksort($declarations, SORT_STRING);

        return new self($declarations, array_values(array_unique($errors)));
    }

    /**
     * The composer.json of every package directory under the given roots (vendor/larena, a workspace's packages/).
     * A missing root is skipped; an unreadable composer.json is reported by the check.
     *
     * @param list<string> $roots
     */
    public static function fromPackageDirectories(array $roots): self
    {
        $manifests = [];
        $errors = [];
        foreach ($roots as $root) {
            $entries = is_dir($root) ? scandir($root) : false;
            if ($entries === false) {
                continue;
            }
            foreach ($entries as $entry) {
                $path = rtrim($root, '/') . '/' . $entry . '/composer.json';
                if ($entry === '.' || $entry === '..' || !is_file($path)) {
                    continue;
                }
                try {
                    $manifest = json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    $errors[] = 'component_package_manifest_unreadable:' . $entry;
                    continue;
                }
                $name = is_array($manifest) ? ($manifest['name'] ?? null) : null;
                if (is_string($name) && !isset($manifests[$name])) {
                    $manifests[$name] = $manifest;
                }
            }
        }
        $requirements = self::fromComposerManifests($manifests);

        return new self($requirements->declarations, array_values(array_unique([...$errors, ...$requirements->declarationErrors])));
    }

    /** @return array<string, array<string, array{releases:string|list<string>,release_repository:?string,source_repositories:?list<string>}>> consumer => set => declaration */
    public function declarations(): array
    {
        return $this->declarations;
    }

    /**
     * Where a pinned set is released: its `release_repository`, else (a lock written before sources and releases
     * were split) its `repository`. Null when the lock names neither as a string.
     *
     * @param array<mixed> $package a lock entry (packages.<set>)
     */
    public static function releaseRepository(array $package): ?string
    {
        $repository = array_key_exists('release_repository', $package)
            ? $package['release_repository']
            : ($package['repository'] ?? null);

        return is_string($repository) ? $repository : null;
    }

    /**
     * Check the declarations against a frontend runtime lock (decoded runtime-lock.json, schema_version 6; a
     * schema_version 5 lock holds its one set in a top-level `larena` slot).
     *
     * @param array<mixed> $lock
     * @return array{
     *   schema:string,
     *   status:string,
     *   framework_release:?string,
     *   pinned:array<string,string>,
     *   declared:array<string,list<string>>,
     *   unrequested:list<string>,
     *   errors:list<string>
     * }
     */
    public function check(array $lock): array
    {
        $errors = $this->declarationErrors;
        $packages = self::lockPackages($lock);
        $framework = is_array($lock['ui'] ?? null) ? ($lock['ui']['release'] ?? null) : null;
        $framework = is_string($framework) && $framework !== '' ? $framework : null;
        if ($packages === null || $framework === null) {
            $errors[] = 'component_package_lock_invalid';
            $packages = $packages ?? [];
        }

        $pinned = [];
        foreach ($packages as $name => $package) {
            $release = $package['release'] ?? null;
            $commit = $package['commit'] ?? null;
            $pinned[$name] = is_string($release) ? $release : '';
            if (!is_string($commit) || preg_match('/\A[a-f0-9]{40}\z/', $commit) !== 1
                || $release !== $name . '-' . substr($commit, 0, 12)) {
                $errors[] = 'component_package_release_invalid:' . $name;
            }
            if ($framework !== null && ($package['requires_framework'] ?? null) !== $framework) {
                $errors[] = 'component_package_framework_mismatch:' . $name;
            }
            if (!in_array($package['repository'] ?? null, self::SOURCE_REPOSITORIES, true)) {
                $errors[] = 'component_package_source_repository_invalid:' . $name;
            }
            if (!in_array(self::releaseRepository($package), self::RELEASE_REPOSITORIES, true)) {
                $errors[] = 'component_package_release_repository_invalid:' . $name;
            }
            foreach (self::HASH_FIELDS as $field) {
                if (!is_string($package[$field] ?? null) || preg_match('/\A[a-f0-9]{64}\z/', $package[$field]) !== 1) {
                    $errors[] = 'component_package_receipt_missing:' . $name . ':' . $field;
                }
            }
        }

        $declared = [];
        foreach ($this->declarations as $consumer => $sets) {
            foreach ($sets as $name => $declaration) {
                $declared[$name][] = $consumer;
                $package = $packages[$name] ?? null;
                if ($package === null) {
                    $errors[] = 'component_package_missing:' . $name . ':' . $consumer;
                    continue;
                }
                $releases = $declaration['releases'];
                if ($releases !== self::ANY_RELEASE && !in_array($pinned[$name], $releases, true)) {
                    $errors[] = 'component_package_release_not_accepted:' . $name . ':' . $consumer;
                }
                if ($declaration['release_repository'] !== null
                    && self::releaseRepository($package) !== $declaration['release_repository']) {
                    $errors[] = 'component_package_release_repository_mismatch:' . $name . ':' . $consumer;
                }
                if ($declaration['source_repositories'] !== null
                    && !in_array($package['repository'] ?? null, $declaration['source_repositories'], true)) {
                    $errors[] = 'component_package_source_repository_not_accepted:' . $name . ':' . $consumer;
                }
            }
        }
        ksort($declared, SORT_STRING);
        $errors = array_values(array_unique($errors));

        return [
            'schema' => self::SCHEMA,
            'status' => $errors === [] ? 'passed' : 'failed',
            'framework_release' => $framework,
            'pinned' => $pinned,
            'declared' => $declared,
            'unrequested' => array_values(array_diff(array_keys($pinned), array_keys($declared))),
            'errors' => $errors,
        ];
    }

    /**
     * The pinned sets a page script leaves out of SF_BOOT_CONFIG.packages: a set the page does not declare by its
     * base (<name>:{base:…}) has no loader rule, and its tags do not load.
     *
     * @param array<mixed> $lock
     * @return list<string>
     */
    public static function undeclaredBootPackages(array $lock, string $pageScript): array
    {
        $missing = [];
        foreach (array_keys(self::lockPackages($lock) ?? []) as $name) {
            if (preg_match('/(?<![A-Za-z0-9_$])' . $name . '\s*:\s*\{\s*base\s*:/', $pageScript) !== 1) {
                $missing[] = $name;
            }
        }

        return $missing;
    }

    /**
     * @param array<mixed> $lock
     * @return array<string, array<string, mixed>>|null name => pinned set, in lock order
     */
    private static function lockPackages(array $lock): ?array
    {
        $packages = $lock['packages'] ?? null;
        $legacy = $lock['larena'] ?? null;
        if ($packages === null && is_array($legacy)) {
            $packages = ['larena' => $legacy];
        } elseif (!is_array($packages) || $legacy !== null) {
            return null;
        }
        if ($packages === [] || array_is_list($packages)) {
            return null;
        }
        foreach ($packages as $name => $package) {
            if (!is_string($name) || preg_match(self::NAME, $name) !== 1 || in_array($name, self::RESERVED_NAMES, true)
                || !is_array($package)) {
                return null;
            }
        }

        /** @var array<string, array<string, mixed>> $packages */
        return $packages;
    }

    /** @return array<string, array{releases:string|list<string>,release_repository:?string,source_repositories:?list<string>}>|null */
    private static function parsedDeclarations(mixed $value): ?array
    {
        if (!is_array($value) || ($value !== [] && array_is_list($value))) {
            return null;
        }
        $declarations = [];
        foreach ($value as $name => $declaration) {
            if (!is_string($name) || preg_match(self::NAME, $name) !== 1 || in_array($name, self::RESERVED_NAMES, true)
                || !is_array($declaration) || array_diff(array_keys($declaration), self::DECLARATION_KEYS) !== []) {
                return null;
            }
            $releases = $declaration['releases'] ?? null;
            if ($releases !== self::ANY_RELEASE) {
                if (!is_array($releases) || $releases === [] || !array_is_list($releases)) {
                    return null;
                }
                foreach ($releases as $release) {
                    if (!is_string($release) || preg_match('/\A' . $name . '-[a-f0-9]{12}\z/', $release) !== 1) {
                        return null;
                    }
                }
                $releases = array_values(array_unique($releases));
            }
            // `repository` is the key declarations used before sources and releases were split: the release one.
            if (array_key_exists('repository', $declaration) && array_key_exists('release_repository', $declaration)) {
                return null;
            }
            $release = $declaration['release_repository'] ?? $declaration['repository'] ?? null;
            if ($release !== null && (!is_string($release) || preg_match(self::REPOSITORY, $release) !== 1)) {
                return null;
            }
            $sources = $declaration['source_repositories'] ?? null;
            if ($sources !== null) {
                if (!is_array($sources) || $sources === [] || !array_is_list($sources)) {
                    return null;
                }
                foreach ($sources as $source) {
                    if (!is_string($source) || preg_match(self::REPOSITORY, $source) !== 1) {
                        return null;
                    }
                }
                $sources = array_values(array_unique($sources));
            }
            $declarations[$name] = ['releases' => $releases, 'release_repository' => $release, 'source_repositories' => $sources];
        }

        return $declarations;
    }
}
