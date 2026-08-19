# Declarative Smart UI A0-A1 evidence

Base revision: `5127f475e6679a1164590698da2e0fef149da313`.

Implemented surfaces:

- validated Smart View v1 JSON descriptor and schema;
- registered view resolution behind `Larena\Ui\Facades\Smart`;
- bounded recursive composite renderer with cycle/depth guards;
- exact child asset aggregation;
- Admin collection, table Dataview and toolbar composite views;
- safe runtime child-prop overrides for declared child identifiers only;
- fail-closed nested child overrides plus replaceable input and button views for
  the table toolbar;
- replaceable dropdown views and declared filter, sorting and pagination slots
  for a backend-configured table query toolbar.
- safe same-origin continuation links rendered inside the registered
  pagination Smart Component, with the opaque token remaining backend-owned.

Checks on PHP 8.4.20:

- `composer validate --no-check-publish`: pass;
- `composer lint`: pass;
- `composer analyse`: pass;
- `composer test`: pass;
- JSON parse validation for all new schemas, manifests, views and examples: pass;
- focused negative checks for unsafe values, unknown presets/views/children and manifest collisions: pass.

Compatibility: additive. Existing `Smart::render()` calls remain valid. New
composites stay hidden from the constructor atlas until A5 supplies complete
localized authoring metadata.

Blockers: none for A1. Browser parity and real query interactions remain A2-A3.

Rollback: revert only the files listed by the matching launch record. No data,
database schema, active runtime or external environment is changed by A0-A1.
