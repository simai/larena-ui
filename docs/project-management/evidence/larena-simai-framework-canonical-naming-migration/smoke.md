# Package smoke evidence

- `FrontendRuntimeLock::bundled()` loads `resources/sf/runtime-lock.json` and
  validates pair `sf-v5.3.2-7e836d8a-dd786bba` against the unchanged pinned
  commits.
- `SmartRegistry::withDefaults()` resolves the `ui.button` manifest through
  renderer `ui.sf_element`.
- The button reference exposes `Button` / `Кнопка` and Simai Framework copy,
  while rendering the existing `<sf-button>` element.
- Source-backed descriptor resources resolve under
  `resources/assets/source-backed-sf/`.
- Both button carriers plus tags and pagination catalog CSS are byte-identical
  to their pinned `ui/distr/component/*/css` provenance sources. Admin catalog
  and standalone binding diagnostics pass without an exception or weakened
  comparison.
- The action-link marker and responsive selector agree on
  `larena/ui:sf_action_link`.
- Active runtime, UI, tests and package metadata contain no generation-specific
  naming. Old terms remain only in immutable historical evidence and explicit
  deletion tombstones required by the scope checker.

Browser publication and cross-package route smoke remain part of the shared
Larena closeout and are not claimed by this UI-only packet.
