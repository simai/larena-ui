# Stage D data-view resource registration

The exact local Framework bundle `ui-56cd91e1d7a3-smart-5e7adda70be4-registry-8969813a-exact-git-tree-v2` already contains `smart/smart/data-view/js/data-view.js`. The Larena UI runtime lock now registers `sf-data-view` from that source and declares `sf-table` and `sf-pagination` as required assets. No Framework bytes or pair identity changed.

`SourceBackedComponentRegistryTest.php` verifies the tag and allowed structural attributes. `FrontendRuntimeAssetResolverTest.php` verifies the dependency order. `composer run quality:gate` passed with ServBay PHP 8.4. Consumer rendering and browser acceptance remain separate steps.
