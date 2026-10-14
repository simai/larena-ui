import {html, nothing} from "lit";
import "./navigation.css";
import {getInitials} from "../../../helpers/text";
import {renderPalette, renderSearchRow} from "./palette";
import {renderPersonButton} from "./account";
import {renderEditBanner, renderEditRoots, renderEditRows} from "./edit";

// The two columns, drawn in the element's own light DOM like every other Smart
// component, so the Framework's component stylesheets reach the icons, badges
// and fields inside it.
//
// First column: the site, the site's roots, the install apart from them, the
// favourites, and at the bottom only «who am I» -- notifications and the person.
// Second column: the open root's rows. There is no third: the page is the
// third, in the host's own area (assignment, section 3).

const icon = (name, rootClass = "flex-none") => (name
    ? html`<sf-icon root-class=${rootClass} aria-hidden="true" icon=${name}></sf-icon>`
    : nothing);

// «Требует внимания» is the only thing on the right of a row. The menu carries
// no totals; those belong in the page header.
const attention = (row) => {
    if (row.attention) {
        return html`<sf-badge class="sf-admin-nav-attention" appearance="tonal" size="1/3"
            status=${row.attention.tone} text=${row.attention.label}></sf-badge>`;
    }
    if (row.badge) {
        return html`<sf-badge class="sf-admin-nav-attention" size="1/3" scheme="primary"
            text=${String(row.badge)}></sf-badge>`;
    }
    if (row.shortcut) return html`<kbd class="sf-admin-nav-kbd">${row.shortcut}</kbd>`;
    return nothing;
};

const mark = (site) => html`<span class="sf-admin-nav-mark" aria-hidden="true">${site?.mark || getInitials(site?.label ?? "")}</span>`;

function renderSite(context) {
    const {controller, navigation, compact} = context;
    const site = navigation?.site?.current;
    if (!site) return nothing;
    const switchable = controller.canSwitchSite();
    const trigger = html`
        <button type="button" class="sf-admin-nav-site-trigger"
            aria-haspopup=${switchable ? "menu" : nothing}
            aria-expanded=${switchable ? "false" : nothing}
            ?disabled=${!switchable}
            title=${compact ? site.label : nothing}
            @click=${(event) => controller.openSites(event.currentTarget)}>
            ${mark(site)}
            ${compact ? nothing : html`
                <span class="sf-admin-nav-site-text">
                    <span class="sf-admin-nav-site-label">${site.label}</span>
                    ${site.caption ? html`<span class="sf-admin-nav-site-caption">${site.caption}</span>` : nothing}
                </span>
                ${switchable ? icon("unfold_more", "sf-admin-nav-site-chevron") : nothing}`}
        </button>`;
    return html`<div class="sf-admin-nav-site">${trigger}</div>`;
}

// A row of the first column. A root with rows opens the second column and
// carries ›; a root with none is a page and opens it (assignment, section 1).
function renderFirstRow(context, row) {
    const {controller, compact, openRoot, text} = context;
    // A person's own rows at the top level: a line, a heading, a link, as in a
    // root's column. The top level had no add, so it never met them.
    if (row.kind === "divider") return html`<hr class="sf-admin-nav-divider">`;
    if (row.kind === "heading") return compact ? nothing : html`<div class="sf-admin-nav-heading">${row.label}</div>`;
    if (row.kind === "link") {
        return html`<a class="sf-admin-nav-row sf-admin-nav-row--first" href=${row.href ?? "#"} target="_blank" rel="noopener"
            title=${compact ? row.label : nothing} aria-label=${compact ? row.label : nothing}>
            ${icon(row.icon, "sf-admin-nav-row-icon")}
            ${compact ? nothing : html`<span class="sf-admin-nav-row-label">${row.label}</span>
                ${icon("north_east", "sf-admin-nav-row-chevron")}<span class="sr-only">, ${text("external")}</span>`}
        </a>`;
    }
    const opens = row.kind === "root";
    const open = opens && openRoot?.key === row.key;
    const current = opens ? controller.holdsCurrent(row) : controller.isCurrent(row.key);
    return html`<a class="sf-admin-nav-row sf-admin-nav-row--first"
        href=${row.href ?? "#"}
        role=${opens ? "button" : nothing}
        aria-expanded=${opens ? String(open) : nothing}
        aria-current=${current && !opens ? "page" : nothing}
        data-current=${current ? "true" : nothing}
        data-open=${open ? "true" : nothing}
        title=${compact ? row.label : nothing}
        aria-label=${compact ? row.label : nothing}
        @click=${(event) => controller.openRow(row, event)}>
        ${icon(row.icon, "sf-admin-nav-row-icon")}
        ${compact ? (row.attention || row.badge ? html`<span class="sf-admin-nav-dot" aria-hidden="true"></span>` : nothing) : html`
            <span class="sf-admin-nav-row-label">${row.label}</span>
            ${attention(row)}
            ${opens ? icon("chevron_right", "sf-admin-nav-row-chevron") : nothing}`}
    </a>`;
}

