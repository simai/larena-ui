# Current Framework pair — 2026-10-06, with tonal choice cells and boolean words from the field

Larena moves to the pair the Framework owner handed over on 2026-10-06: ui `0c48f8ec` binds the registry over build `4ebf9f7f`, handoff `Framework message 2026-10-06 (tonal choice cells)`. The archive SHA-256 of both runtime trees in this package's lock equals the release lock `ui/contracts/releases/ui-4ebf9f7f4d63-smart-9a9898d9b1f0.lock.json` (Core `4ed1826e…`, Smart `6b1f2df4…`).

This pair replaces `ui-05f1c93eae4d-smart-237fa22bac3a`: Core `4ebf9f7f4d633b9cc0b0566c54207bac43f316d8` (6143 files), Smart `9a9898d9b1f0f2858fdde4f9f1ec32a662cae940` (992 files), registry `0c48f8ec70debc58699046fed7aae577599500c5` (file SHA-256 `cdbce90c…`).

Framework changes carried: choice cells are tonal badges by default (a column may ask for outline or main through badgeAppearance); a boolean cell and editor take trueText/falseText from the field; Smart templates read the values their component resolved, so sf-list-item draws its checkbox and sf-input appearance=hidden renders its hidden input.

The runtime lock keeps Larena's component and attribute lists; only the pair, bundle, source and registry entries change.
