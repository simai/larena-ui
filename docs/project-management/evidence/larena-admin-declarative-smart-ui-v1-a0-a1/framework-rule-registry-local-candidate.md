# Framework rule-registry local candidate

The local Developer Preview pins `simai/ui` commit
`c606f6125993ad310be5ef64bab97c6db1052328` with the unchanged
`simai/ui-smart` commit `dd786bbae98391fb21df9b4e1e6cd402ead0614c`.

The UI distribution was rebuilt twice from the canonical `ui-loader` source
through `ui-builder`. Both waves produced byte-identical `distr/rule`
artifacts with empty build diagnostics. The resulting rule manifest is valid
JSON, contains unique names, declares one `cl-alert`, and does not request a
component-owned alert stylesheet.

The candidate is local-only. It does not create or imply a public tag,
publication, deployment or production-readiness claim.
