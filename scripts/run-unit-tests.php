<?php

declare(strict_types=1);

$tests = [
    __DIR__ . '/../tests/Unit/UiContractTest.php',
    __DIR__ . '/../tests/Unit/UiFailsClosedTest.php',
    __DIR__ . '/../tests/Unit/InMemoryUiRuntimeTest.php',
    __DIR__ . '/../tests/Unit/FrontendRenderArtifactTest.php',
];

foreach ($tests as $test) {
    require $test;
}

echo "Larena UI unit tests passed.\n";
