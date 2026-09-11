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
    'simai.framework.sf_table.css',
    'simai.framework.sf_table.js',
]);
assert(array_column($core, 'relative_path') === [
    'ui/distr/core/css/core.css',
    'ui/distr/core/js/core.js',
]);
assert($resolver->preloadedCssPaths(Smart::assetGraph('sf-table')) === [
    'ui/distr/core/css/core.css',
    'smart/smart/table/css/table.css',
]);

$baseline = FrontendRuntimeLock::bundled()->toArray();
$switched = $baseline;
$switched['ui']['commit'] = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
$switched['pair_id'] = 'ui-aaaaaaaaaaaa-smart-8b0e0482f78a';
$switched['bundle_id'] = 'ui-aaaaaaaaaaaa-smart-8b0e0482f78a-registry-d6977db7-exact-git-tree-v2';
$switched['framework_registry']['compatibility_id'] = $switched['pair_id'];
$switchedLock = FrontendRuntimeLock::fromArray($switched);
assert($switchedLock->pairId() !== FrontendRuntimeLock::fromArray($baseline)->pairId());
assert(FrontendRuntimeLock::fromArray($baseline)->pairId() === 'ui-2228262ad358-smart-8b0e0482f78a');

$mismatched = $switched;
$mismatched['pair_id'] = (string) $baseline['pair_id'];
try {
    FrontendRuntimeLock::fromArray($mismatched);
    throw new RuntimeException('Expected mismatched revision identity to fail closed.');
} catch (RuntimeException $exception) {
    assert($exception->getMessage() === 'ui_frontend_runtime_pair_identity_mismatch');
}

echo "FrontendRuntimeAssetResolverTest passed.\n";
