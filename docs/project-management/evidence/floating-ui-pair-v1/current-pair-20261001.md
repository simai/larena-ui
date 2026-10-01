# Current Framework pair — 2026-10-01

Larena moves to the pair the Framework owner bound on 2026-10-01 (ui `0c7a19af`, "contracts: bind the generated pair ui-0315c0ffebb6-smart-41fd1892b345"; release lock `ui/contracts/releases/ui-0315c0ffebb6-smart-41fd1892b345.lock.json`, status `bounded`). The archive SHA-256 of both runtime trees in this package's lock equals the release lock's.

This pair replaces `ui-e5a1228a9d8a-smart-c184f5944ae6`: Core `0315c0ffebb698536fc69036f9fee68a73c27c92` (archive SHA-256 `a2ab3aac…`, 5187 files), Smart `41fd1892b345287f6a75f55080a4a3552b865037` (archive SHA-256 `0ab47651…`, 785 files), registry `0c7a19af148b9f9b8ed9edbffd0de8fd0e286b8c` (file SHA-256 `c66d7212…`).

Framework changes carried since the previous pin (Framework CHANGELOG, Unreleased): `sf-toggle` deprecated in favour of `sf-switch`; the `--size-1/3` step removed from checkbox, radio and switch; `--sf-on-surface-muted` retired for text (WCAG contrast); radius scale raised (control 4px, block 8px, `radius-4`, `--sf-radius--surface`); `elevation-1…5` and `--sf-surface-5`; one focus ring per component and separate border roles; `--sf-label-large` fixed at 14/20; a page without `window.sfPath` warns that it loads from the CDN.

The runtime lock keeps Larena's component and attribute lists; only the pair, bundle, source and registry entries change.