function renderFavourites(context) {
    const {controller, text, compact} = context;
    const all = controller.favourites();
    if (!all.length || compact) return nothing;
    return html`<div class="sf-admin-nav-favourites">
        <div class="sf-admin-nav-group-head">
            <span>${text("favourites")}</span>
            <button type="button" class="sf-admin-nav-link-button"
                aria-expanded=${String(!controller.favouritesFolded)}
                @click=${() => controller.toggleFavouritesFolded()}>${controller.favouritesFolded ? text("unfold") : text("fold")}</button>
        </div>
        ${controller.shownFavourites().map((entry) => html`<a class="sf-admin-nav-row sf-admin-nav-row--first"
            href=${entry.href}
            data-current=${controller.isCurrent(entry.key) ? "true" : nothing}
            @click=${(event) => controller.openRow({...entry, kind: "page"}, event)}>
            ${icon(entry.icon, "sf-admin-nav-row-icon")}
            <span class="sf-admin-nav-row-label">${entry.label}</span>
            ${attention(findRow(context, entry.key) ?? {})}
        </a>`)}
    </div>`;
}

function findRow(context, key) {
    for (const root of context.navigation?.items ?? []) {
        if (root.key === key) return root;
        for (const row of root.children ?? []) if (row.key === key) return row;
    }
    return null;
}

function renderBottom(context) {
    const {navigation, text, compact} = context;
    const notifications = navigation?.notifications;
    return html`<div class="sf-admin-nav-bottom">
        ${notifications ? html`<div class="sf-admin-nav-row sf-admin-nav-row--first" title=${compact ? text("notifications") : nothing}>
            ${icon("notifications", "sf-admin-nav-row-icon")}
            ${compact
                ? (notifications.unread ? html`<span class="sf-admin-nav-dot" aria-hidden="true"></span>` : nothing)
                : html`<span class="sf-admin-nav-row-label">${text("notifications")}</span>
                    ${notifications.unread ? html`<sf-badge class="sf-admin-nav-attention" size="1/3" status="error"
                        text=${String(notifications.unread)}></sf-badge>` : nothing}`}
        </div>` : nothing}
        <div class="sf-admin-nav-person-row">
            ${renderPersonButton(context)}
            ${renderCollapse(context)}
        </div>
    </div>`;
}

// Folding the first column to a rail: a small button beside the person, not
// a row of its own under it (owner, 2026-10-14). In the rail it stands under
// the picture.
function renderCollapse(context) {
    const {controller, text, compact} = context;
    if (!context.wide || controller.edit?.on) return nothing;
    return html`<sf-icon-button class="sf-admin-nav-collapse" size="1/2" appearance="ghost" scheme="on-surface"
        icon=${compact ? "chevron_right" : "chevron_left"}
        aria-label=${compact ? text("expand") : text("collapse")}
        title=${compact ? text("expand") : text("collapse")}
        @click=${() => controller.toggleCompact()}></sf-icon-button>`;
}

