# Concept alignment: Larena UI

## Accepted role

UI owns Smart Component manifests, registry, renderer allowlist, design tokens, asset requirements and safe rendering of normalized input. It does not own page composition or stored records.

Accepted Target State: `larena.target.minimal_cms_v1` at semantic digest `sha256:2793f61ba9563839831d57e87ac5cd6399c37a3183f1a68981b6fc7f941a1ad2`.

## Dependency and ownership boundary

- Mandatory Larena dependencies: Core and Dataview.
- Layout supplies component identifiers through a render plan at integration time but is not a UI dependency.
- Unknown components, renderers, props, sources and assets fail closed.

## Continuation strategy

The existing registry and reference contracts are retained. B3 removes the Layout edge and adds Core; B11 completes manifest/allowlist/asset safety without introducing server-side frontend build requirements.

## Current alignment gap

B3 declares only Core and Dataview as mandatory Larena dependencies and keeps Layout as a development-only compatibility surface. Safe renderer behavior requires B11 capability verification.

## Install and rollback baseline

Install through the Root Composer lock and package discovery; assets must be declared rather than built remotely at runtime. B0 is documentation-only. Rollback restores the previous verified Root lock, manifest registry and packaged asset set.

## Verification

Run package tests, `composer validate`, dependency reporting, manifest validation and unknown-component/renderer/prop/source fail-closed probes.
