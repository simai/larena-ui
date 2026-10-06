<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeAssetResolver;
use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Smart;

$resolver = FrontendRuntimeAssetResolver::bundled();
$core = $resolver->resolve(FrontendRuntimeAssetResolver::coreGraph());
$assets = $resolver->resolve(Smart::assetGraph('sf-table'));
$keys = array_column($assets, 'asset_key');

assert($keys === [
    'simai.framework.core.css',
    'simai.framework.core.js',
    'simai.framework.smart_base.js',
    'simai.framework.bridge.js',
    'simai.framework.sf_icon.js',
    'simai.framework.sf_icon_button.css',
    'simai.framework.sf_icon_button.js',
    'simai.framework.sf_table.css',
    'simai.framework.sf_table.js',
]);
assert(array_column($core, 'relative_path') === [
    'ui/distr/core/css/core.css',
    'ui/distr/core/js/core.js',
]);
assert($resolver->preloadedCssPaths(Smart::assetGraph('sf-table')) === [
    'ui/distr/core/css/core.css',
    'smart/smart/icon-buttons/css/icon-buttons.css',
    'smart/smart/table/css/table.css',
]);
$dataViewAssets = $resolver->resolve(Smart::assetGraph('sf-data-view'));
assert(array_column($dataViewAssets, 'asset_key') === [
    'simai.framework.core.css',
    'simai.framework.core.js',
    'simai.framework.smart_base.js',
    'simai.framework.bridge.js',
    'simai.framework.sf_icon.js',
    'simai.framework.sf_icon_button.css',
    'simai.framework.sf_icon_button.js',
    'simai.framework.sf_table.css',
    'simai.framework.sf_table.js',
    'simai.framework.sf_pagination.js',
    'simai.framework.sf_data_view.js',
]);

$baseline = FrontendRuntimeLock::bundled()->toArray();
$switched = $baseline;
$switched['ui']['commit'] = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
$switched['pair_id'] = 'ui-aaaaaaaaaaaa-smart-8a820884d00e';
$switched['bundle_id'] = 'ui-aaaaaaaaaaaa-smart-8a820884d00e-registry-daf5f3e6-exact-git-tree-v2';
$switched['framework_registry']['compatibility_id'] = $switched['pair_id'];
$switchedLock = FrontendRuntimeLock::fromArray($switched);
assert($switchedLock->pairId() !== FrontendRuntimeLock::fromArray($baseline)->pairId());
assert(FrontendRuntimeLock::fromArray($baseline)->pairId() === 'ui-571f3dc8fc5b-smart-8a820884d00e');

$mismatched = $switched;
$mismatched['pair_id'] = (string) $baseline['pair_id'];
try {
    FrontendRuntimeLock::fromArray($mismatched);
    throw new RuntimeException('Expected mismatched revision identity to fail closed.');
} catch (RuntimeException $exception) {
    assert($exception->getMessage() === 'ui_frontend_runtime_pair_identity_mismatch');
}

echo "FrontendRuntimeAssetResolverTest passed.\n";
