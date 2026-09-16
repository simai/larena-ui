# Dataview host lifecycle candidate

The existing Smart event bridge tracks each workbench instance and disposes only the subscriptions and pending requests it owns. Reinsertion reconnects once; child replacement invalidates the previous binding. Query sequence ordering remains per instance. This candidate does not alter domain routes, permissions or Framework grammar.

Run `composer test:dataview-host` with the supported Node runtime on PATH. Tests execute the production bridge with controlled transport responses: duplicate discovery, independent instances, out-of-order results, removal, late output, remount and child replacement. They do not prove Chrome hydration or a complete two-source list.

This is an isolated candidate based on the existing owner-bound composition branch. Do not install its package revision until owner integration, browser acceptance and exact Root pins are completed. Rollback is the preceding package revision. Row-ID/action payload migration remains a separate Framework/public-event boundary.

## Isolated integration resource contract

The changed bridge uses resource revision `20260916-dataview-host-lifecycle-59e27f70219c`. The matching Admin candidate must pin SHA-256 `59e27f70219c23c59af066a4289b4fafc76d9b2034c0c8f629c0af73b6212127`; an old expectation correctly refuses activation. A successful controlled-port probe alone does not establish this cross-package contract.