// The columns are widened and narrowed by their edges (owner, 2026-10-14),
// within limits, the width remembered in this browser; a double click on the
// edge gives the column its width back. Not in the rail or on a phone.
function renderResizer(context, column) {
    if (!context.wide || (column === "first" && context.compact)) return nothing;
    const limits = column === "first" ? {min: "13rem", max: "24rem"} : {min: "13rem", max: "28rem"};
    // The width the host keeps for the person, when it does.
    const kept = context.navigation?.person?.widths?.[column];
    return html`<sf-resizer min=${limits.min} max=${limits.max} value=${kept ? String(kept) : nothing}
        property=${`--sf-admin-nav-${column}--inline-size`} storage-key=${`admin-nav-${column}`}
        label=${context.text(column === "first" ? "menuWidth" : "columnWidth")}></sf-resizer>`;
}

function renderFirstColumn(context) {
    const {controller, text, compact, edit} = context;
    const site = controller.siteRoots();
    const install = controller.installRoots();
    if (edit.on && !compact) {
        return html`<div class="sf-admin-nav-first" data-editing="true">
            ${renderSite(context)}
            ${renderEditBanner(context)}
            <div class="sf-admin-nav-scroll">${renderEditRoots(context, site, install)}</div>
            ${renderBottom(context)}
            ${renderResizer(context, "first")}
        </div>`;
    }
    return html`<div class="sf-admin-nav-first" data-compact=${compact ? "true" : nothing}>
        ${renderSite(context)}
        <div class="sf-admin-nav-scroll">
            <div class="sf-admin-nav-rows">
                ${renderSearchRow(context)}
                ${site.map((row) => renderFirstRow(context, row))}
            </div>
            ${install.length ? html`<hr class="sf-admin-nav-divider">
                <div class="sf-admin-nav-rows">${install.map((row) => renderFirstRow(context, row))}</div>` : nothing}
            ${renderFavourites(context)}
        </div>
        ${renderBottom(context)}
        ${renderResizer(context, "first")}
    </div>`;
}

// A row of the second column: a heading, a divider, a page, a section that
// unfolds in place, or an external link.
function renderRow(context, row, depth = 0) {
    const {controller, text} = context;
    if (row.kind === "heading") return html`<div class="sf-admin-nav-heading">${row.label}</div>`;
    if (row.kind === "divider") return html`<hr class="sf-admin-nav-divider">`;
    const section = row.kind === "section";
    const unfolded = section && controller.unfolded.has(row.key);
    const external = row.kind === "link";
    const starred = controller.isFavourite(row.key);
    const canStar = controller.offers("favourites") && !section && !external;
    return html`<div class="sf-admin-nav-item" data-depth=${depth}>
        <a class="sf-admin-nav-row"
            href=${row.href ?? "#"}
            target=${external ? "_blank" : nothing}
            rel=${external ? "noopener" : nothing}
            role=${section ? "button" : nothing}
            aria-expanded=${section ? String(unfolded) : nothing}
            aria-current=${controller.isCurrent(row.key) ? "page" : nothing}
            data-current=${controller.isCurrent(row.key) ? "true" : nothing}
            @click=${(event) => controller.openRow(row, event)}>
            ${icon(row.icon, "sf-admin-nav-row-icon")}
            <span class="sf-admin-nav-row-label">${row.label}</span>
            ${external ? html`${icon("north_east", "sf-admin-nav-row-chevron")}<span class="sr-only">, ${text("external")}</span>` : nothing}
            ${section ? icon(unfolded ? "expand_less" : "expand_more", "sf-admin-nav-row-chevron") : nothing}
        </a>
        ${canStar ? html`<button type="button" class="sf-admin-nav-star"
            data-on=${starred ? "true" : nothing}
            aria-pressed=${String(starred)}
            aria-label=${starred ? text("unstar") : text("star")}
            title=${starred ? text("unstar") : text("star")}
            @click=${() => controller.toggleFavourite(row)}>${icon(starred ? "star" : "star_outline")}</button>` : nothing}
        ${attention(row)}
    </div>
    ${unfolded ? html`<div class="sf-admin-nav-children">${(row.children ?? []).filter((child) => !child.hidden)
        .map((child) => renderRow(context, child, depth + 1))}</div>` : nothing}`;
}

