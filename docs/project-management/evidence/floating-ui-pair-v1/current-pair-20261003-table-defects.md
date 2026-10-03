# Current Framework pair — 2026-10-03, data view table defects

Larena moves to the pair the Framework owner handed over on 2026-10-03 for group 1 of the data view table request (`larena-specs` `40cdaf74`): ui `e799df2b` binds the registry, handoff `ui-control/source/handoff/2026-10-03-dataview-table-defects-to-larena.md`. The archive SHA-256 of both runtime trees in this package's lock is computed from the pinned commits.

This pair replaces `ui-3b0f4adecf3e-smart-ff82c2636e0e`: Core `5e466412cac0b202a9965f0b228938964690916a` (6129 files, `distr` unchanged in content), Smart `a794c2e9c4928d78f49e62425876d9a8306797cf` (785 files), registry `e799df2bd729eb960dc0c47c77b5abf2bab35e8a` (file SHA-256 `6932972a…`).

Framework changes carried: the column resize handle is found by `data-sf-column-resizer` (dragging the edge resizes again, dragging the label still moves the column); "Show more" appends the next rows and skips repeated ids, so checked rows stay checked; a new `vertical-scroll` input (`self` default, `page`). Larena keeps `self` and bounds the table to the viewport height (owner decision 2026-10-03, variant A), so the head stays visible inside the table box and wide tables scroll inside it.

The runtime lock keeps Larena's component and attribute lists; only the pair, bundle, source and registry entries change.
