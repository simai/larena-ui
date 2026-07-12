# UI package tests

- Complete Composer test suite passed, including facade success, missing and
  invalid resolver failures, SmartManager parity and legacy Smart regression.
- `SmartComponentReferenceTest` passed with the HTTP-normalized `radius=null`
  regression and preserved fail-closed input validation.
- `AdminUiLabReferenceBehaviorTest` passed for scoped immediate/debounced GET
  submission without a client-side preview renderer.
- Package lint passed for 58 PHP files.
- PHPStan level 5 passed with a 1024M analysis memory limit.
- Scope and evidence checks passed for the current launch context.
- The pinned SF5 runtime-lock SHA-256 remained unchanged.
