import {html, nothing} from "lit";
import {LIST_ID} from "./controller";

// The edit mode (assignment, section 7): both columns at once, a banner with
// the profile being edited and what that means, and on every row a handle, an
// eye and a ⋯. Rows are moved with sf-sortable -- a mouse, a finger on the
// handle, or the keyboard -- and only here: a drag outside the edit mode is
// not an edit (owner, 2026-10-11). Everything applies at once.

const icon = (name, rootClass = "sf-admin-nav-row-icon") => (name
    ? html`<sf-icon root-class=${rootClass} aria-hidden="true" icon=${name}></sf-icon>`
    : nothing);

// What a new profile can be. A person has one personal profile, so with one
// already made a new profile is shared -- offered only to whoever may make
// shared ones; without one, it is theirs unless they may and choose otherwise.
export function newProfileKinds(controller) {
    const navigation = controller.navigation;
    const hasPersonal = (navigation?.profiles?.list ?? []).some((entry) => entry.kind === "personal");
    const mayShare = Boolean(navigation?.rights?.create_shared);
    return {personal: !hasPersonal && Boolean(navigation?.rights?.edit_personal), shared: mayShare};
}

function renderNaming(context, mode) {
    const {controller, text} = context;
    const current = controller.currentProfile();
    const kinds = newProfileKinds(controller);
    let label = mode === "rename" ? current?.label ?? "" : "";
    // Shared unless a personal one can still be made, in which case the person
    // chooses -- and only if they may share at all.
    let sharedOn = !kinds.personal;
    const submit = () => (mode === "rename"
        ? controller.renameProfile(label)
        : controller.createProfile(label, sharedOn));
    return html`<form class="sf-admin-nav-edit-form" @submit=${(event) => { event.preventDefault(); submit(); }}>
        <sf-input size="1/2" value=${label} placeholder=${text("linkLabel")}
            @input=${(event) => { label = event.target?.value ?? event.detail?.value ?? ""; }}></sf-input>
        ${mode === "new" && kinds.personal && kinds.shared ? html`<label class="sf-admin-nav-edit-check">
            <input type="checkbox" @change=${(event) => { sharedOn = event.target.checked; }}>
            <span>${text("sharedProfile")}</span></label>` : nothing}
        <div class="sf-admin-nav-edit-actions">
            <sf-button size="1/2" @click=${submit} text=${mode === "rename" ? text("save") : text("create")}></sf-button>
            <sf-button size="1/2" appearance="ghost" scheme="on-surface"
                @click=${() => controller.startNaming(null)} text=${text("cancel")}></sf-button>
        </div>
    </form>`;
}

function renderProfileList(context) {
    const {controller, navigation, text, edit} = context;
    const profiles = navigation.profiles;
    const current = controller.currentProfile();
    return html`<div class="sf-admin-nav-edit-profiles" role="dialog" aria-label=${text("menuProfile")}>
        <ul class="sf-admin-nav-account-list" role="listbox" aria-label=${text("menuProfile")}>
            ${profiles.list.map((entry) => html`<li role="option" aria-selected=${String(entry.key === profiles.current)}>
                <button type="button" class="sf-admin-nav-row" ?disabled=${!controller.mayEdit(entry)}
                    @click=${() => controller.editProfile(entry.key)}>
                    ${icon(entry.kind === "personal" ? "person" : "group")}
                    <span class="sf-admin-nav-row-label">${entry.label}</span>
                    ${entry.key === profiles.current ? icon("check", "sf-admin-nav-row-chevron") : nothing}
                </button>
            </li>`)}
        </ul>
        <hr class="sf-admin-nav-divider">
        ${edit.naming ? renderNaming(context, edit.naming) : html`
            ${newProfileKinds(controller).personal || newProfileKinds(controller).shared ? html`<button type="button"
                class="sf-admin-nav-row" @click=${() => controller.startNaming("new")}>
                ${icon("add")}<span class="sf-admin-nav-row-label">${text("newProfile")}</span></button>` : nothing}
            ${current?.renamable ? html`<button type="button" class="sf-admin-nav-row" @click=${() => controller.startNaming("rename")}>
                ${icon("edit")}<span class="sf-admin-nav-row-label">${text("renameProfile")}</span></button>` : nothing}
            ${current?.removable ? html`<button type="button" class="sf-admin-nav-row sf-admin-nav-row--danger"
                @click=${() => controller.deleteProfile()}>
                ${icon("delete")}<span class="sf-admin-nav-row-label">${text("deleteProfile")}</span></button>` : nothing}`}
    </div>`;
}

