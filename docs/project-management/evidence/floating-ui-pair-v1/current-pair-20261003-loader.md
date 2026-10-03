# Current Framework pair — 2026-10-03, complete loader dependencies

Larena moves to the pair the Framework owner bound on 2026-10-03 (ui `e34e0264`, "Bind the registry to the pair ui-3b0f4adecf3e-smart-ff82c2636e0e"; release lock `ui/contracts/releases/ui-3b0f4adecf3e-smart-ff82c2636e0e.lock.json`, status `bounded`). The archive SHA-256 of both runtime trees in this package's lock equals the release lock's.

This pair replaces `ui-227b90a6355a-smart-5c48c415d04d`: Core `3b0f4adecf3e203ff8c427e54f04a4389ccaaa28` (archive SHA-256 `15b2c11d…`, 6129 files), Smart `ff82c2636e0ef902cd6f27e03896009b8484f24f` (archive SHA-256 `629a795f…`, 785 files), registry `e34e0264b52756d5ae568fcf3a5f2ee5b505f1fb` (file SHA-256 `945f9a99…`).

Framework changes carried since the previous pin: the pagination page-size dropdown lists its sizes instead of a single empty option, and dropdowns take their options as data; the rule registry fetches every Smart element a component renders (the admin menu its badge, icon, icon button and input; the table its avatar, badge, list item, modal and range slider; breadcrumbs, buttons and inputs the icon), so a component no longer works only when a neighbour on the page loaded those; the gradient type rule no longer requests a deleted group.

The runtime lock keeps Larena's component and attribute lists; only the pair, bundle, source and registry entries change.
