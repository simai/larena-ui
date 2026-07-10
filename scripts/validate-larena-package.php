<?php

declare(strict_types=1);

$requiredFiles = [
    '.gitignore',
    '.env.example',
    '.github/workflows/larena-package-ci.yml',
    '.githooks/pre-commit',
    '.githooks/pre-push',
    'composer.json',
    'module.yaml',
    'phpstan.neon.dist',
    '.larena/spec-ref.json',
    '.larena/launch-context.json',
    'tools/larena-scope-check.php',
];
$errors = [];
foreach ($requiredFiles as $file) {
    if (!is_file($file)) {
        $errors[] = "Missing required enforcement file: {$file}";
    }
}
$specRef = is_file('.larena/spec-ref.json')
    ? json_decode((string) file_get_contents('.larena/spec-ref.json'), true, 512, JSON_THROW_ON_ERROR)
    : [];
$launchContext = is_file('.larena/launch-context.json')
    ? json_decode((string) file_get_contents('.larena/launch-context.json'), true, 512, JSON_THROW_ON_ERROR)
    : [];
if (($specRef['canonical_update_allowed'] ?? null) !== false) {
    $errors[] = '.larena/spec-ref.json must keep canonical_update_allowed=false';
}
if (($launchContext['package'] ?? null) !== 'larena/ui') {
    $errors[] = '.larena/launch-context.json package must be larena/ui';
}
if (!str_starts_with((string) ($launchContext['evidence_path'] ?? ''), 'docs/project-management/evidence/')) {
    $errors[] = 'launch-context evidence_path must start with docs/project-management/evidence/';
}
if (!str_starts_with((string) ($launchContext['graph_sync_proposal_path'] ?? ''), (string) ($launchContext['evidence_path'] ?? '__missing__'))) {
    $errors[] = 'graph_sync_proposal_path must be inside evidence_path';
}
$allowedStatuses = [
    'repository_prepared_pending_review',
    'coding_started',
    'contract_skeleton_review_passed',
];
if (!in_array((string) ($launchContext['status'] ?? ''), $allowedStatuses, true)) {
    $errors[] = 'launch-context status is not allowed for this package stage.';
}
$codingStarted = ($launchContext['coding_started'] ?? null) === true;
if (!$codingStarted) {
    foreach (['src', 'config', 'database', 'routes', 'resources', 'tests', 'lang'] as $runtimePath) {
        if (is_dir($runtimePath)) {
            $errors[] = "{$runtimePath}/ is not allowed before a coding launch record.";
        }
    }
}
if ($codingStarted) {
    if (!in_array(($launchContext['launch_record_ref'] ?? null), [
        'specs/implementation-planning/launch-records/ui-batch-1-contract-skeletons-current.json',
        '/Users/rim/Documents/GitHub/larena/docs/project-management/launch-records/admin-ui-runtime-dataview-pages-users.json',
    ], true)) {
        $errors[] = 'coding_started requires the current ui batch 1 launch record.';
    }
    $requiredContractFiles = [
        'src/Contracts/BackendRenderResult.php',
        'src/Contracts/ComponentAtlasEntry.php',
        'src/Contracts/DesignPackDescriptor.php',
        'src/Contracts/HydrationContract.php',
        'src/Contracts/SmartComponentManifest.php',
        'src/Contracts/SmartViewDescriptor.php',
        'src/Contracts/UiAssetGraph.php',
        'src/Contracts/UiAssetRequirement.php',
        'src/Contracts/UiPlaygroundScenario.php',
        'src/Contracts/UiResourcePackManifest.php',
        'src/Contracts/UiRuntime.php',
        'src/Enums/HydrationStrategy.php',
        'src/Enums/RenderStrategy.php',
        'src/Enums/UiAssetKind.php',
        'tests/Unit/UiContractTest.php',
        'tests/Unit/UiFailsClosedTest.php',
    ];
    foreach ($requiredContractFiles as $file) {
        if (!is_file($file)) {
            $errors[] = "Missing required ui contract skeleton file: {$file}";
        }
    }
}
if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, $error . PHP_EOL);
    }
    exit(1);
}
echo $codingStarted
    ? "Larena UI contract skeleton launch context is valid.\n"
    : "Larena UI clean pre-codegen baseline is valid.\n";
