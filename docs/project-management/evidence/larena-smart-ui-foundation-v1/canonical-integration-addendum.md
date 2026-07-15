# Canonical integration addendum

The Developer Alpha integration adds a tracked provenance lock for 14 vendored
Simai Framework assets and 9 reference examples. Package verification compares
the vendored bytes with locked SHA-256 values. External `ui`, `ui-smart`, and
`ui-play` repositories are audited only when their three explicit environment
roots are supplied; absent roots produce `source_audit_unavailable` and do not
invalidate the portable package contract.

No frontend build pipeline, route, controller, database mutation, or production
readiness claim is introduced by this package batch.
