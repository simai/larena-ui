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
