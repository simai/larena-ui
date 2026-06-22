# Tests

- `php tests/Unit/UiContractTest.php`
- `php tests/Unit/UiFailsClosedTest.php`
- `composer run quality:gate`

Expected: 12 package-owned carrier resources are present, all final paths remain core.assets-owned, and reference-only readiness fails closed unless Larena-owned carriers are available.
