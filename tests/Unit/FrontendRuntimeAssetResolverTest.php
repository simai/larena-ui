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
    'ui/distr/core/css/core.min.css',
    'ui/distr/core/js/core.min.js',
]);
assert($resolver->preloadedCssPaths(Smart::assetGraph('sf-table')) === [
    'ui/distr/core/css/core.min.css',
    'ui/distr/smart/icon-buttons/css/icon-buttons.css',
    'larena/smart/table/css/table.css',
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
    'simai.framework.sf_data_view.css',
    'simai.framework.sf_data_view.js',
]);

$baseline = FrontendRuntimeLock::bundled()->toArray();
$switched = $baseline;
$switched['ui']['commit'] = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
$switched['ui']['release'] = 'ui-aaaaaaaaaaaa';
$switched['larena']['requires_framework'] = 'ui-aaaaaaaaaaaa';
$switched['pair_id'] = 'ui-aaaaaaaaaaaa-larena-b51fe8e5b4cb';
$switched['bundle_id'] = $switched['pair_id'] . '-registry-105578bb-exact-git-tree-v2';
$switched['framework_registry']['compatibility_id'] = 'ui-aaaaaaaaaaaa';
$switchedLock = FrontendRuntimeLock::fromArray($switched);
assert($switchedLock->pairId() !== FrontendRuntimeLock::fromArray($baseline)->pairId());
assert(FrontendRuntimeLock::fromArray($baseline)->pairId() === 'ui-86580e83da1b-larena-b51fe8e5b4cb');

$mismatched = $switched;
$mismatched['pair_id'] = (string) $baseline['pair_id'];
try {
    FrontendRuntimeLock::fromArray($mismatched);
    throw new RuntimeException('Expected mismatched revision identity to fail closed.');
} catch (RuntimeException $exception) {
    assert($exception->getMessage() === 'ui_frontend_runtime_pair_identity_mismatch');
}

echo "FrontendRuntimeAssetResolverTest passed.\n";
