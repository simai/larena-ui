# Larena UI canonical Simai Framework naming migration

This packet records the bounded naming migration inside `larena/ui`. Product
copy now uses **Simai Framework**; compact PHP symbols use `Sf`; machine keys,
paths and the immutable runtime pair use `sf`. Existing `<sf-*>` elements,
`SF` browser globals, upstream version tags and `simai.framework.*` asset keys
remain canonical.

The migration removes generation-specific naming from active UI/runtime
contracts without changing the pinned upstream revisions. Historical evidence
keeps its original terminology and contents.

- `implementation-summary.md` describes the delivered contract changes.
- `tests.md` and `smoke.md` record automated and package-level smoke evidence.
- `file-map.json` lists the active files and explicit rename families.
- `deviations.json` records deferred cross-package/browser boundaries.
- `independent-review.md` gives the reverse-outcome verdict.
- `graph-sync-proposal.json` is proposal-only and cannot update canonical data.

This packet does not claim cross-package browser completion, production
readiness or readiness of all Larena packages.
