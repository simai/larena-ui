# Implementation summary

UI now owns validated Smart View descriptors, registered view resolution,
bounded composite rendering and exact child asset aggregation behind the public
`Smart` facade. The table product view composes replaceable toolbar, table and
pagination children.

Nested `_props` and `_children` overrides keep deeper controls configurable
without exposing template paths. The toolbar now composes registered
`ui.input` and `ui.button` views, so both remain independently replaceable.
