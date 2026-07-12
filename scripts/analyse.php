<?php

declare(strict_types=1);

$phpstan = 'vendor/bin/phpstan';
if (!is_file($phpstan)) {
    echo "PHPStan is not installed; skipping static analysis until composer install runs.\n";
    exit(0);
}
$memoryLimit = getenv('LARENA_UI_PHPSTAN_MEMORY_LIMIT') ?: '1024M';
passthru(
    escapeshellarg(PHP_BINARY)
    . ' ' . escapeshellarg($phpstan)
    . ' analyse --configuration=phpstan.neon.dist --no-progress --memory-limit='
    . escapeshellarg($memoryLimit),
    $exitCode,
);
exit($exitCode);
