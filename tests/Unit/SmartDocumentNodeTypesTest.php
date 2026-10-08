<?php

declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use Larena\Ui\Contracts\BackendRenderResult;
use Larena\Ui\Contracts\HydrationContract;
use Larena\Ui\Contracts\SmartBackendRenderer;
use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Enums\RenderStrategy;
use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Registry\SmartRegistry;
use Larena\Ui\Runtime\FrameworkLayoutDocumentTypes;
use Larena\Ui\Runtime\RegisteredDocumentRenderer;
use Larena\Ui\Runtime\SmartDocumentNodeTypes;
use Larena\Ui\Runtime\SmartManager;

// A host Smart part: renders only the request-bound text it is given.
$registry = SmartRegistry::withDefaults();
$registry->registerRenderer('test.document.part', new class implements SmartBackendRenderer {
    public function render(SmartComponentManifest $manifest, array $props, array $slots = []): BackendRenderResult
    {
        $text = is_string($props['text'] ?? null) ? $props['text'] : '';
        return new BackendRenderResult('<p data-part="'.$manifest->componentKey.'">'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</p>',
            RenderStrategy::Native, HydrationContract::none(), $manifest->assetRequirements);
    }
});
foreach (['test.document.search', 'test.document.actions'] as $key) {
    $registry->registerManifest(SmartComponentManifest::fromArray(['schema' => 'larena.ui.smart_manifest.v1', 'key' => $key,
        'version' => '1.0.0', 'owner_package' => 'larena/ui', 'kind' => 'smart',
        'props' => ['type' => 'object', 'properties' => ['text' => ['type' => 'string']], 'required' => ['text'], 'additionalProperties' => false],
        'slots' => [], 'events' => [], 'views' => ['default' => []], 'presets' => [], 'constraints' => [],
        'render' => ['strategy' => 'native', 'renderer' => 'test.document.part'], 'frontend' => [], 'assets' => [],
        'atlas' => ['visible' => false, 'title' => $key, 'description' => 'Test part.', 'category' => 'test', 'order' => 1],
        'provenance' => ['source' => 'larena/ui', 'reference_status' => 'test']]));
}
$activation = ['activation_owner' => 'larena/core:core.assets', 'physical_publication_ready' => true,
    'writes_database' => false, 'copies_to_root' => false, 'uses_hardcoded_cdn' => false,
    'runtime_pair' => FrontendRuntimeLock::bundled()->pairId(),
    'renderable_tags' => ['<link rel="stylesheet" href="/larena/assets/sf/core.css">']];
$seenNodes = [];
$smart = new SmartDocumentNodeTypes(new SmartManager($registry), ['dataview.toolbar', 'test.document.search', 'test.document.actions'],
    static function (array $node) use (&$seenNodes): array {
        $seenNodes[] = $node['id'];
        return $node['type'] === 'dataview.toolbar' ? [] : ['text' => 'request <'.$node['id'].'>'];
    }, $activation);
$types = FrameworkLayoutDocumentTypes::registrations() + $smart->registrations();
$part = static fn (string $id, string $type): array => ['id' => $id, 'type' => $type, 'presentation' => ['view' => 'default']];
$document = ['schema' => 'simai.composition.document.v1', 'root' => ['id' => 'page', 'type' => 'layout.page', 'slots' => ['default' => [
    ['id' => 'section', 'type' => 'layout.section', 'slots' => ['default' => [
        ['id' => 'toolbar', 'type' => 'dataview.toolbar', 'slots' => [
            'search' => [$part('search', 'test.document.search')],
            'options' => [],
            'actions' => [$part('actions', 'test.document.actions')],
        ]],
    ]]],
]]]];
$html = (new RegisteredDocumentRenderer(static function (array $doc): void {}, $types))->render($document);
// Slot children render first and land in the composite's own slot wrappers.
assert($seenNodes === ['search', 'actions', 'toolbar']);
assert(str_starts_with($html, '<main data-sf-composition-id="page"><section data-sf-composition-id="section" data-composition-preset="surface"><div class="larena-dataview-toolbar" data-larena-composite="dataview.toolbar">'));
assert(str_contains($html, '<div class="larena-dataview-toolbar__search" data-larena-slot="search"><p data-part="test.document.search">request &lt;search&gt;</p></div>'));
assert(str_contains($html, '<div class="larena-dataview-toolbar__options" data-larena-slot="options"></div>'));
assert($smart->assetTags() === ['<link rel="stylesheet" href="/larena/assets/sf/core.css">']);

// The Document carries structure only: props, data, unknown slots, other views and unregistered types are refused.
foreach ([
    ['props' => ['text' => 'from document']],
    ['data' => ['text' => 'from document']],
    ['slots' => ['unknown' => []]],
    ['presentation' => ['view' => 'compact']],
] as $patch) {
    try { ($types['test.document.search']['validate'])(array_replace($part('search', 'test.document.search'), $patch)); throw new RuntimeException('Document-owned Smart props accepted'); }
    catch (InvalidArgumentException $exception) { assert($exception->getMessage() === 'ui_document_smart_node_invalid:test.document.search'); }
}
try { (new SmartDocumentNodeTypes(new SmartManager($registry), ['missing.component'], static fn (array $node): array => [], $activation))->registrations(); throw new RuntimeException('Unknown component registered'); }
catch (InvalidArgumentException) {}

// A component whose assets are not published is not rendered.
$unready = new SmartDocumentNodeTypes(new SmartManager($registry), ['dataview.toolbar'], static fn (array $node): array => [], []);
try { ($unready->registrations()['dataview.toolbar']['render'])(['id' => 't', 'type' => 'dataview.toolbar', 'slots' => []], []); throw new RuntimeException('Unpublished assets rendered'); }
catch (InvalidArgumentException) {}

echo "SmartDocumentNodeTypesTest passed: Smart components render as Document nodes with host props, slot children and asset tags.\n";
