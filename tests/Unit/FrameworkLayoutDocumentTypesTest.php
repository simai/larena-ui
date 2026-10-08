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
// The host may name its own page classes; the Document cannot.
$hosted = FrameworkLayoutDocumentTypes::registrations(['layout.page' => 'larena-auth__screen larena-layout', 'layout.section' => 'larena-layout__section']);
assert(($hosted['layout.page']['render'])(['type' => 'layout.page', 'id' => 'p'], ['default' => 'x']) === '<main class="larena-auth__screen larena-layout" data-sf-composition-id="p">x</main>');
assert(($hosted['layout.section']['render'])(['type' => 'layout.section', 'id' => 's'], ['default' => 'y']) === '<section class="larena-layout__section" data-sf-composition-id="s" data-composition-preset="surface">y</section>');
assert(($types['layout.page']['render'])(['type' => 'layout.page', 'id' => 'p'], ['default' => 'x']) === '<main data-sf-composition-id="p">x</main>');
foreach ([['layout.page' => 'bad" onclick="x'], ['layout.page' => ''], ['content.text' => 'a'], ['layout.section' => 'A']] as $bad) {
    try { FrameworkLayoutDocumentTypes::registrations($bad); throw new RuntimeException('Unsafe host class accepted'); }
    catch (InvalidArgumentException) {}
}
echo "FrameworkLayoutDocumentTypesTest passed: selected backend shell projection and manifest refusal.\n";
