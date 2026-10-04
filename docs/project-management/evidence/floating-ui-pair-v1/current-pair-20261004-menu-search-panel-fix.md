# Current Framework pair — 2026-10-03, with the admin menu search panel in place

Larena moves to the pair the Framework owner handed over on 2026-10-03: ui `3d5a5e71` binds the registry over build `727a22fe`, handoff `ui-control/source/handoff/2026-10-04-admin-menu-search-to-larena.md (addendum, Framework message 2026-10-04)`. The archive SHA-256 of both runtime trees in this package's lock equals the release lock `ui/contracts/releases/ui-727a22fe189e-smart-7d932ed51408.lock.json` (Core `9da9774a…`, Smart `0a55555d…`).

This pair replaces `ui-a928c76c79da-smart-8e60c9cdbf32`: Core `727a22fe189e93b77eaeefee9e0253376743b066` (6143 files), Smart `7d932ed5140813806a601db3f3ddc6f3840e4638` (987 files), registry `3d5a5e71072af4ed57e5b56abdff9ab6085a167e` (file SHA-256 `8ca0191d…`).

Framework changes carried: the open search panel of sf-admin-menu stands at translateX(0) like a submenu on every screen (it was moved one menu width aside and clipped above the small breakpoint); an optional search-title for the panel's head.

The runtime lock keeps Larena's component and attribute lists; only the pair, bundle, source and registry entries change.
