# Checks

Node behavioral tests: 3 passed against actual bridge. Package gate passed; its shared-vendor PHPStan step reports skipped, so PHPStan 2.2.1 was independently installed in an isolated temporary environment and run against phpstan.neon.dist: zero errors. Four negative launch checks reject wrong owner, goal, baseline and scope.

Chrome native-DOM lifecycle probe: seven observed checks passed. Uses controlled component ports and transport; does not accept full Smart Table or authenticated server workflow. See browser-receipt.json.


## Registered composite presentation — 2026-09-17

Full native package quality gate passed after ensuring tests load this package's source: 89 PHP files linted, all existing unit/contract suites, JavaScript lifecycle suites, Minimal CMS dependency, evidence and scope checks. Independent PHPStan 2.2.1 level 5 of RegisteredListRenderer and modified SmartManager returned zero errors. The new unit test proves distinct table/pagination IDs for two placements of the same dataset, projection excludes a private field, HTML-looking text is safely serialized in the request hydration JSON, and invalid columns/bindings fail. Structural HTML-looking props still fail. This is not Chrome/XSS-after-hydration, SSR content or interactive list acceptance.
