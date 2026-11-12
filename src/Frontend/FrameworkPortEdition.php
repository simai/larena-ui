<?php

declare(strict_types=1);

namespace Larena\Ui\Frontend;

use Illuminate\Container\Container;
use RuntimeException;

/**
 * The edition of a Framework port Larena speaks: the latest one the pinned Framework release ships
 * (distr/core/contracts/ports/<port>/<edition>). Names carry no version (owner decision 2026-10-18): a host never writes
 * an edition by hand; a new pinned pair brings the new edition, and the port kit of that edition runs in the gates.
 */
final class FrameworkPortEdition
{
    /** The ports Larena hosts serve, as the admin page announces them. */
    public const PORTS = ['simai.dataview-port', 'simai.property-view-port', 'simai.admin-navigation-port'];

    /** @var array<string, string> */
    private static array $editions = [];

    public static function current(string $port): string
    {
        if (isset(self::$editions[$port])) {
            return self::$editions[$port];
        }
        if (preg_match('/^simai\.[a-z][a-z0-9-]{1,63}$/', $port) !== 1) {
            throw new RuntimeException('framework_port_name_invalid');
        }
        $editions = array_values(array_filter(array_map('basename', glob(self::root().'/distr/core/contracts/ports/'.$port.'/*', GLOB_ONLYDIR) ?: []),
            static fn (string $edition): bool => preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', $edition) === 1));
        if ($editions === []) {
            throw new RuntimeException('framework_port_edition_missing:'.$port);
        }
        // The component that implements the port's newest edition in the pinned release names the editions it accepts
        // (component.json x-port.supports); the page speaks the newest of them the release ships, so a free release
        // ahead of its Pro pair does not break the views. Older components that stay on an early edition are left out.
        foreach (self::componentSupports($port) as $supports) {
            $editions = array_values(array_intersect($editions, $supports));
        }
        if ($editions === []) {
            throw new RuntimeException('framework_port_edition_unsupported:'.$port);
        }
        usort($editions, 'version_compare');

        return self::$editions[$port] = end($editions);
    }

    /** @return list<list<string>> the editions accepted by the components implementing the port's newest edition */
    private static function componentSupports(string $port): array
    {
        $bundle = dirname(self::root());
        $lists = [];
        foreach (glob($bundle.'/{ui/distr,pro,chart}/smart/*/component.json', GLOB_BRACE) ?: [] as $file) {
            $component = json_decode((string) file_get_contents($file), true);
            $contract = $component['port']['contract'] ?? null;
            $supports = $component['x-port']['supports'] ?? null;
            if (!is_string($contract) || !str_contains($contract, '/ports/'.$port.'/') || !is_array($supports) || $supports === []) {
                continue;
            }
            $lists[basename(dirname($contract))][] = array_values(array_filter($supports, 'is_string'));
        }
        if ($lists === []) {
            return [];
        }
        uksort($lists, 'version_compare');

        return end($lists);
    }

    /** @return array<string, string> every port Larena serves and its edition */
    public static function all(): array
    {
        $all = [];
        foreach (self::PORTS as $port) {
            try {
                $all[$port] = self::current($port);
            } catch (RuntimeException) {
            }
        }

        return $all;
    }

    /** The pinned release's ui root: the published bundle in the application, or SIMAI_UI_ROOT where there is none. */
    private static function root(): string
    {
        $container = Container::getInstance();
        if ($container->bound('path.public')) {
            return rtrim((string) $container->make('path.public'), '/').'/larena/assets/sf/'.FrontendRuntimeLock::bundled()->bundleId().'/ui';
        }
        $root = getenv('SIMAI_UI_ROOT');
        if (!is_string($root) || $root === '') {
            throw new RuntimeException('framework_port_root_missing');
        }

        return rtrim($root, '/');
    }
}