function renderSecondColumn(context) {
    const {controller, navigation, text, openRoot, filter, wide} = context;
    if (!openRoot) return nothing;
    const install = openRoot.level === "install";
    const rows = controller.rowsOf(openRoot);
    return html`<div class="sf-admin-nav-second" aria-label=${openRoot.label}>
        <div class="sf-admin-nav-second-head">
            ${wide ? nothing : html`<sf-icon-button size="1/2" appearance="ghost" scheme="on-surface"
                icon="arrow_back" aria-label=${text("back")} @click=${() => controller.back()}></sf-icon-button>`}
            <div class="sf-admin-nav-second-title">
                <span class="sf-admin-nav-second-label">${openRoot.label}</span>
                <span class="sf-admin-nav-second-caption" data-install=${install ? "true" : nothing}>${install
                    ? text("allSites")
                    : navigation?.site?.current?.label ?? ""}</span>
            </div>
            ${wide ? html`<sf-icon-button size="1/2" appearance="ghost" scheme="on-surface"
                icon="left_panel_close" aria-label=${text("hideColumn")} title=${text("hideColumn")}
                @click=${() => controller.hideColumn()}></sf-icon-button>` : nothing}
        </div>
        ${context.edit.on ? html`<div class="sf-admin-nav-scroll">${renderEditRows(context, openRoot)}</div>` : nothing}
        ${!context.edit.on && controller.offersFind(openRoot) ? html`<div class="sf-admin-nav-find">
            <sf-input size="1/2" icon-start="search" placeholder=${text("findInSection")} value=${filter}
                @input=${(event) => controller.setFilter(event.target?.value ?? event.detail?.value ?? "")}></sf-input>
        </div>` : nothing}
        ${context.edit.on ? nothing : html`<div class="sf-admin-nav-scroll">
            ${rows.length
                ? rows.map((row) => renderRow(context, row))
                : html`<p class="sf-admin-nav-empty">${text("nothingFound")}</p>`}
        </div>`}
        ${openRoot.action ? html`<div class="sf-admin-nav-second-foot">
            <a class="sf-admin-nav-action" href=${openRoot.action.href ?? "#"}>
                ${icon(openRoot.action.icon || "add")}<span>${openRoot.action.label}</span></a>
        </div>` : nothing}
        ${renderResizer(context, "second")}
    </div>`;
}

export function renderNavigationTemplate(context) {
    const {status, text, controller, wide, screen, compact} = context;
    if (status === "loading") {
        return html`<nav class="sf-admin-nav" aria-busy="true"><div class="sf-admin-nav-first">
            <p class="sf-admin-nav-empty">${text("loading")}</p></div></nav>`;
    }
    if (status !== "ready") {
        return html`<nav class="sf-admin-nav"><div class="sf-admin-nav-first">
            <p class="sf-admin-nav-empty">${context.message || text("unavailable")}</p>
            <sf-button size="1/2" appearance="outline" @click=${() => controller.load("requested")} text=${text("retry")}></sf-button>
        </div></nav>`;
    }
    // On a phone the two columns are two screens; the second has «←».
    const phoneSecond = !wide && screen === "second";
    return html`<nav class="sf-admin-nav" data-wide=${wide ? "true" : "false"} data-compact=${compact ? "true" : nothing}>
        ${phoneSecond ? nothing : renderFirstColumn(context)}
        ${wide || phoneSecond ? renderSecondColumn(context) : nothing}
        ${renderPalette(context)}
    </nav>`;
}
