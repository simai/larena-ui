# Icon duplicate-definition pair — 2026-09-27

Framework extended the duplicate-registration guard to `sf-icon` and `sf-icon-button` after the signed-in Larena readback of the `b79` pair still showed `SfIcon.define()` and `SfIconButton.define()` "already defined" warnings (Framework handoff `ui-control/source/handoff/2026-09-27-framework-icon-duplicate-registration-guard.md`, acceptance `control/evidence/2026-09-27-icon-duplicate-registration/acceptance.json`, `e3bc1ca`).

Pinned exact Core `56cd91e1d7a3dc19b32a2acfdaa1389744e174a7` (unchanged), Smart runtime `1f898fc321c05167f7c1e58f209fa5e691254839` (smart tree `41a1c897deb84c1371b255bb7f12702edf21aaa0`) and UI registry `77992771b93cc3732fce923f6d4e65a116ad6c47` (registry file SHA-256 `899085b429ba5ae06e0d6234d4f6ac96e4ecba698a94ae38e9866364d155d675`), producing `ui-56cd91e1d7a3-smart-1f898fc321c0-registry-899085b4-exact-git-tree-v2`. The artifact was materialized with `scripts/package-frontend-runtime-artifact.py` from the local Framework repositories.

Canonical proof continues in Root evidence `data-view-duplicate-definition-canonical-20260927.md`.
