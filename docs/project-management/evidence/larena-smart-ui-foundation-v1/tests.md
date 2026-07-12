# Tests

Baseline on PHP 8.4.20:

- complete Composer test suite: passed;
- PHP syntax lint: passed, 58 files;
- package launch-context validator: passed;
- evidence contract: passed;
- PHPStan with 1G memory limit: passed with no errors.

The UI analysis wrapper now applies a reproducible 1G default, configurable via
`LARENA_UI_PHPSTAN_MEMORY_LIMIT`, after the previous 128M inherited limit was
shown to be insufficient.

Batch B1 on PHP 8.4.20:

- `composer quality:gate`: passed;
- package launch-context validator: passed;
- PHP syntax lint: passed, 60 files;
- PHPStan: passed with no errors;
- complete Composer test suite: passed, including the new catalog and AI
  projection tests;
- evidence contract: passed;
- package scope check: passed.

Focused regression coverage proves stable `atlas.order -> key` sorting, RU/EN
projection, localized-label precedence, exact four-flag readiness, canonical
relative provenance, deterministic AI JSON, explicit complex frontend-example
unavailability, missing renderer rejection, hidden/unknown manifest rejection
and canonical `ui.dataview` rendering.

The Composer PHAR emits upstream PHP 8.4 deprecation notices before running
scripts; every package command itself exits successfully.

Batch B2 on PHP 8.4.20:

- `composer quality:gate`: passed end to end;
- complete Composer test suite: passed, including the ten-component library;
- PHP syntax lint: passed, 61 files;
- PHPStan: passed with no errors;
- package launch-context validator, evidence contract and scope check: passed;
- focused catalog, AI projection and component-library tests: passed;
- all 10 manifest files parsed canonically and matched the pinned prop/asset
  contracts;
- real SmartManager default renders: 10 of 10 passed;
- deterministic submitted alternate renders: 48 of 48 exposed controls changed
  both resolved props and host HTML;
- source event projection accepted all real dropdown/modal colon names and
  rejected spaces, slashes, malformed separators and handler-style names;
- every event keeps `backend_handler_binding=false`, with emitted alert,
  pagination, dropdown and modal events classified as custom;
- dropdown and dataview backend render paths passed while frontend snippets
  remained explicitly unavailable for their structured props.
