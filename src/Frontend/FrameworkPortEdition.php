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
        usort($editions, 'version_compare');

        return self::$editions[$port] = end($editions);
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
