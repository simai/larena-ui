# Stage D data-view resource registration

The exact local Framework bundle `ui-56cd91e1d7a3-smart-5e7adda70be4-registry-8969813a-exact-git-tree-v2` already contains `smart/smart/data-view/js/data-view.js`. The Larena UI runtime lock now registers `sf-data-view` from that source and declares `sf-table` and `sf-pagination` as required assets. No Framework bytes or pair identity changed.

`SourceBackedComponentRegistryTest.php` verifies the tag and allowed structural attributes. `FrontendRuntimeAssetResolverTest.php` verifies the dependency order. `composer run quality:gate` passed with ServBay PHP 8.4. Consumer rendering and browser acceptance remain separate steps.

The bridge now connects a slotted `sf-data-view` to the same-origin Admin port with per-instance query state. It maps only owner-projected `display_values` into visible cells and rejects an applied answer that lacks that projection. It initializes search and sort from the server query when hidden form controls are absent, derives capabilities from the host, and sends the complete saved-view content with exactly the accepted host keys. The focused Node test covers scoped payloads, action mapping, raw-ID non-disclosure, initial query and saved-view payload. `composer run quality:gate` passed with ServBay PHP 8.4 and the pinned runtime. The page has not switched to this path yet; authenticated browser and lifecycle acceptance remain open.

After the Admin page switch, the bridge exposes the slotted pagination's all-record toggle only when the host supplied the matching permission. This is verified in the focused Node test and the full UI quality gate. Selected bulk remains blocked in the pinned Framework composite before the host port call; it is not accepted yet.

## Corrected host port digest, 2026-09-25

The laboratory now pins Core `56cd91e1d7a3dc19b32a2acfdaa1389744e174a7`, Smart runtime `8f2522b7e71671237bc5f102eecac97a0450664e`, and registry `467ca4012abb36b375858efd217dfc92694ad4c3`. The pair is `ui-56cd91e1d7a3-smart-8f2522b7e716`; the exact bundle is `ui-56cd91e1d7a3-smart-8f2522b7e716-registry-29fc9a8c-exact-git-tree-v2`. The Smart change corrects the declared `simai.dataview-port` 1.1.0 digest in the built manifest; the selected-bulk behavior from the preceding pin is preserved.

The local pin produced and validated the exact artifact. `composer run quality:gate` passed with ServBay PHP 8.4 and the exact Core archive. The Framework filter endpoint browser fixture passed against the exact Core and Smart archives, including two pages, an off-page selected label, stale response rejection, and error recovery. The actual signed-in CMS page still requires owner browser acceptance.
