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
$switched['pair_id'] = 'ui-aaaaaaaaaaaa-pro-22376674af4e';
$switched['bundle_id'] = $switched['pair_id'] . '-registry-994a4ed3-exact-git-tree-v2';
$switched['framework_registry']['compatibility_id'] = 'ui-aaaaaaaaaaaa';
$switchedLock = FrontendRuntimeLock::fromArray($switched);
assert($switchedLock->pairId() !== FrontendRuntimeLock::fromArray($baseline)->pairId());
assert(FrontendRuntimeLock::fromArray($baseline)->pairId() === 'ui-1e5ec795a1ce-pro-22376674af4e');

$mismatched = $switched;
$mismatched['pair_id'] = (string) $baseline['pair_id'];
try {
    FrontendRuntimeLock::fromArray($mismatched);
    throw new RuntimeException('Expected mismatched revision identity to fail closed.');
} catch (RuntimeException $exception) {
    assert($exception->getMessage() === 'ui_frontend_runtime_pair_identity_mismatch');
}

echo "FrontendRuntimeAssetResolverTest passed.\n";
