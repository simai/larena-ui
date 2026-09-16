<?php

declare(strict_types=1);

$node = getenv('SIMAI_NODE_BINARY') ?: 'node';
if ($node !== 'node' && !is_executable($node)) {
    throw new RuntimeException('SIMAI_NODE_BINARY must identify the managed Node runtime for composition registry acceptance.');
}
passthru(escapeshellarg($node).' '.escapeshellarg(__DIR__.'/logical-file-composition-registry.mjs'), $exit);
if ($exit !== 0) throw new RuntimeException('Trusted composition registry failed; install Node or set SIMAI_NODE_BINARY to the pinned runtime.');
