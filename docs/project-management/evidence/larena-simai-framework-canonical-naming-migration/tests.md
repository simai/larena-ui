# Verification evidence

Verification used `/opt/homebrew/Cellar/php@8.3/8.3.31/bin/php` because the
default Homebrew PHP 8.2 binary is linked to an unavailable ICU 73 library.

- All Composer test-script equivalents passed, including the Smart registry,
  manager, facade, component reference, action link and runtime lock tests.
- Focused canonical naming assertions passed for `ui.sf_element`, the `sf-v`
  pair, the new lock path and Simai Framework product copy.
- PHP lint passed for 58 files.
- PHPStan passed with `--memory-limit=512M`.
- `scripts/validate-larena-package.php` passed.
- `tools/larena-scope-check.php` passed with explicit rename tombstones.
- `scripts/check-evidence.php` passed for this packet.
- JSON parsing passed for launch context, runtime lock and Smart manifests.
- `git diff --check` passed.
- The active naming gate found no generation-specific identifier outside the
  explicit deletion tombstones; active path-name search also returned zero.
- `cmp -s` confirmed byte equality for both button carriers plus the tags and
  pagination package CSS against the pinned `ui` source; SHA-256 values are
  recorded in `implementation-summary.md` and `deviations.json`.
- Admin `SourceBackedSfCatalogAdapterPipeline::run()` returned `status=passed`,
  `all_package_resources_match_source=true` and no failed diagnostics.
- Admin `SourceBackedSfBindingDemo::run()` returned `status=passed`; all four
  binding resource provenance comparisons are byte-identical and no runtime
  diagnostic failed.
