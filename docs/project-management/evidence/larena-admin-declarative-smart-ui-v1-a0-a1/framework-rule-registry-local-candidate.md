# Framework rule-registry local candidate

The local Developer Preview pins `simai/ui` commit
`fba98acad7a0225cd949fd8d4652176b49ac41ac` with the unchanged
`simai/ui-smart` commit `dd786bbae98391fb21df9b4e1e6cd402ead0614c`.

The complete Framework core distribution was rebuilt twice from canonical
sources through `ui-builder`. Both waves produced byte-identical artifacts
with empty build diagnostics. The resulting rule manifest is valid JSON,
contains unique names, declares one `cl-alert`, and does not request a
component-owned alert stylesheet. The core rule bundle contains no merge
markers, and every font referenced by the generated core stylesheet is
present in the immutable distribution.

The candidate is local-only. It does not create or imply a public tag,
publication, deployment or production-readiness claim.
