<?php

declare(strict_types=1);

$loaderRoot = getenv('SIMAI_UI_LOADER_REPO');
if (!is_string($loaderRoot) || trim($loaderRoot) === '') {
    $loaderRoot = dirname(__DIR__, 4) . '/ui-loader';
}
$loaderRoot = rtrim($loaderRoot, '/');
$target = dirname(__DIR__) . '/resources/contracts';
$registryPath = $loaderRoot . '/contracts/generated/smart-component-contract-registry.json';

/** @return string */
$read = static function (string $path): string {
    $content = is_file($path) ? file_get_contents($path) : false;
    if (!is_string($content)) {
        throw new RuntimeException('smart_contract_source_missing:' . basename($path));
    }

    return $content;
};
$write = static function (string $path, string $content): void {
    if (file_put_contents($path, $content) !== strlen($content)) {
        throw new RuntimeException('smart_contract_materialization_write_failed:' . basename($path));
    }
};

$registryContent = $read($registryPath);
$registry = json_decode($registryContent, true, 512, JSON_THROW_ON_ERROR);
if (!is_array($registry)
    || ($registry['schema'] ?? null) !== 'simai.smart-component-contract-registry.v1'
    || ($registry['schema_id'] ?? null) !== 'https://simai.io/contracts/smart-component-manifest.v1.schema.json'
) {
    throw new RuntimeException('smart_contract_registry_incompatible');
}
$schemaPath = $loaderRoot . '/' . ($registry['schema_path'] ?? '');
$schemaContent = $read($schemaPath);
if (!hash_equals((string) ($registry['schema_sha256'] ?? ''), hash('sha256', $schemaContent))) {
    throw new RuntimeException('smart_contract_schema_hash_mismatch');
}

if (!is_dir($target) && !mkdir($target, 0775, true) && !is_dir($target)) {
    throw new RuntimeException('smart_contract_materialization_directory_failed');
}
$write($target . '/smart-component-contract-registry.json', $registryContent);
$write($target . '/smart-component-manifest.schema.json', $schemaContent);

$required = ['smart.admin-menu', 'smart.data-view'];
$entries = [];
foreach (is_array($registry['entries'] ?? null) ? $registry['entries'] : [] as $entry) {
    if (is_array($entry) && in_array($entry['id'] ?? null, $required, true)) {
        $entries[(string) $entry['id']] = $entry;
    }
}

$contracts = [];
foreach ($required as $id) {
    $entry = $entries[$id] ?? throw new RuntimeException('smart_contract_registry_entry_missing:' . $id);
    $manifestContent = $read($loaderRoot . '/' . ($entry['manifest_path'] ?? ''));
    if (!hash_equals((string) ($entry['manifest_sha256'] ?? ''), hash('sha256', $manifestContent))) {
        throw new RuntimeException('smart_contract_manifest_hash_mismatch:' . $id);
    }
    $manifest = json_decode($manifestContent, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($manifest)
        || ($manifest['id'] ?? null) !== $id
        || ($manifest['compatibility']['contract_schema']['version'] ?? null) !== 1
    ) {
        throw new RuntimeException('smart_contract_manifest_incompatible:' . $id);
    }
    $file = $id . '.json';
    $write($target . '/' . $file, $manifestContent);
    $contracts[] = [
        'id' => $id,
        'version' => $manifest['version'],
        'file' => $file,
        'manifest_sha256' => $entry['manifest_sha256'],
    ];
}

$materialization = [
    'schema' => 'larena.ui.source_contract_materialization.v1',
    'contracts' => $contracts,
    'registry_sha256' => hash('sha256', $registryContent),
    'registry_schema_sha256' => hash('sha256', $schemaContent),
];
$write(
    $target . '/materialization.json',
    json_encode($materialization, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL,
);

echo $target . PHP_EOL;
