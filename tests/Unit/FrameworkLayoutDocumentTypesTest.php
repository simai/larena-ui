<?php

declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Larena\Ui\Runtime\FrameworkLayoutDocumentTypes;
$types = FrameworkLayoutDocumentTypes::registrations();
foreach ($types as $type => $registration) {
    $node = ['type' => $type, 'id' => 'page-1', 'slots' => ['default' => []]];
    ($registration['validate'])($node);
    assert(str_contains(($registration['render'])($node, ['default' => '<p>trusted child</p>']), '<p>trusted child</p>'));
    foreach ([['props' => ['html' => 'unsafe']], ['slots' => ['unknown' => []]], ['presentation' => ['view' => 'unknown']]] as $patch) {
        try { ($registration['validate'])(array_replace($node, $patch)); throw new RuntimeException('Unknown type surface accepted'); }
        catch (InvalidArgumentException) {}
    }
}
echo "FrameworkLayoutDocumentTypesTest passed: selected backend shell projection and manifest refusal.\n";