export function renderEditBanner(context) {
    const {controller, text, edit} = context;
    const current = controller.currentProfile();
    const shared = current?.kind === "shared";
    return html`<div class="sf-admin-nav-edit-banner" role="region" aria-label=${text("editMenu")}>
        <div class="sf-admin-nav-edit-title">
            ${icon("edit", "")}
            <span class="sf-admin-nav-row-label">${text("editMenu")}</span>
            <sf-button size="1/2" @click=${() => controller.stopEdit()} text=${text("done")}></sf-button>
        </div>
        <button type="button" class="sf-admin-nav-edit-profile" aria-haspopup="dialog"
            aria-expanded=${String(edit.profiles)} @click=${() => controller.toggleEditProfiles()}>
            ${icon(shared ? "group" : "person")}
            <span class="sf-admin-nav-row-label">${text("profileLabel")} <b>${current?.label ?? ""}</b></span>
            ${icon("expand_more", "sf-admin-nav-row-chevron")}
        </button>
        ${edit.profiles ? renderProfileList(context) : nothing}
        <p class="sf-admin-nav-edit-note">${shared ? text("noteShared") : text("notePersonal")}</p>
        ${edit.message ? html`<p class="sf-admin-nav-edit-message" role="alert">${edit.message}</p>` : nothing}
        <sf-button size="1/2" appearance="outline" scheme="on-surface" icon-start="restart_alt"
            @click=${() => controller.resetProfile()} text=${text("resetToPackages")}></sf-button>
    </div>`;
}

function renderRowMenu(context, row) {
    const {controller, text} = context;
    const renamed = row.package_label && row.label !== row.package_label;
    return html`<div class="sf-admin-nav-row-menu" role="menu">
        ${row.kind !== "divider" ? html`<button type="button" role="menuitem" class="sf-admin-nav-row"
            @click=${() => { controller.edit = {...controller.edit, renaming: row.key, rowMenu: null}; controller.update("edit"); }}>
            ${icon("edit")}<span class="sf-admin-nav-row-label">${text("rename")}</span></button>` : nothing}
        ${renamed ? html`<button type="button" role="menuitem" class="sf-admin-nav-row"
            @click=${() => controller.returnPackageName(row.key)}>
            ${icon("undo")}<span class="sf-admin-nav-row-label">${text("packageName")}</span></button>` : nothing}
        ${row.own ? html`<button type="button" role="menuitem" class="sf-admin-nav-row sf-admin-nav-row--danger"
            @click=${() => controller.removeOwnRow(row.key)}>
            ${icon("delete")}<span class="sf-admin-nav-row-label">${text("deleteRow")}</span></button>` : nothing}
    </div>`;
}

function renderRenaming(context, row) {
    const {controller} = context;
    let value = row.label ?? "";
    return html`<form class="sf-admin-nav-edit-rename" @submit=${(event) => { event.preventDefault(); controller.renameRow(row.key, value); }}
        @keydown=${(event) => { if (event.key === "Escape") { event.stopPropagation(); controller.renameRow(row.key, ""); } }}>
        <sf-input size="1/2" value=${value} autofocus
            @input=${(event) => { value = event.target?.value ?? event.detail?.value ?? ""; }}></sf-input>
    </form>`;
}

// One editable row: the handle, what it is, and its eye and ⋯. A divider is a
// line with the same controls, so it can be moved and hidden like a row.
function renderEditRow(context, row, {draggable = true} = {}) {
    const {controller, text, edit, openRoot} = context;
    const menuOpen = edit.rowMenu === row.key;
    const name = row.kind === "divider" ? text("addDivider") : row.label;
    // A root still opens its column in the edit mode: its rows are edited there.
    const label = row.kind === "root"
        ? html`<button type="button" class="sf-admin-nav-edit-open" aria-expanded=${String(openRoot?.key === row.key)}
            data-open=${openRoot?.key === row.key ? "true" : nothing}
            @click=${() => controller.openRootColumn(row.key)}>
            ${icon(row.icon)}<span class="sf-admin-nav-row-label">${row.label}</span>
            ${icon("chevron_right", "sf-admin-nav-row-chevron")}</button>`
        : html`${row.kind === "heading" ? nothing : icon(row.icon)}
            <span class="sf-admin-nav-row-label" data-heading=${row.kind === "heading" ? "true" : nothing}>${row.label}</span>`;
    return html`<div class="sf-admin-nav-edit-row" data-sf-sortable-item=${draggable ? row.key : nothing}
        data-hidden=${row.hidden ? "true" : nothing} data-kind=${row.kind}>
        ${draggable ? html`<span class="sf-admin-nav-edit-handle" data-sf-sortable-handle aria-hidden="true"
            title=${text("drag")}>${icon("drag_indicator", "")}</span>` : html`<span class="sf-admin-nav-edit-handle"></span>`}
        ${edit.renaming === row.key ? renderRenaming(context, row) : row.kind === "divider"
            ? html`<hr class="sf-admin-nav-divider sf-admin-nav-edit-line" aria-label=${name}>`
            : label}
        <button type="button" class="sf-admin-nav-edit-button" aria-pressed=${String(!row.hidden)}
            aria-label=${`${row.hidden ? text("show") : text("hide")}: ${name}`} title=${row.hidden ? text("show") : text("hide")}
            @click=${() => controller.toggleHidden(row.key)}>${icon(row.hidden ? "visibility_off" : "visibility", "")}</button>
        <button type="button" class="sf-admin-nav-edit-button" aria-haspopup="menu" aria-expanded=${String(menuOpen)}
            aria-label=${`${text("more")}: ${name}`} title=${text("more")}
            @click=${() => { controller.edit = {...controller.edit, rowMenu: menuOpen ? null : row.key}; controller.update("edit"); }}>
            ${icon("more_horiz", "")}</button>
        ${menuOpen ? renderRowMenu(context, row) : nothing}
    </div>`;
}

