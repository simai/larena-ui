<?php

declare(strict_types=1);

$node = getenv('SIMAI_NODE_BINARY');
if (!is_string($node) || !is_executable($node)) {
    throw new RuntimeException('SIMAI_NODE_BINARY must identify the managed Node runtime for composition registry acceptance.');
}
passthru(escapeshellarg($node).' '.escapeshellarg(__DIR__.'/logical-file-composition-registry.mjs'), $exit);
assert($exit === 0, 'Trusted composition registry rejected its safety tests.');
