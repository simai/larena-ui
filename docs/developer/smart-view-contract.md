# Smart View Descriptor v1

`larena/ui` owns named visual/composition variants for registered Smart
Components. A Smart View is developer/designer-owned JSON and is not a page or
data record.

A view may declare:

- immutable component and view keys;
- inert default props;
- named presets that contribute allowlisted props;
- semantic modifiers that contribute allowlisted props;
- child Smart invocations assigned to declared parent slots;
- non-executable constraints.

The component manifest remains the capability source of truth. View resolution
must verify the component, view key, preset, modifiers, props, child component
keys and slots. Resolved props still pass `SmartPropsValidator`; rendered
components still use `Larena\Ui\Facades\Smart` and the container-owned
`SmartManager`.

Pages reference only stable component/view/preset/modifier keys. Therefore a
compatible template or renderer can change without rewriting page data. A
breaking prop or event contract requires a new manifest version and an
explicit descriptor migration.

The schema is `resources/schemas/smart-view.schema.json`; the first composition
example is `resources/examples/dataview-table.smart-view.json`.

Migration notes: additive contract only. Existing direct `Smart::render()`
calls remain valid. Rollback removes view registration and the new descriptors
without changing component manifests or stored page data.
