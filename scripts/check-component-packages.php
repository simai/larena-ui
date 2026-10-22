<?php

declare(strict_types=1);

/*
 * Check that the frontend runtime lock pins every smart-component set the installed Larena packages declare
 * (composer.json extra.larena.component_packages), that each pinned set suits its Framework release and carries its
 * hashes. Runs without Composer's autoloader, so Root installers and Runtime Delivery can call it on an installed
 * release before it is sealed.
 *
 *   php scripts/check-component-packages.php [--lock=<runtime-lock.json>] [--packages-dir=<dir> …] [--json]
 *
 * --lock          defaults to this package's resources/sf/runtime-lock.json
 * --packages-dir  a directory of package directories (vendor/larena, a workspace's packages/); repeatable;
 *                 defaults to the directory this package sits in
 * Exit 0 when the check passes, 1 when it fails, 64 on bad input.
 */

require_once dirname(__DIR__) . '/src/Frontend/ComponentPackageRequirements.php';

use Larena\Ui\Frontend\ComponentPackageRequirements;

$options = getopt('', ['lock:', 'packages-dir:', 'json']);
$lockPath = is_string($options['lock'] ?? null) ? $options['lock'] : dirname(__DIR__) . '/resources/sf/runtime-lock.json';
$roots = $options['packages-dir'] ?? [dirname(__DIR__, 2)];
$roots = array_values(array_filter(is_array($roots) ? $roots : [$roots], 'is_string'));

try {
    $lock = json_decode((string) file_get_contents($lockPath), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable) {
    $lock = null;
}
if (!is_array($lock) || $roots === []) {
    fwrite(STDERR, "component_package_check_input_invalid\n");
    exit(64);
}

$result = ComponentPackageRequirements::fromPackageDirectories($roots)->check($lock);

if (array_key_exists('json', $options)) {
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
} elseif ($result['errors'] === []) {
    echo 'Component packages ok: ' . implode(', ', array_map(
        static fn (string $name, string $release): string => $name . ' ' . $release,
        array_keys($result['pinned']),
        $result['pinned'],
    )) . ($result['unrequested'] === [] ? '' : '; pinned, declared by no package: ' . implode(', ', $result['unrequested'])) . PHP_EOL;
} else {
    fwrite(STDERR, "Component package check failed:\n- " . implode("\n- ", $result['errors']) . PHP_EOL);
}

exit($result['errors'] === [] ? 0 : 1);
