# Tests

- `composer quality:gate` with SIMAI_UI_ROOT set to the pinned pair runtime: passed.
- `node --test tests/JavaScript/composition-routing-host.test.mjs`: 6 passed (empty target, one query per selection, latest result wins, local empty and oversized answers, source list ignores context, unmount suppresses late answers).
