<?php

declare(strict_types=1);

$studioRoot = getenv('SIMAI_UI_STUDIO_REPO');
if (!is_string($studioRoot) || trim($studioRoot) === '') {
    $studioRoot = dirname(__DIR__, 4) . '/ui-studio';
}
$loaderRoot = getenv('SIMAI_UI_LOADER_REPO');
if (!is_string($loaderRoot) || trim($loaderRoot) === '') {
    $loaderRoot = dirname(__DIR__, 4) . '/ui-loader';
}
$node = getenv('SIMAI_NODE_BINARY');
if (!is_string($node) || trim($node) === '') {
    $node = 'node';
}

$command = [
    $node,
    rtrim($studioRoot, '/') . '/checks/verify-consumer-parity.mjs',
    '--consumer',
    'larena-ui',
    '--larena-ui-root',
    dirname(__DIR__) . '/resources/contracts',
    '--source-registry',
    rtrim($loaderRoot, '/') . '/contracts/generated/smart-component-contract-registry.json',
];
$process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $studioRoot);
if (!is_resource($process)) {
    throw new RuntimeException('ui_studio_verifier_start_failed');
}
$status = proc_close($process);
if ($status !== 0) {
    throw new RuntimeException('ui_studio_verifier_failed:' . $status);
}
