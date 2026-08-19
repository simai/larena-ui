# Implementation summary

UI now owns validated Smart View descriptors, registered view resolution,
bounded composite rendering and exact child asset aggregation behind the public
`Smart` facade. The table product view composes replaceable toolbar, table and
pagination children.

Nested `_props` and `_children` overrides keep deeper controls configurable
without exposing template paths. The toolbar now composes registered
`ui.input` and `ui.button` views, so both remain independently replaceable.
The same JSON composition now includes a replaceable saved-view dropdown;
selection semantics and persistence remain outside UI in Admin, Root and Setting.

The A4 extension adds `admin.record_editor` as a replaceable composite shell.
Its dynamic field and action slots accept backend-rendered Smart artifacts;
the UI component owns no Storage data or mutation effect.