// The site's roots are one list to reorder; the install stays apart, as it is
// outside the edit mode, because it is not the site's.
export function renderEditRoots(context, siteRoots, installRoots) {
    const {text} = context;
    return html`<sf-sortable id=${`${LIST_ID}roots`} group="admin-nav-roots" label=${text("editMenu")}
        class="sf-admin-nav-edit-list">
        ${siteRoots.map((row) => renderEditRow(context, row))}
    </sf-sortable>
    ${installRoots.length ? html`<hr class="sf-admin-nav-divider">
        ${installRoots.map((row) => renderEditRow(context, row, {draggable: false}))}` : nothing}`;
}

function renderAdd(context, root) {
    const {controller, text, edit} = context;
    if (edit.adding === "link") {
        let label = "";
        let href = "";
        return html`<form class="sf-admin-nav-edit-form" @submit=${(event) => {
            event.preventDefault();
            if (label.trim() && href.trim()) controller.addOwnRow(root.key, "link", {label: label.trim(), href: href.trim()});
        }}>
            <sf-input size="1/2" placeholder=${text("linkLabel")} @input=${(event) => { label = event.target?.value ?? ""; }}></sf-input>
            <sf-input size="1/2" placeholder=${text("linkHref")} @input=${(event) => { href = event.target?.value ?? ""; }}></sf-input>
            <div class="sf-admin-nav-edit-actions">
                <sf-button size="1/2" @click=${(event) => event.target.closest("form")?.requestSubmit()} text=${text("add")}></sf-button>
                <sf-button size="1/2" appearance="ghost" scheme="on-surface"
                    @click=${() => { controller.edit = {...controller.edit, adding: null}; controller.update("edit"); }} text=${text("cancel")}></sf-button>
            </div>
        </form>`;
    }
    if (edit.adding === "menu") {
        return html`<div class="sf-admin-nav-row-menu sf-admin-nav-add-menu" role="menu">
            <button type="button" role="menuitem" class="sf-admin-nav-row" @click=${() => controller.addOwnRow(root.key, "divider")}>
                ${icon("horizontal_rule")}<span class="sf-admin-nav-row-label">${text("addDivider")}</span></button>
            <button type="button" role="menuitem" class="sf-admin-nav-row" @click=${() => controller.addOwnRow(root.key, "heading")}>
                ${icon("title")}<span class="sf-admin-nav-row-label">${text("addHeading")}</span></button>
            <button type="button" role="menuitem" class="sf-admin-nav-row"
                @click=${() => { controller.edit = {...controller.edit, adding: "link"}; controller.update("edit"); }}>
                ${icon("link")}<span class="sf-admin-nav-row-label">${text("addLink")}</span></button>
        </div>`;
    }
    return html`<button type="button" class="sf-admin-nav-row sf-admin-nav-add" aria-haspopup="menu"
        @click=${() => { controller.edit = {...controller.edit, adding: "menu"}; controller.update("edit"); }}>
        ${icon("add")}<span class="sf-admin-nav-row-label">${text("add")}</span></button>`;
}

export function renderEditRows(context, root) {
    const {text} = context;
    return html`<sf-sortable id=${`${LIST_ID}${root.key}`} group=${`admin-nav-rows`} label=${root.label ?? text("editMenu")}
        class="sf-admin-nav-edit-list">
        ${(root.children ?? []).map((row) => renderEditRow(context, row))}
    </sf-sortable>
    ${renderAdd(context, root)}`;
}
