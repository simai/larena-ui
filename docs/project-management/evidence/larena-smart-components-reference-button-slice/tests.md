# UI package tests

- `SmartComponentReferenceTest`: manifest/runtime parity, preset resolution,
  accessible-name mirroring, loading/disabled constraint, invalid combination,
  unknown/empty control rejection, real artifact and escaped examples passed.
- Package lint passed.
- PHPStan passed with a 1024M analysis memory limit.
- Scope check passed.
- Complete Composer test suite passed.
- Pinned SF5 runtime-lock diff was empty and SHA-256 was unchanged.
- Dependent Docara feature suite passed: 40 tests / 511 assertions.
- Evidence contract passed. The legacy package-stage validator remains red
  because its hard-coded status/launch-record allowlist predates this launch.
