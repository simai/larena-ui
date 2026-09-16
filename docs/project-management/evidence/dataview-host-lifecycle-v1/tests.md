# Checks

Node behavioral tests: 3 passed against actual bridge. Package gate passed; its shared-vendor PHPStan step reports skipped, so PHPStan 2.2.1 was independently installed in an isolated temporary environment and run against phpstan.neon.dist: zero errors. Four negative launch checks reject wrong owner, goal, baseline and scope.

Chrome native-DOM lifecycle probe: seven observed checks passed. Uses controlled component ports and transport; does not accept full Smart Table or authenticated server workflow. See browser-receipt.json.
