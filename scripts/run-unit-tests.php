<?php

declare(strict_types=1);

$tests = [
    __DIR__ . '/../tests/Unit/UiContractTest.php',
    __DIR__ . '/../tests/Unit/UiFailsClosedTest.php',
    __DIR__ . '/../tests/Unit/InMemoryUiRuntimeTest.php',
    __DIR__ . '/../tests/Unit/FrontendRenderArtifactTest.php',
    __DIR__ . '/../tests/Unit/SmartViewDescriptorTest.php',
    __DIR__ . '/../tests/Unit/InstalledSmartAdapterCatalogTest.php',
    __DIR__ . '/../tests/Unit/SourceSmartContractCatalogTest.php',
    __DIR__ . '/../tests/Unit/CompositeSmartViewTest.php',
    __DIR__ . '/../tests/Unit/PaginationLocalizationContractTest.php',
    __DIR__ . '/../tests/Unit/AdminSmartEventBridgeContractTest.php',
];

foreach ($tests as $test) {
    require $test;
}

echo "Larena UI unit tests passed.\n";
