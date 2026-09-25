# Data View duplicate-definition pair — 2026-09-25

Framework delivered an isolated local correction for duplicate `sf-table` and `sf-pagination` registrations when `data-view.js` and standalone table/pagination scripts are both loaded. The source adds guards before the two `define()` calls. Framework reproduced both warnings on the old Smart bundle and reported zero warnings/page errors for both loading orders on the new built bundle. The number of downloaded files is unchanged.

The laboratory pinned exact Core `56cd91e1d7a3dc19b32a2acfdaa1389744e174a7`, Smart runtime `b79ef84d74acefbfd1409ef90f8024f0ea2d5712`, and UI registry `b7a639f364943e36c5067fb169ac26a5e1978aa3`, producing `ui-56cd91e1d7a3-smart-b79ef84d74ac-registry-fbbe461a-exact-git-tree-v2`. The registry file SHA-256 is `fbbe461a41857fbd7349fb392679e608a468b219ec2978ca18d7cf9c8607ef13`; the resulting runtime lock SHA-256 is `36e90d72ee971584e686ab80c92f885547952c640ad9ef065f90e51d1689e7e9`. `pin-framework-pair.sh` verified publication of the exact artifact.

The full UI `composer run quality:gate` passed on PHP 8.4 with the pinned Core. Framework's own source, Smart, registry and Chromium checks are recorded in `/Users/rim/Documents/GitHub/.worktrees/ui-control-data-view-duplicate-handoff/source/handoff/2026-09-25-framework-dataview-duplicate-registration-guard.md`.

This is laboratory package proof. The canonical 23-package Root, authenticated CMS page, Admin activation digest and its runtime have not yet moved to this pair. Continue those as separate integration and browser acceptance steps.
