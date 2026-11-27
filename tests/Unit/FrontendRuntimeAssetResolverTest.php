<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeAssetResolver;
use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Smart;

$resolver = FrontendRuntimeAssetResolver::bundled();
$core = $resolver->resolve(FrontendRuntimeAssetResolver::coreGraph());
$assets = $resolver->resolve(Smart::assetGraph('sf-view-table'));
$keys = array_column($assets, 'asset_key');

assert($keys === [
    'simai.framework.core.css',
    'simai.framework.core.js',
    'simai.framework.smart_base.js',
    'simai.framework.bridge.js',
    'simai.framework.sf_icon.js',
    'simai.framework.sf_button_icon.css',
    'simai.framework.sf_button_icon.js',
    'simai.framework.sf_view_table.css',
    'simai.framework.sf_view_table.js',
]);
assert(array_column($core, 'relative_path') === [
    'ui/distr/core/css/core.min.css',
    'ui/distr/core/js/core.min.js',
]);
assert($resolver->preloadedCssPaths(Smart::assetGraph('sf-view-table')) === [
    'ui/distr/core/css/core.min.css',
    'ui/distr/smart/button-icon/css/button-icon.min.css',
    'pro/smart/view-table/css/view-table.min.css',
]);
// sf-editor comes from the Editor set's own tree (editor/smart/editor), after the free components it uses.
$editorAssets = $resolver->resolve(Smart::assetGraph('sf-editor'));
assert(array_slice($editorAssets, -2) === [
    ['asset_key' => 'simai.framework.sf_editor.css', 'kind' => 'css', 'relative_path' => 'editor/smart/editor/css/editor.min.css', 'critical' => true],
    ['asset_key' => 'simai.framework.sf_editor.js', 'kind' => 'javascript', 'relative_path' => 'editor/smart/editor/js/editor.min.js', 'critical' => true],
]);
foreach (array_slice($editorAssets, 4) as $asset) {
    assert(str_starts_with($asset['relative_path'], 'ui/distr/') || str_starts_with($asset['relative_path'], 'editor/smart/'), $asset['asset_key']);
}
assert(in_array('ui/distr/smart/editor-inline/js/editor-inline.min.js', array_column($editorAssets, 'relative_path'), true));
$dataViewAssets = $resolver->resolve(Smart::assetGraph('sf-view'));
assert(array_column($dataViewAssets, 'asset_key') === [
    'simai.framework.core.css',
    'simai.framework.core.js',
    'simai.framework.smart_base.js',
    'simai.framework.bridge.js',
    'simai.framework.sf_icon.js',
    'simai.framework.sf_button_icon.css',
    'simai.framework.sf_button_icon.js',
    'simai.framework.sf_view_table.css',
    'simai.framework.sf_view_table.js',
    'simai.framework.sf_pagination.js',
    'simai.framework.sf_view.css',
    'simai.framework.sf_view.js',
]);

$baseline = FrontendRuntimeLock::bundled()->toArray();
$switched = $baseline;
$switched['ui']['commit'] = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
$switched['ui']['release'] = 'ui-aaaaaaaaaaaa';
$switched['packages']['pro']['requires_framework'] = 'ui-aaaaaaaaaaaa';
$switched['packages']['chart']['requires_framework'] = 'ui-aaaaaaaaaaaa';
$switched['packages']['admin']['requires_framework'] = 'ui-aaaaaaaaaaaa';
$switched['packages']['editor']['requires_framework'] = 'ui-aaaaaaaaaaaa';
$switched['pair_id'] = 'ui-aaaaaaaaaaaa-pro-cfaab360bb39-chart-58ba480db497-admin-25245d70cde8-editor-52109ce5017f';
$switched['bundle_id'] = $switched['pair_id'] . '-registry-a1b46744-exact-git-tree-v2';
$switched['framework_registry']['compatibility_id'] = 'ui-aaaaaaaaaaaa';
$switchedLock = FrontendRuntimeLock::fromArray($switched);
assert($switchedLock->pairId() !== FrontendRuntimeLock::fromArray($baseline)->pairId());
assert(FrontendRuntimeLock::fromArray($baseline)->pairId() === 'ui-4f5678e8c0ed-pro-cfaab360bb39-chart-58ba480db497-admin-25245d70cde8-editor-52109ce5017f');

$mismatched = $switched;
$mismatched['pair_id'] = (string) $baseline['pair_id'];
try {
    FrontendRuntimeLock::fromArray($mismatched);
    throw new RuntimeException('Expected mismatched revision identity to fail closed.');
} catch (RuntimeException $exception) {
    assert($exception->getMessage() === 'ui_frontend_runtime_pair_identity_mismatch');
}

echo "FrontendRuntimeAssetResolverTest passed.\n";
