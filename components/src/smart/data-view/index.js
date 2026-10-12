// A composition shell. The host owns access, storage and query semantics;
// this element owns the browser lifecycle and projects answers into its slots.
import "../table/index.js";
import "../pagination/index.js";
// The notice is a toast, so the composite carries it the way it carries the
// table and the page row.
import "../toast/index.js";
import {pageText} from "../../core/js/page-language";
import {acceptLevel, collapse, createTree, expandKnown, hasLevel, knownRecords, markLoading, visibleRows} from "./js/tree.js";
const EVENTS = Object.freeze({
    "sf-table-query-intent": "query.change",
    "sf-table-template-select": "view.open",
    "sf-table-template-save": "view.save",
    "sf-table-template-delete": "view.delete",
    "sf-table-create-intent": "view.create_record",
});

let instanceNumber = 0;
// Every port version this composite accepts, in one place. The list used to be
// written inline in the guard, and two versions were published — 1.3.0 for a
// host that does not count, 1.4.0 for personal pinning — while the guard still
// named 1.2.0 as the newest it would take. A host moving to either would have
// been refused outright by the component that asked it to move. The contract
// test beside this file now compares the list with the manifest.
// The port speaks snake_case and the table reads camelCase, and the translation
// is this view's job: a column that named its property_type reached the table
// under a name nothing read, so the cell drew the stored value. Both names are
// kept -- the port's, because that is what the host sent and what a consumer
// reading the DOM expects, and the component's, because that is what the shared
// renderer set asks for.
const COLUMN_PRESENTATION = {
    property_type: "propertyType",
    min_width: "minWidth",
};

function projectColumn(column) {
    const projected = copy(column);
    for (const [sent, read] of Object.entries(COLUMN_PRESENTATION)) {
        if (projected[sent] !== undefined && projected[read] === undefined) {
            projected[read] = projected[sent];
        }
    }
    return projected;
}

const SUPPORTED_PORT_VERSIONS = ["1.0.0", "1.1.0", "1.2.0", "1.3.0", "1.4.0", "1.5.0", "1.6.0", "1.7.0", "1.8.0", "1.9.0"];
const DATA_VIEW_TEXT = {
    en: {
        showAll: "Show all",
        emptyText: "No data",
        closeNoticeLabel: "Close the message",
        statePortNotConnected: "The data port is not connected",
        stateUnavailable: "The data is temporarily unavailable",
        stateLoadFailed: "The data could not be loaded",
        stateLoading: "Loading",
        noticeFetchFailed: "The data could not be fetched. Try again.",
        noticeLoadFailed: "The data could not be loaded.",
        currentViewText: "Current",
        ofTotal: "{shown} of {total}",
        layoutLabel: "Data view",
        recordsLabel: "Records",
        noticeActionNotAllowed: "That action is not available for the chosen record.",
        noticeCapabilityNotProvided: "The host does not support that action.",
        noticePortNotConnected: "The data source is not connected.",
        noticeSavedViewLevels: "Shared views need a newer port.",
        noticeConflict: "The data changed. Read the view again and repeat the action.",
        noticeRefused: "The action was refused.",
        noticeUnavailable: "The action is unavailable.",
        noticeFailed: "The action was not carried out.",
        moveAction: "Move…",
        createChildAction: "Add a nested one",
        moveTitle: "Move «{label}»",
        moveRoot: "To the top level",
        movePlace: "Place",
        moveFirst: "First",
        moveLast: "Last",
        moveApply: "Move",
        moveNoTargets: "There is nowhere this record can be moved.",
        confirmTitle: "Confirm the change",
        confirmApply: "Confirm",
        cancel: "Cancel",
    },
    ru: {
        showAll: "Показать все",
        emptyText: "Нет данных",
        closeNoticeLabel: "Закрыть сообщение",
        statePortNotConnected: "Порт данных не подключён",
        stateUnavailable: "Данные временно недоступны",
        stateLoadFailed: "Не удалось загрузить данные",
        stateLoading: "Загрузка данных",
        noticeFetchFailed: "Не удалось получить данные. Повторите попытку.",
        noticeLoadFailed: "Не удалось загрузить данные.",
        currentViewText: "Текущий",
        ofTotal: "{shown} из {total}",
        layoutLabel: "Представление данных",
        recordsLabel: "Записи",
        noticeActionNotAllowed: "Это действие недоступно для выбранной записи.",
        noticeCapabilityNotProvided: "Хост не поддерживает это действие.",
        noticePortNotConnected: "Источник данных не подключён.",
        noticeSavedViewLevels: "Общие виды требуют более новой версии порта.",
        noticeConflict: "Данные изменились. Обновите представление и повторите действие.",
        noticeRefused: "Действие отклонено.",
        noticeUnavailable: "Действие недоступно.",
        noticeFailed: "Действие не выполнено.",
        moveAction: "Переместить…",
        createChildAction: "Добавить вложенную",
        moveTitle: "Переместить «{label}»",
        moveRoot: "На верхний уровень",
        movePlace: "Место",
        moveFirst: "Первым",
        moveLast: "Последним",
        moveApply: "Переместить",
        moveNoTargets: "Эту запись некуда переместить.",
        confirmTitle: "Подтвердите изменение",
        confirmApply: "Подтвердить",
        cancel: "Отмена",
    },
};
// The parts this element draws itself and a consumer can leave out. `status` is
// absent on purpose and cannot be switched off: a list that could not say a load
// failed would be a list that looks empty when it is broken, and an embedded one
// with a row cap and no chrome is exactly where someone would be tempted to
// remove it. `bulk` and `notice` are host slots that render nothing when empty,
// so switching them would be theatre.
// `select` and `settings` are the table's two chrome columns rather than parts of
// this element, and they are named here because they are furniture of the same
// kind: a checkbox column exists to feed a bulk band and a gear column exists to
// reach column settings, and an embedded list has neither. Named explicitly
// instead of inferred from the toolbar, so a consumer who does want a selection
// in an embedded list can ask for it -- `chrome="select"` -- rather than having to
// turn the whole toolbar back on to get a checkbox.
const CHROME_PARTS = ["toolbar", "pagination", "select", "settings"];

// Writes that cannot have changed a row, so the view does not re-query after
// them. Opening a record changes nothing either; the rest of the settings layer
// is the person's own view of the same rows.
const ANSWER_ONLY = new Set(["node.targets"]);
const TREE_INTENTS = new Set(["tree.expand", "node.targets", "node.move"]);

const NO_REFRESH_AFTER = new Set([
    "record.open",
    "tree.expand",
    "node.targets",
    "settings.save_personal",
    "settings.save_shared",
]);
const copy = (value) => (value == null ? value : JSON.parse(JSON.stringify(value)));

class SfDataView extends HTMLElement {
    constructor() {
        super();
        this.instanceId = `sf-data-view-${++instanceNumber}`;
        this.query = {filters: {}, search: "", sort: [], page: 1, per_page: 25};
        this.records = [];
        // The tree, when the host answers in levels (port 1.8.0): mode is
        // tree, flat or flattened as the last answer said, null before one.
        this.treeMode = null;
        this.tree = createTree();
        this._treeAppend = new Set();
        this.treeSequence = 0;
        // The host's labels and groups for its own action ids (row_actions).
        this.rowActionCatalog = [];
        this.continuing = false;
        this.appended = null;
        this.columns = [];
        this.total = 0;
        // Whether the host counts. It is answered by the answers themselves: a
        // total means there is a number, no total means there is a link. There
        // is no mode attribute, because a view whose host stops counting should
        // not need a host to be reconfigured as well.
        this.countKnown = true;
        this.hasNext = false;
        this.countedQuery = null;
        this.savedViews = [];
        this.columnSettings = null;
        this.sequence = 0;
        this.epoch = 0;
        this.pending = null;
        this.hostPort = null;
        // Whether a projection has arrived. Until one has, the counts the host
        // wrote in the markup are left alone.
        this._answered = false;
        this._bound = (event) => this._onEvent(event);
        this._seenEvents = new WeakSet();
        this.attachShadow({mode: "open"});
        this.shadowRoot.innerHTML = `
          <style>
            :host { display: block; }
            [part="layout"] { display: grid; gap: var(--sf-space-3); }
            [part="toolbar"] { display: flex; flex-wrap: wrap; gap: var(--sf-space-2); }
            /* What the view is doing is announced, not laid out. It used to be
               a line above the table, which said "Нет данных" over a table
               whose own body says the same, and pushed everything down by its
               height every time a search found nothing. */
            [part="status"] {
              position: absolute;
              inline-size: var(--sf-px);
              block-size: var(--sf-px);
              margin: calc(var(--sf-px) * -1);
              padding: 0;
              overflow: hidden;
              clip-path: inset(50%);
              white-space: nowrap;
              border: 0;
            }
            /* A notice is a message about an action. It used to float over the
               top of the card, which moved nothing but covered the search, the
               create button and the filters -- the consumer was right that this
               is worse than a shifted table. It stands in the corner of the
               window now, over the page and over none of the controls, and it
               goes away by itself. Slotted from the light DOM because the
               Framework's styles -- sf-toast's among them -- do not cross a
               shadow boundary. */
            slot[name="notice"]::slotted(*) {
              position: fixed;
              inset-block-end: var(--sf-space-3);
              inset-inline-end: var(--sf-space-3);
              max-inline-size: var(--sf-g0);
              z-index: var(--sf-z-index-9, 90);
            }
            /* The band of bulk controls is the last strip of the card and holds
               the bottom of the window while the card runs past it. It is
               slotted rather than built in here: the Framework's own styles do
               not cross a shadow boundary, so the band lives in the light DOM
               where they reach it, and this layout is what contains it. */
            slot[name="bulk"]::slotted(*) {
              position: sticky;
              inset-block-end: 0;
              z-index: var(--sf-z-index-3, 30);
            }
          </style>
          <section part="layout" style="position: relative">
            <div part="toolbar"><slot name="toolbar">
              <label>Поиск <input type="search" data-search></label>
              <!-- Saved views are offered only when the host says it keeps them.
                   They were offered always, and a host that claims neither
                   saved-views nor shared-saved-views answers every one of these
                   with capability-not-provided -- so «Сохранить вид» could do
                   nothing but refuse, which is not a control, it is a trap. The
                   owner met them on a page whose host claims only row-actions
                   (owner, 2026-10-09). -->
              <span data-saved-views hidden>
                <label>Вид <select data-view><option value="">Текущий</option></select></label>
                <label>Название вида <input type="text" data-view-name></label>
                <button type="button" data-save>Сохранить вид</button>
                <button type="button" data-delete>Удалить вид</button>
              </span>
            </slot></div>
            <div part="status" role="status" aria-live="polite" hidden></div>
            <slot name="content"><sf-table toolbar="false"></sf-table></slot>
            <slot name="show-all"></slot>
            <slot name="pagination"><sf-pagination top="false" bottom="false"></sf-pagination></slot>
            <slot name="bulk"></slot>
            <slot name="notice"></slot>
            <slot name="dialog"></slot>
          </section>`;
    }

    get table() {
        const slot = this.shadowRoot.querySelector('slot[name="content"]');
        return slot.assignedElements().find((element) => element.matches("sf-table"))
            ?? slot.querySelector("sf-table");
    }

    // The band of bulk controls, kept in the light DOM so the Framework styles
    // reach it, and assigned to the layout's own slot so the card contains it.
    get bulkBand() {
        if (this._bulkBand?.isConnected) return this._bulkBand;
        const existing = this.querySelector(':scope > [slot="bulk"]');
        this._bulkBand = existing ?? document.createElement("div");
        this._bulkBand.setAttribute("slot", "bulk");
        this._bulkBand.classList.add("sf-pagination-actions", "flex", "items-cross-center");
        this._bulkBand.setAttribute("role", "region");
        if (!this._bulkBand.isConnected) this.append(this._bulkBand);
        return this._bulkBand;
    }

    // The show-all row, in the light DOM for the same reason the bulk band and the
    // notice are: the Framework's stylesheets do not cross a shadow boundary, so a
    // control drawn inside the shadow comes out as a native grey button. A consumer
    // reported exactly that beside «5 of 61», and it is the same trap that had an
    // embedded table showing its icon ligatures as text.
    get showAllBand() {
        if (this._showAll?.isConnected) return this._showAll;
        const existing = this.querySelector(':scope > [slot="show-all"]');
        this._showAll = existing ?? document.createElement("div");
        this._showAll.setAttribute("slot", "show-all");
        this._showAll.classList.add("flex", "items-cross-center", "gap-1/3");
        // The click is listened for on the band rather than on this element. A
        // listener on the element would see every click inside it, and the handler
        // below branches on [data-save] and [data-delete] as well -- a row that
        // happened to carry one of those would have reached the wrong branch.
        if (this._showAll.getAttribute("data-sf-bound") !== "1") {
            this._showAll.setAttribute("data-sf-bound", "1");
            this._showAll.addEventListener("click", this._bound);
        }
        if (!this._showAll.isConnected) this.append(this._showAll);
        return this._showAll;
    }

    // The notice: one alert at a time, in the light DOM so the Framework's own
    // styles reach it, assigned to the layout's slot so the card holds it.
    get noticeBand() {
        if (this._notice?.isConnected) return this._notice;
        const existing = this.querySelector(':scope > [slot="notice"]');
        this._notice = existing ?? document.createElement("div");
        this._notice.setAttribute("slot", "notice");
        if (!this._notice.isConnected) this.append(this._notice);
        return this._notice;
    }

    // What a reason code says to a person. A host that sends its own `message`
    // is believed; a reason this component knows is translated; anything else
    // is a plain sentence, with the code kept in the title for the people who
    // read codes. A code is never the message: the consumer saw
    // `minimal_cms_action_rejected` where a sentence belonged.
    _noticeText(answer = {}) {
        if (typeof answer.message === "string" && answer.message.trim()) return answer.message.trim();
        // The host's own sentence wins; these are what to say when it sent
        // none. They follow the page's language like every other word.
        const keys = {
            "action-not-allowed": "noticeActionNotAllowed",
            "capability-not-provided": "noticeCapabilityNotProvided",
            "port-not-connected": "noticePortNotConnected",
            "saved-view-levels-require-port-1.1.0": "noticeSavedViewLevels",
        };
        if (keys[answer.reason]) return pageText(DATA_VIEW_TEXT, keys[answer.reason], this);
        const byAnswer = {
            conflict: "noticeConflict",
            refused: "noticeRefused",
            unavailable: "noticeUnavailable",
        }[answer.answer];
        return pageText(DATA_VIEW_TEXT, byAnswer ?? "noticeFailed", this);
    }

    // Shown over the card, not in it. `role` follows what happened: an error is
    // an alert, a refusal is a message.
    notify(text, {kind = "error", code = ""} = {}) {
        const band = this.noticeBand;
        band.replaceChildren();
        if (!text) {
            band.remove();
            this._notice = null;
            return this;
        }
        const toast = document.createElement("sf-toast");
        toast.setAttribute("status", kind === "error" ? "error" : "warning");
        toast.setAttribute("closable", "true");
        toast.setAttribute("close-label", pageText(DATA_VIEW_TEXT, "closeNoticeLabel", this));
        toast.setAttribute("role", kind === "error" ? "alert" : "status");
        toast.setAttribute("title", text);
        // Long enough to read a sentence and a code, and it can be closed at
        // once. A message that stays until it is dismissed ends up covering
        // something, which is how the overlay before it went wrong.
        toast.setAttribute("duration", "7000");
        // The code is for the people who read codes; the sentence above is for
        // everyone else. It is a second line, not the message.
        if (code) toast.setAttribute("supporting-text", code);
        toast.addEventListener("sf-toast-close", () => this.notify(""));
        band.append(toast);
        return this;
    }

    get pagination() {
        const slot = this.shadowRoot.querySelector('slot[name="pagination"]');
        return slot.assignedElements().find((element) => element.matches("sf-pagination"))
            ?? slot.querySelector("sf-pagination");
    }

    connectedCallback() {
        this.epoch += 1;
        this._controller = new AbortController();
        const events = [
            ...Object.keys(EVENTS), "sf-table-search-change", "sf-table-filter-change",
            "sf-table-sort-change", "sf-table-column-settings-change", "sf-table-action-intent",
            "sf-table-retry", "sf-port-output", "sf-page-change", "sf-page-size-change",
            "sf-action-apply", "sf-show-more", "sf-selection-clear", "sf-count-request",
            "sf-table-column-settings-reset", "sf-action-change", "sf-action-for-all-change",
            "sf-table-tree-toggle", "sf-table-tree-more",
        ];
        for (const name of events) this.shadowRoot.addEventListener(name, this._bound);
        for (const name of events) this.addEventListener(name, this._bound);
        this.shadowRoot.querySelector("[data-search]").addEventListener("change", this._bound);
        this.shadowRoot.querySelector("[data-view]").addEventListener("change", this._bound);
        this.shadowRoot.querySelector("[data-save]").addEventListener("click", this._bound);
        this.shadowRoot.querySelector("[data-delete]").addEventListener("click", this._bound);
        this._renderProjection();
        if (this.hostPort) this.refresh("connected");
        else this._requestHostPort();
    }

    // A component that takes a host port can also ask for one, so a consumer who
    // composes it inside something else does not have to find the element and call
    // a method on it. Raised once per connection and only while there is no port;
    // `setHostPort` is what answers it, synchronously or later. The name is the
    // shared convention rather than one of this element's own: the next composite
    // that takes a host port raises the same event, and a host answers them all
    // the same way.
    _requestHostPort() {
        if (this.hostPort || this._portRequested) return false;
        this._portRequested = true;
        this.dispatchEvent(new CustomEvent("sf-host-port-request", {
            bubbles: true,
            composed: true,
            detail: {component: this, context: {owner: this.portOwner, record: this.portRecord, group: this.portGroup}},
        }));
        return true;
    }

    // What a host needs to know to answer: which composite is asking and about
    // what. Set by whatever embedded this element; absent on a page list, which is
    // asking about nothing but itself.
    get portOwner() {
        return this.getAttribute("port-owner") || null;
    }

    get portRecord() {
        return this.getAttribute("port-record") || null;
    }

    get portGroup() {
        return this.getAttribute("port-group") || null;
    }

    disconnectedCallback() {
        this.epoch += 1;
        // Once per connection, so an element moved in the DOM asks again. Without
        // this reset a list re-attached into a different host would sit portless
        // and silent, which looks exactly like a host that chose not to answer.
        this._portRequested = false;
        this._controller?.abort();
        this._controller = null;
        const events = [
            ...Object.keys(EVENTS), "sf-table-search-change", "sf-table-filter-change",
            "sf-table-sort-change", "sf-table-column-settings-change", "sf-table-action-intent",
            "sf-table-retry", "sf-port-output", "sf-page-change", "sf-page-size-change",
            "sf-action-apply", "sf-show-more", "sf-selection-clear", "sf-count-request",
            "sf-table-column-settings-reset", "sf-action-change", "sf-action-for-all-change",
            "sf-table-tree-toggle", "sf-table-tree-more",
        ];
        for (const name of events) this.shadowRoot.removeEventListener(name, this._bound);
        for (const name of events) this.removeEventListener(name, this._bound);
        this.shadowRoot.querySelector("[data-search]").removeEventListener("change", this._bound);
        this.shadowRoot.querySelector("[data-view]").removeEventListener("change", this._bound);
        this.shadowRoot.querySelector("[data-save]").removeEventListener("click", this._bound);
        this.shadowRoot.querySelector("[data-delete]").removeEventListener("click", this._bound);
        this.pending = null;
    }

    // A host adapter exposes raise(intent, payload, {signal}). No transport is implied.
    // Which of this element's own parts render. A list and not a boolean, because
    // a boolean would have to mean «all of it or none», and an embedded list wants
    // its rows without a search box while a page list wants both. The names are
    // the ones this element already publishes as parts and slots, so nothing here
    // gives an existing thing a second name -- `sf-table` keeps its own `toolbar`
    // and `sf-pagination` keeps `top`/`bottom`.
    get chrome() {
        const declared = this.getAttribute("chrome");
        if (declared === null) return [...CHROME_PARTS];
        return declared.split(/\s+/u).filter((part) => CHROME_PARTS.includes(part));
    }

    renders(part) {
        return this.chrome.includes(part);
    }

    // How many rows an embedded list draws at most. Zero is no cap, which is what
    // a page list is. Not `limit`, which reads like a query limit the host owns,
    // and not `page-size`, which the pagination and the table already use for the
    // host's paging -- a cap is what this element shows of a page it already has.
    get rowCap() {
        const declared = Number.parseInt(this.getAttribute("row-cap") ?? "", 10);
        return Number.isFinite(declared) && declared > 0 ? declared : 0;
    }

    get cappedRecords() {
        // A tree is drawn whole, as far as it is open: the cap is a list's.
        if (this.treeMode === "tree") return visibleRows(this.tree);
        const cap = this.rowCap;
        return cap ? this.records.slice(0, cap) : this.records;
    }

    get capHidesRows() {
        const cap = this.rowCap;
        return Boolean(cap) && (this.records.length > cap || this.hasNext === true);
    }

    // The whole list is the host's to open. The component has no address to build
    // one from, and a route it guessed would send a person to a page the product
    // may not have. The query travels so the full view opens on the records the
    // person was looking at rather than on an unfiltered collection.
    openList(reason = "row-cap") {
        if (!this.hostPort) return Promise.resolve({answer: "unavailable", reason: "port-not-connected"});
        return this._raise("list.open", {
            reason,
            query: {search: this.query.search ?? "", filters: {...(this.query.filters ?? {})}},
        });
    }

    // The presentation attributes are observed, because they are read when the
    // projection runs and nothing re-ran it. A consumer rendering this element
    // through a template sets its attributes after it is in the document, so the
    // first projection saw no `chrome` at all and drew the full page chrome -- a
    // consumer reported the fallback «Поиск / Сохранить вид» form standing inside a
    // group that had asked for no chrome, until data arrived and projected again.
    static get observedAttributes() {
        return ["chrome", "row-cap", "aria-label"];
    }

    attributeChangedCallback(name, previous, next) {
        if (previous === next || !this.isConnected) return;
        this._renderProjection();
    }

    setHostPort(host) {
        if (!host || typeof host.raise !== "function" || !SUPPORTED_PORT_VERSIONS.includes(host.version)) {
            throw new TypeError(
                `sf-data-view requires a declared dataview host port: ${SUPPORTED_PORT_VERSIONS.join(", ")}`,
            );
        }
        this.hostPort = host;
        if (this.isConnected) this.refresh("host-connected");
        return this;
    }

    // `message` is for the live region only: it is announced, never laid out.
    _setState(state, message = "") {
        this.setAttribute("data-state", state);
        this.setAttribute("aria-busy", state === "loading" ? "true" : "false");
        this.table?.setDataState?.(state);
        const status = this.shadowRoot.querySelector('[part="status"]');
        status.hidden = !message;
        status.textContent = message;
        this.dispatchEvent(new CustomEvent("sf-data-view-state", {
            bubbles: true, composed: true, detail: {state, message, instance_id: this.instanceId},
        }));
    }

    async _raise(intent, payload, {query = false} = {}) {
        if (!this.hostPort) {
            this._setState("error", pageText(DATA_VIEW_TEXT, "statePortNotConnected", this));
            this.notify(pageText(DATA_VIEW_TEXT, "noticePortNotConnected", this));
            return {answer: "unavailable", reason: "port-not-connected"};
        }
        const capability = {
            "view.open": "saved-views", "view.save": "saved-views", "view.delete": "saved-views",
            "view.save_shared": "shared-saved-views",
            "settings.save_shared": "shared-column-settings", "bulk.apply_under_filter": "bulk-under-filter",
            "record.open": "row-actions", "record.mutate": "row-actions",
            "tree.expand": "tree", "node.targets": "tree-move", "node.move": "tree-move",
        }[intent];
        if (capability && !this.hostPort.capabilities?.includes(capability)) {
            const answer = {answer: "unavailable", reason: "capability-not-provided"};
            this._showAnswer(intent, answer);
            return answer;
        }
        // The tree's intents are announced as the one event the port names
        // for them, so a page can watch them without standing in the way.
        if (TREE_INTENTS.has(intent)) {
            this.dispatchEvent(new CustomEvent("sf-table-tree-intent", {
                bubbles: true, composed: true, detail: {intent, payload: copy(payload)},
            }));
        }
        const epoch = this.epoch;
        const sequence = query ? payload.sequence : null;
        try {
            const answer = await this.hostPort.raise(intent, copy(payload), {signal: this._controller?.signal});
            if (!this.isConnected || epoch !== this.epoch || (query && sequence !== this.sequence)) return null;
            if (!answer || !["applied", "conflict", "refused", "unavailable"].includes(answer.answer)) {
                throw new TypeError("Host answer is not a declared port answer");
            }
            if (query && answer.sequence != null && answer.sequence !== sequence) return null;
            if (query) this.pending = null;
            // A write the host confirms first (port 1.8.0): it answered with what
            // it is about to do and a digest of it. The person reads it; if they
            // confirm, the same intent goes again with the digest, which the
            // host checks against the write it described.
            if (!query && answer.answer === "refused" && answer.reason === "confirm_required"
                && answer.data?.confirm?.digest && !payload?.confirm) {
                await this._ensureElements(["sf-button"]);
                const confirmed = await this._dialog({
                    title: pageText(DATA_VIEW_TEXT, "confirmTitle", this),
                    text: String(answer.data.confirm.summary ?? ""),
                    apply: pageText(DATA_VIEW_TEXT, "confirmApply", this),
                });
                if (!confirmed) return {answer: "refused", reason: "not-confirmed"};
                return this._raise(intent, {...payload, confirm: String(answer.data.confirm.digest)});
            }
            if (answer.answer === "applied") {
                // Where a node may go is an answer to a question, not a view of
                // the list: it is read by the move dialog and changes no rows.
                if (ANSWER_ONLY.has(intent)) {
                    // nothing to project
                } else if (!query && this._isRecordPatch(answer.data)) this._applyRecordPatch(answer.data.records);
                else if (answer.data) this.applyProjection(answer.data);
                if (query && !answer.data) throw new TypeError("Applied query has no data");
                if (intent === "view.open") this.refresh("view-switch");
                // A write that changed no record cannot have changed the rows.
                // Column settings — order, width, visibility, pinning — used to
                // fall into this branch and re-query the whole page, so moving a
                // column reloaded the view and flashed a loader at the person.
                if (!query && !answer.data && !NO_REFRESH_AFTER.has(intent)) this.refresh("after-write");
            } else if (query && answer.answer === "unavailable") {
                this._setState("error", pageText(DATA_VIEW_TEXT, "stateUnavailable", this));
                this.notify(pageText(DATA_VIEW_TEXT, "noticeFetchFailed", this), {code: answer.reason || ""});
            }
            this._showAnswer(intent, answer);
            return answer;
        } catch (error) {
            if (!this.isConnected || epoch !== this.epoch) return null;
            const answer = {answer: "unavailable", reason: error?.message || "port-error"};
            if (query) {
                this._setState("error", pageText(DATA_VIEW_TEXT, "stateLoadFailed", this));
                this.notify(pageText(DATA_VIEW_TEXT, "noticeLoadFailed", this), {code: answer.reason || ""});
            }
            this._showAnswer(intent, answer);
            return answer;
        }
    }

    _showAnswer(intent, answer) {
        if (["conflict", "refused"].includes(answer.answer)
            || (answer.answer === "unavailable" && intent !== "query.change")) {
            const text = this._noticeText(answer);
            this._setState(this.records.length ? "populated" : "empty", text);
            this.notify(text, {kind: answer.answer === "conflict" ? "warning" : "error", code: answer.reason || ""});
        }
        this.dispatchEvent(new CustomEvent("sf-data-view-answer", {
            bubbles: true, composed: true, detail: {intent, ...copy(answer), instance_id: this.instanceId},
        }));
    }

    refresh(reason = "refresh") {
        if (!this.isConnected) return Promise.resolve(null);
        const sequence = ++this.sequence;
        this.pending = sequence;
        // Show more continues the list; every other reason replaces it. The
        // answer carries no reason of its own, so it is remembered here.
        this.continuing = reason === "show-more";
        // Continuing the list is answered inside the button that asked for it.
        // The table is not loading — the rows on screen stay exactly as they
        // are — so it neither dims them nor replaces them with a loader.
        if (this.continuing) {
            this.pagination?.setState?.({showMoreBusy: true});
        } else {
            this._setState("loading", pageText(DATA_VIEW_TEXT, "stateLoading", this));
        }
        return this._raise("query.change", {sequence, query: copy(this.query), reason}, {query: true});
    }

    changeQuery(patch, reason = "query-change") {
        if (!patch || typeof patch !== "object" || Array.isArray(patch)) throw new TypeError("Query patch must be an object");
        this.query = {...this.query, ...copy(patch)};
        if (!["page-change", "page-size-change", "show-more"].includes(reason)) this.query.page = 1;
        return this.refresh(reason);
    }

    applyProjection(data) {
        if (!data || typeof data !== "object") throw new TypeError("Port data must be an object");
        if (Array.isArray(data.row_actions)) this.rowActionCatalog = copy(data.row_actions);
        // One node's children: they go under it, and nothing else about the
        // view changes -- not the query, not the count, not the columns.
        const tree = data.tree && typeof data.tree === "object" ? data.tree : null;
        if (tree?.mode === "tree" && tree.parent !== null && tree.parent !== undefined && Array.isArray(data.records)) {
            const parent = String(tree.parent);
            acceptLevel(this.tree, {
                parent, records: copy(data.records), hasNext: tree.has_next === true,
                cursor: tree.cursor ?? null, append: this._treeAppend.delete(parent),
            });
            this.records = knownRecords(this.tree);
            this._renderProjection();
            return;
        }
        // An answer without records is no new shape of the list (a settings
        // write, a saved view): the mode stays what the last list said.
        if (tree) this.treeMode = tree.mode ?? null;
        else if (Array.isArray(data.records)) this.treeMode = null;
        if (this.treeMode === "tree" && Array.isArray(data.records)) {
            const reopen = [...this.tree.expanded];
            acceptLevel(this.tree, {
                records: copy(data.records), hasNext: tree.has_next === true,
                cursor: tree.cursor ?? null, append: this.continuing && this.records.length > 0,
            });
            this.records = knownRecords(this.tree);
            this.appended = null;
            // Branches a person had open stay open: their children are asked
            // for again, since a re-read may have changed them.
            for (const id of reopen) {
                if (this.tree.nodes.get(id)?.has_children === true && !hasLevel(this.tree, id)) this.expandNode(id);
            }
        } else if (Array.isArray(data.records)) {
            const next = copy(data.records);
            // A continuation keeps what is already shown and adds to it, and
            // keeps it by identity: a record the port repeats is not duplicated.
            if (this.continuing && this.records.length) {
                const known = new Set(this.records.map((record) => record.id));
                this.appended = next.filter((record) => !known.has(record.id));
                this.records = [...this.records, ...this.appended];
            } else {
                this.appended = null;
                this.records = next;
            }
        }
        if (Array.isArray(data.columns)) this.columns = data.columns.map(projectColumn);
        if (Number.isSafeInteger(data.total) && data.total >= 0) {
            this.total = data.total;
            this.countKnown = true;
        } else if (typeof data.has_next === "boolean") {
            this.hasNext = data.has_next;
            // A number already answered for this very query stays on screen.
            // Paging and Show more do not take it away — only a query that
            // changes what is being counted does.
            this.countKnown = this.countedQuery !== null && this.countedQuery === this._countKey();
        }
        this._answered = true;
        if (Array.isArray(data.saved_views)) this.savedViews = copy(data.saved_views);
        if (data.column_settings) this.columnSettings = copy(data.column_settings);
        if (data.echo) {
            const echo = data.echo;
            this.query = {
                ...this.query, filters: copy(echo.filters_chosen ?? echo.filters ?? {}),
                search: echo.search ?? "", sort: copy(echo.sort ?? []),
                page: echo.page ?? 1, per_page: echo.per_page ?? 25,
            };
        }
        this._renderProjection();
        this._restoreOpenedView();
        // The empty result is shown by the table, in its own body. The view only
        // announces it, so a search that finds nothing moves nothing.
        this._setState(this.records.length ? "populated" : "empty", this.records.length ? "" : pageText(DATA_VIEW_TEXT, "emptyText", this));
        this.notify("");
    }

    // What a count belongs to. Search, filters and page size change what the
    // number would be; a page does not.
    _countKey() {
        return JSON.stringify([this.query.search ?? "", this.query.filters ?? {}, this.query.per_page ?? 25]);
    }

    // What the band shows and what it does. The words and the action list stay
    // the host's, set on the pagination as they always were; the band only reads
    // them, so a host configures one element and sees the band in the card.
    _renderBulkBand() {
        const pagination = this.pagination;
        const table = this.table;
        const selected = table?.getSelectedRecordIds?.() ?? [];
        const actions = pagination?.actions ?? [];
        if (!pagination || !selected.length || !actions.length) {
            if (this._bulkBand?.isConnected) this._bulkBand.remove();
            this._bulkBand = null;
            return this;
        }
        const band = this.bulkBand;
        band.setAttribute("aria-label", pagination.actionsRegionLabel ?? "");
        const action = pagination.action ?? "";
        const total = pagination.countKnown === false ? null : pagination.total;
        const forAllLabel = total == null
            ? pagination.actionForAllLabel
            : `${pagination.actionForAllLabel} (${total})`;
        const chosen = (node, name, value) => {
            if (value == null || value === false) node.removeAttribute(name);
            else node.setAttribute(name, value === true ? "" : String(value));
        };
        const parts = {};
        if (!band.childElementCount) {
            band.innerHTML = `
                <span class="sf-pagination-actions-count" data-sf-bulk="count"></span>
                <span class="sf-pagination-actions-separator" aria-hidden="true"></span>
                <sf-dropdown data-sf-bulk="actions" size="1" type="outlined" search="false" mode="select"></sf-dropdown>
                <sf-button data-sf-bulk="apply" size="1" type="default" scheme="primary"></sf-button>
                <span class="sf-pagination-actions-separator" aria-hidden="true"></span>
                <sf-checkbox data-sf-bulk="for-all"></sf-checkbox>
                <sf-icon-button data-sf-bulk="clear" class="sf-pagination-actions-clear" size="1" type="link" scheme="on-surface" icon="close"></sf-icon-button>`;
            band.querySelector('[data-sf-bulk="apply"]')
                .addEventListener("click", () => this.applyBulkAction());
            band.querySelector('[data-sf-bulk="actions"]')
                .addEventListener("change", (event) => pagination.setAction?.(event.detail?.value ?? ""));
            band.querySelector('[data-sf-bulk="for-all"]')
                .addEventListener("change", (event) => pagination.setActionForAll?.(Boolean(event.target?.checked)));
            band.querySelector('[data-sf-bulk="clear"]')
                .addEventListener("click", () => this.clearSelection());
        }
        for (const node of band.querySelectorAll("[data-sf-bulk]")) {
            parts[node.dataset.sfBulk] = node;
        }
        parts.count.textContent = `${pagination.selectedLabel} ${selected.length}`;
        chosen(parts.actions, "value", action);
        chosen(parts.actions, "placeholder", pagination.actionChooseLabel);
        chosen(parts.actions, "aria-label", pagination.actionsLabel);
        parts.actions.options = [
            {type: "text", size: "1", text: pagination.actionChooseLabel, value: "", selected: !action},
            ...actions.map((item) => ({
                type: "text", size: "1", text: item.text, value: item.value,
                selected: item.value === action, disabled: Boolean(item.disabled),
            })),
        ];
        chosen(parts.apply, "text", pagination.actionApplyText);
        chosen(parts.apply, "disabled", !action);
        chosen(parts["for-all"], "label", forAllLabel);
        chosen(parts["for-all"], "checked", Boolean(pagination.actionForAll));
        parts["for-all"].hidden = !pagination.showActionForAll;
        chosen(parts.clear, "title", pagination.clearSelectionLabel);
        chosen(parts.clear, "aria-label", pagination.clearSelectionLabel);

        return this;
    }

    // Apply is the band's only write, and it goes the same way it went from the
    // page row: under the filter when For-all is ticked, over the marked rows
    // otherwise.
    applyBulkAction() {
        const pagination = this.pagination;
        const action = pagination?.action;
        if (!action) return undefined;
        if (pagination.actionForAll) return this.applyUnderFilter(action, this.total);
        return this._action(
            {action_id: action, record_ids: this.table?.getSelectedRecordIds?.() ?? []},
            {selectedBulk: true},
        );
    }

    clearSelection() {
        this.table?.clearSelection?.();
        this.pagination?.setState?.({selectedCount: 0});
        return this._renderBulkBand();
    }

    // One record, changed in place. A write that touches a row does not need
    // the page re-queried and the body rebuilt: the host answers with the
    // record it changed, or the page calls this itself, and only that row is
    // written. A full projection is still a full projection.
    updateRecord(id, patch = {}) {
        const key = String(id);
        if (this.treeMode === "tree") {
            const known = this.tree.nodes.get(key);
            if (!known || !patch || typeof patch !== "object") return false;
            this.tree.nodes.set(key, {...known, ...copy(patch), id: known.id});
            this.records = knownRecords(this.tree);
            this.table?.setRows?.(visibleRows(this.tree));
            this._renderBulkBand();
            return true;
        }
        const index = this.records.findIndex((record) => String(record?.id) === key);
        if (index < 0 || !patch || typeof patch !== "object") return false;
        const next = {...this.records[index], ...copy(patch), id: this.records[index].id};
        this.records = [...this.records.slice(0, index), next, ...this.records.slice(index + 1)];
        this.table?.updateRowByKey?.("id", this.records[index].id, {...next}, "record-update");
        this._renderBulkBand();
        return true;
    }

    // ─── the tree (port 1.8.0) ───────────────────────────────────────────────
    // Opening a node shows the children the view already has, or asks the host
    // for them; closing it is the view's own business. A node is opened only
    // when the host said it has children.
    expandNode(id) {
        const key = String(id);
        const node = this.tree.nodes.get(key);
        if (!node || node.has_children !== true) return null;
        if (hasLevel(this.tree, key)) {
            expandKnown(this.tree, key);
            this._renderTreeRows();
            return null;
        }
        markLoading(this.tree, key);
        this._renderTreeRows();
        return this._raise("tree.expand", {id: node.id, cursor: null, sequence: ++this.treeSequence});
    }

    collapseNode(id) {
        collapse(this.tree, id);
        this._renderTreeRows();
    }

    // The next page of one level: of the roots through the query, as a list
    // pages, and of a node's children through tree.expand with its cursor.
    loadMoreNodes(parent, cursor) {
        if (parent === null || parent === undefined || parent === "") {
            return this.changeQuery({page: this.query.page + 1}, "show-more");
        }
        const key = String(parent);
        this._treeAppend.add(key);
        const node = this.tree.nodes.get(key);
        return this._raise("tree.expand", {id: node?.id ?? parent, cursor: cursor ?? null, sequence: ++this.treeSequence});
    }

    // Where a node may go is the host's: it is asked first, and only the places
    // it names are offered -- the top level when it allows that, and the first
    // or last place among the new siblings when it allows reordering.
    async openMoveDialog(record) {
        const answer = await this._raise("node.targets", {id: record.id});
        if (answer?.answer !== "applied") return answer;
        const targets = answer.data?.move_targets ?? {};
        const parents = Array.isArray(targets.parents) ? targets.parents : [];
        const options = [
            ...(targets.root ? [{id: null, label: pageText(DATA_VIEW_TEXT, "moveRoot", this), path: []}] : []),
            ...parents.filter((parent) => String(parent.id) !== String(record.id)),
        ];
        if (!options.length) {
            this.notify(pageText(DATA_VIEW_TEXT, "moveNoTargets", this), {kind: "warning"});
            return {answer: "refused", reason: "no-targets"};
        }
        const label = String(record.title ?? record.label ?? record.name ?? record.id);
        // The choices are sf-radio, which nothing else on the page may have
        // asked for: a Loader that scans the light DOM never meets the
        // dialog's (Larena: an empty choice list, 2026-10-12). Asked for here,
        // and waited for briefly; the dialog opens either way.
        await this._ensureElements(["sf-radio", "sf-button"]);
        const chosen = await this._dialog({
            title: pageText(DATA_VIEW_TEXT, "moveTitle", this).replace("{label}", label),
            apply: pageText(DATA_VIEW_TEXT, "moveApply", this),
            options,
            reorder: targets.reorder === true,
        });
        if (!chosen) return null;
        return this._raise("node.move", {
            id: record.id, parent: chosen.parent, index: chosen.index,
            ...(record.revision !== undefined ? {revision: record.revision} : {}),
        });
    }

    async _ensureElements(tags) {
        const loader = globalThis.SF?.Loader;
        await Promise.all(tags.map((tag) => {
            if (customElements.get(tag)) return null;
            loader?.ensureSmartElementLoaded?.(tag);
            return Promise.race([
                customElements.whenDefined(tag),
                new Promise((settle) => setTimeout(settle, 3000)),
            ]);
        }));
    }

    // One small dialog for both the move and the confirmation: a native
    // <dialog> in the light DOM, where the Framework's styles reach it, shown
    // modal so it holds the focus and Escape closes it. Resolves with what was
    // chosen, true for a plain confirmation, or null.
    _dialog({title, text = "", apply, options = null, reorder = false}) {
        const dialog = document.createElement("dialog");
        dialog.slot = "dialog";
        // No layout utility on the dialog itself: one that sets display would
        // beat the browser's own hiding of a closed dialog.
        dialog.className = "sf-data-view-dialog";
        dialog.setAttribute("aria-label", title);
        dialog.style.cssText = "max-inline-size: min(var(--sf-g4), calc(100vw - 2 * var(--sf-space-3)));"
            + "padding: var(--sf-space-3); border: 0; border-radius: var(--sf-radius--surface);"
            + "background-color: var(--sf-elevation-3--surface); box-shadow: var(--sf-elevation-3--shadow);"
            + "color: var(--sf-on-surface);";
        const heading = document.createElement("h2");
        heading.className = "sf-text-2 font-semibold m-0";
        heading.textContent = title;
        // The places can be many; the list scrolls and the title and the
        // buttons stay where they are.
        const content = document.createElement("div");
        content.className = "flex flex-col gap-3";
        content.style.cssText = "max-block-size: calc(100dvh - 4 * var(--sf-space-3));";
        dialog.append(content);
        content.append(heading);
        if (text) {
            const body = document.createElement("p");
            body.className = "sf-text-1 m-0";
            body.textContent = text;
            content.append(body);
        }
        const name = `${this.instanceId}-move`;
        if (options) {
            const list = document.createElement("div");
            list.setAttribute("role", "radiogroup");
            list.setAttribute("aria-label", title);
            list.className = "flex flex-col gap-1";
            list.style.cssText = "overflow-y: auto; min-block-size: 0; flex: 1 1 auto;";
            options.forEach((option, index) => {
                const radio = document.createElement("sf-radio");
                radio.setAttribute("name", name);
                radio.setAttribute("value", option.id === null ? "" : String(option.id));
                radio.setAttribute("label", String(option.label));
                if (Array.isArray(option.path) && option.path.length) radio.setAttribute("description", option.path.join(" / "));
                if (index === 0) radio.setAttribute("checked", "");
                list.append(radio);
            });
            content.append(list);
            if (reorder) {
                const placeList = document.createElement("div");
                placeList.setAttribute("role", "radiogroup");
                placeList.setAttribute("aria-label", pageText(DATA_VIEW_TEXT, "movePlace", this));
                placeList.className = "flex gap-2";
                for (const [value, key] of [["last", "moveLast"], ["first", "moveFirst"]]) {
                    const radio = document.createElement("sf-radio");
                    radio.setAttribute("name", `${name}-place`);
                    radio.setAttribute("value", value);
                    radio.setAttribute("label", pageText(DATA_VIEW_TEXT, key, this));
                    if (value === "last") radio.setAttribute("checked", "");
                    placeList.append(radio);
                }
                content.append(placeList);
            }
        }
        const footer = document.createElement("div");
        footer.className = "flex justify-end gap-2";
        const cancel = document.createElement("sf-button");
        cancel.setAttribute("appearance", "outline");
        cancel.setAttribute("text", pageText(DATA_VIEW_TEXT, "cancel", this));
        const confirm = document.createElement("sf-button");
        confirm.setAttribute("text", apply);
        confirm.dataset.apply = "";
        footer.append(cancel, confirm);
        content.append(footer);
        this.append(dialog);
        const checked = (radioName) => [...dialog.querySelectorAll(`sf-radio[name="${radioName}"]`)]
            .find((radio) => radio.checked === true || radio.hasAttribute("checked") && radio.checked !== false);
        return new Promise((settle) => {
            const finish = (value) => {
                dialog.close();
                dialog.remove();
                settle(value);
            };
            cancel.addEventListener("click", () => finish(null));
            dialog.addEventListener("cancel", (event) => {
                event.preventDefault();
                finish(null);
            });
            confirm.addEventListener("click", () => {
                if (!options) return finish(true);
                const parentRadio = checked(name);
                const value = parentRadio?.getAttribute("value") ?? "";
                const option = options.find((item) => (item.id === null ? "" : String(item.id)) === value) ?? options[0];
                const place = reorder ? checked(`${name}-place`)?.getAttribute("value") : "last";
                return finish({parent: option.id, index: place === "first" ? 0 : null});
            });
            dialog.showModal();
        });
    }

    _renderTreeRows() {
        this.records = knownRecords(this.tree);
        this.table?.setRows?.(visibleRows(this.tree));
    }

    // An answer that carries records and nothing else is a patch of those
    // records, not a new page: it has no echo of the query, no total and no
    // columns, so there is nothing to lay out again.
    _isRecordPatch(data) {
        return Boolean(data)
            && Array.isArray(data.records)
            && !data.echo && data.total == null && !data.columns
            && !data.saved_views && !data.column_settings && !data.tree;
    }

    _applyRecordPatch(records = []) {
        let applied = 0;
        for (const record of records) {
            if (record && record.id != null && this.updateRecord(record.id, record)) applied += 1;
        }
        // A record the page does not hold cannot be patched into it; that is a
        // new row, and only a query knows where it belongs.
        if (applied !== records.length) this.refresh("record-unknown");
        else this.notify("");
        return applied;
    }

    _renderProjection() {
        // The saved-view controls appear when the host keeps saved views and
        // not before: this is the only place that runs both when a host is set
        // and when data arrives, which is when the answer can have changed.
        const savedViews = this.shadowRoot?.querySelector?.("[data-saved-views]");
        if (savedViews) {
            savedViews.hidden = !this.hostPort?.capabilities?.includes?.("saved-views");
        }
        this.pagination?.setState?.({showMoreBusy: false});
        // The page row no longer carries the band: the card does, so the strip
        // can hold the bottom of the window without the row coming along. The
        // attribute is written rather than put through setState, which clears a
        // false value by removing the attribute — and a removed `bottom` means
        // the default, which is to draw the band.
        this.pagination?.setAttribute?.("bottom", "false");
        const table = this.table;
        if (table) {
            // The two chrome columns. Written as attributes rather than left to the
            // table's own defaults, which are both on: an embedded list rendered a
            // checkbox column with «select all rows» and a gear column, and both
            // read as leftovers from a page table -- there is no bulk band and no
            // toolbar in an embedded list for either of them to lead to.
            table.setAttribute("selectable", this.renders("select") ? "true" : "false");
            // A page's list becomes the compact list on a narrow container; an
            // embedded one (no toolbar) stays a table, and a layout the host
            // wrote on its table is the host's.
            if (!table.hasAttribute?.("data-sf-host-layout")) {
                table.setAttribute("data-sf-host-layout", table.getAttribute?.("layout") ?? "");
            }
            if (!table.getAttribute?.("data-sf-host-layout")) {
                table.setAttribute("layout", this.renders("toolbar") ? "auto" : "table");
            }
            table.setAttribute("settings", this.renders("settings") ? "true" : "false");
            // Row actions are the host's to claim. The intent was already
            // refused without the capability -- answered «unavailable» without
            // the host being called -- but the column and its buttons were drawn
            // all the same, so a person could press an action nothing would ever
            // apply. A view does not show a control the host has not claimed,
            // which is the same rule the saved-view controls already follow
            // (admin polygon, 2026-10-10).
            // A table the host wrote row-actions="false" on keeps it: the host has
            // put every action in its own row menu and still claims the
            // capability, because its menu raises record.open and record.mutate
            // through the port (Larena, 2026-10-12). What the table carried
            // before the view first touched it is the host's word.
            if (!table.hasAttribute?.("data-sf-host-row-actions")) {
                table.setAttribute("data-sf-host-row-actions", table.getAttribute?.("row-actions") ?? "");
            }
            const hostSaysNo = table.getAttribute?.("data-sf-host-row-actions") === "false";
            table.setAttribute(
                "row-actions",
                !hostSaysNo && this.hostPort?.capabilities?.includes("row-actions") ? "true" : "false",
            );
            // The accessible name of a list the host did not slot in. The fallback
            // table carries a name of its own, which is right for a page list and
            // wrong inside a group called «Счета»: it would announce «Записи».
            // Only the fallback is renamed -- a table a host slotted in keeps the
            // name the host gave it.
            const slot = this.shadowRoot.querySelector('slot[name="content"]');
            const own = this.getAttribute("aria-label");
            if (own && slot && !slot.assignedElements().length) table.setAttribute("aria-label", own);
            const allowed = new Set(this.records.flatMap((record) => Array.isArray(record.actions) ? record.actions : []));
            // The catalogue is read once and kept. This used to narrow the
            // table's own list in place, which is destructive: the first
            // projection of an empty list left the catalogue empty, and from
            // then on every id the host named was unknown. «view» came out as a
            // link labelled "view" instead of the eye, and in a narrow column
            // the label broke mid-word and set the row height.
            this._knownRowActions ??= table.ACTIONS_SETTINGS_ROW ?? [];
            const known = this._knownRowActions;
            // The host's own actions are named by its catalogue (row_actions,
            // port 1.8.0); an action the catalogue groups is offered once, as
            // the group, with a submenu of the members a record allows. Which
            // members a row shows is still that record's actions alone.
            const catalog = new Map(this.rowActionCatalog.map((entry) => [entry.id, entry]));
            // In a tree, move and create are the view's own actions and named
            // by it, unless the host's catalogue names them.
            const treeActions = this.treeMode === "tree"
                ? {move: pageText(DATA_VIEW_TEXT, "moveAction", this), create: pageText(DATA_VIEW_TEXT, "createChildAction", this)}
                : {};
            const single = (id) => {
                const entry = catalog.get(id) ?? (treeActions[id] ? {id, label: treeActions[id]} : null);
                if (entry) {
                    return {
                        id, name: entry.label, danger: entry.danger === true, inline: false,
                        component: {type: "button", props: {text: entry.label, type: "link", ...(entry.icon ? {icon: entry.icon} : {})}},
                    };
                }
                return known.find((action) => action.id === id) ?? {
                    id, name: id, component: {type: "button", props: {text: id, type: "link"}},
                };
            };
            const groups = new Map();
            const listed = [];
            for (const id of allowed) {
                const group = catalog.get(id)?.group;
                if (!group) {
                    listed.push(single(id));
                    continue;
                }
                if (!groups.has(group)) {
                    const label = catalog.get(id).group_label ?? group;
                    const entry = {id: `group:${group}`, name: label, members: [], inline: false,
                        component: {type: "button", props: {text: label, type: "link"}}};
                    groups.set(group, entry);
                    listed.push(entry);
                }
                groups.get(group).members.push(single(id));
            }
            table.ACTIONS_SETTINGS_ROW = listed;
            // A list whose every action lives in the row menu has no actions
            // column to draw.
            if (listed.length && listed.every((action) => action.inline === false)) {
                table.setAttribute("row-actions", "false");
            }
            // The port the list is holding, handed to the table for one thing:
            // the renderer set asks a host how to format a value, and a cell had
            // no host to ask. Only the formatting is read from it -- the table
            // raises its intents as it always has and the view re-raises them.
            table.cellHost = this.hostPort ?? null;
            table.setColumns?.(this.columns);
            if (Array.isArray(this.appended)) {
                // Appending leaves the rows already on screen untouched, so a
                // row that was checked stays checked: the checkbox state lives
                // on the row itself.
                table.addRows?.(this.appended);
            } else {
                table.setRows?.(this.cappedRecords);
            }
            const first = this.query.sort[0];
            table.setSort?.(first?.key ?? null, first?.direction ?? null);
            table.setTemplates?.(Object.fromEntries(this.savedViews.map((view) => [view.key, view])));
        }
        const pagination = this.pagination;
        // Before the first answer there is no count to write. Writing one anyway
        // put «Всего: 0» under a heading that already said 450: the host's own
        // total in the markup was overwritten with this element's initial zero.
        const counted = this._answered
            ? {total: this.total, countKnown: this.countKnown, hasNext: this.hasNext}
            : {};
        pagination?.setState?.({
            current: this.query.page,
            pageSize: this.query.per_page,
            ...counted,
            canCount: Boolean(this.hostPort?.capabilities?.includes("count-on-request")),
            counting: false,
        });
        // The parts a consumer left out. Hidden rather than removed, so turning one
        // back on does not rebuild the shadow tree and lose what a host slotted
        // into it.
        // The shadow template is written once and cannot carry a language: it
        // is built before the element is anywhere. These two are named here,
        // where the page is readable.
        const layout = this.shadowRoot.querySelector('[part="layout"]');
        if (layout) layout.setAttribute("aria-label", pageText(DATA_VIEW_TEXT, "layoutLabel", this));
        const fallbackTable = this.shadowRoot.querySelector('slot[name="content"] > sf-table');
        if (fallbackTable && !fallbackTable.getAttribute("aria-label")) {
            fallbackTable.setAttribute("aria-label", pageText(DATA_VIEW_TEXT, "recordsLabel", this));
        }
        const toolbar = this.shadowRoot.querySelector('[part="toolbar"]');
        if (toolbar) toolbar.hidden = !this.renders("toolbar");
        const pageRow = this.shadowRoot.querySelector('slot[name="pagination"]');
        // A tree has no pages: a level that is long ends in Show more, and the
        // host gives no total for the page row to count.
        if (pageRow) pageRow.hidden = !this.renders("pagination") || this.treeMode === "tree";
        if (!this.capHidesRows) {
            if (this._showAll?.isConnected) this._showAll.remove();
            this._showAll = null;
        } else {
            const band = this.showAllBand;
            let button = band.querySelector("[data-show-all]");
            if (!button) {
                button = document.createElement("sf-button");
                button.setAttribute("data-show-all", "");
                button.setAttribute("appearance", "link");
                button.setAttribute("size", "1");
                band.append(button);
            }
            button.setAttribute("text", pageText(DATA_VIEW_TEXT, "showAll", this));
            let count = band.querySelector("[data-show-count]");
            if (!count) {
                count = document.createElement("span");
                count.setAttribute("data-show-count", "");
                band.append(count);
            }
            // «N of M» only when the host has already said M. A count fetched to
            // decide what a link says would spend the host's query on this
            // element's own layout.
            count.textContent = this.countKnown
                ? pageText(DATA_VIEW_TEXT, "ofTotal", this)
                    .replace("{shown}", String(this.cappedRecords.length))
                    .replace("{total}", String(this.total))
                : "";
        }
        const search = this.shadowRoot.querySelector("[data-search]");
        if (search && search.value !== this.query.search) search.value = this.query.search;
        const select = this.shadowRoot.querySelector("[data-view]");
        if (select) {
            const previous = select.value;
            select.replaceChildren(new Option(pageText(DATA_VIEW_TEXT, "currentViewText", this), ""), ...this.savedViews.map((view) => new Option(view.label, view.key)));
            select.value = this.savedViews.some((view) => view.key === previous) ? previous : "";
        }
    }

    saveView({key, label, level = "personal", revision, roles, layout, settings} = {}) {
        if (!key || !label) throw new TypeError("Saved view needs key and label");
        if (!["personal", "shared"].includes(level)) throw new TypeError("Only personal and shared views can be written");
        if (level === "shared" && this.hostPort?.version !== "1.1.0") {
            return Promise.resolve({answer: "unavailable", reason: "saved-view-levels-require-port-1.1.0"});
        }
        return this._raise(level === "shared" ? "view.save_shared" : "view.save", {
            key, label, revision, query: copy(this.query),
            roles: copy(roles ?? {}), layout: copy(layout ?? {columns: this.columns}),
            settings: copy(settings ?? this.columnSettings ?? {}),
            ...(level === "shared" ? {target: "shared"} : {}),
        });
    }

    deleteView(key) { return this._raise("view.delete", {key}); }
    // Which view was opened, so its own settings can be put back when the
    // answer arrives: the fields the filter was showing are saved with it.
    openView(key) {
        this._openedView = key;
        return this._raise("view.open", {key});
    }

    // The part of a saved view that belongs to the table rather than to the
    // query: which filter fields it was showing.
    _restoreOpenedView() {
        const key = this._openedView;
        if (!key) return this;
        const view = this.savedViews.find((candidate) => candidate?.key === key);
        if (!view) return this;
        this._openedView = null;
        const fields = view.settings?.filter_fields;
        if (Array.isArray(fields)) {
            this.table?.setActiveFilterFields?.(fields.filter((item) => typeof item === "string"));
        }
        return this;
    }
    saveColumnSettings(columnSettings, {shared = false, base_revision} = {}) {
        const layer = shared ? "shared" : "personal";
        const revision = base_revision ?? this.columnSettings?.layers?.[layer]?.revision;
        if (revision == null) throw new TypeError("Column settings need a base revision");
        return this._raise(shared ? "settings.save_shared" : "settings.save_personal", {
            columnSettings: copy(columnSettings), base_revision: revision, ...(shared ? {target: "shared"} : {}),
        });
    }

    // Resetting the person's layer is a write of nothing against its revision,
    // which is exactly what the port calls a reset: "a write against its
    // revision, never a deletion". The empty object matters — the host owns the
    // defaults, and writing this component's idea of them (everything visible,
    // no widths, nothing pinned) would overwrite them.
    resetColumnSettings() {
        return this.saveColumnSettings({}, {});
    }

    applyUnderFilter(actionId, confirmedTotal) {
        if (!Number.isSafeInteger(confirmedTotal) || confirmedTotal !== this.total) {
            throw new TypeError("Confirm the current total before a filter-wide action");
        }
        return this._raise("bulk.apply_under_filter", {
            action_id: actionId, filters: copy(this.query.filters), confirmed_total: confirmedTotal,
        });
    }

    // The number, asked for on its own. The view resumes whatever wanted it:
    // showing it, jumping to the last page, or an action under the filter that
    // has to name a confirmed total. A refusal puts the view back where it was
    // and says so — a for-all box left ticked over a number nobody has would be
    // a promise the view cannot keep.
    async count(reason = "total") {
        const requested = this._countKey();
        const answer = await this._raise("query.count", {
            query: {search: this.query.search ?? "", filters: copy(this.query.filters ?? {})},
            reason,
        });
        const total = answer?.data?.total;
        if (answer?.answer !== "applied" || !Number.isSafeInteger(total) || total < 0) {
            this.pagination?.setState?.({counting: false});
            if (reason === "action-for-all") this.pagination?.setState?.({actionForAll: false});
            return answer;
        }
        // The query may have moved on while the host was counting; a number
        // belonging to a query nobody is looking at any more is not shown.
        if (requested !== this._countKey()) {
            this.pagination?.setState?.({counting: false});
            return answer;
        }
        this.total = total;
        this.countKnown = true;
        this.countedQuery = requested;
        this.pagination?.setState?.({counting: false, countKnown: true, total});
        if (reason === "last-page") this.pagination?.lastPage?.();
        return answer;
    }

    _action(detail, {selectedBulk = false} = {}) {
        const ids = detail?.record_ids;
        if (!Array.isArray(ids) || !ids.length || typeof detail.action_id !== "string") return;
        const chosen = ids.map((id) => this.records.find((record) => record.id === id));
        // In a tree two of a record's actions are the view's own: move opens
        // the choice of the places the host offers, create makes a node under
        // it. Each is offered only when the record lists it.
        if (this.treeMode === "tree" && !selectedBulk && ids.length === 1 && chosen[0]?.actions?.includes(detail.action_id)) {
            if (detail.action_id === "move") return this.openMoveDialog(chosen[0]);
            if (detail.action_id === "create") return this._raise("view.create_record", {item: null, parent: chosen[0].id});
        }
        // A selected-bulk action has its own ID and permission in the host, even
        // when just one row is selected. The host checks the whole batch.
        if (chosen.some((record) => !record)
            || (!selectedBulk && ids.length === 1 && !chosen[0].actions?.includes(detail.action_id))) {
            this._showAnswer("record.mutate", {answer: "refused", reason: "action-not-allowed"});
            return;
        }
        if (!selectedBulk && ids.length === 1 && detail.action_id === "view") return this._raise("record.open", detail);
        const revisions = Object.fromEntries(chosen.map((record) => [String(record.id), record.revision]));
        return this._raise(selectedBulk || ids.length > 1 ? "bulk.apply_selected" : "record.mutate", {...detail, revisions});
    }

    _onEvent(event) {
        if (this._seenEvents.has(event)) return;
        this._seenEvents.add(event);
        if (event.target === this || event.target?.closest?.("sf-data-view") !== this && !this.shadowRoot.contains(event.target)) return;
        // An event crossing the shadow boundary is observed once, at the shadow root.
        if (event.currentTarget === this && this.shadowRoot.contains(event.target)) return;
        if (event.type === "change" && event.target.matches?.("[data-search]")) return this.changeQuery({search: event.target.value}, "search");
        if (event.type === "change" && event.target.matches?.("[data-view]")) return event.target.value && this.openView(event.target.value);
        if (event.type === "click" && event.target.matches?.("[data-show-all]")) return this.openList("row-cap");
        if (event.type === "click" && event.target.matches?.("[data-save]")) {
            const label = this.shadowRoot.querySelector("[data-view-name]")?.value?.trim();
            if (label) return this.saveView({key: label, label});
        }
        if (event.type === "click" && event.target.matches?.("[data-delete]")) {
            const key = this.shadowRoot.querySelector("[data-view]").value;
            if (key) return this.deleteView(key);
        }
        if (event.type === "sf-table-search-change") return this.changeQuery({search: String(event.detail ?? "")}, "search");
        if (event.type === "sf-table-filter-change") return this.changeQuery({filters: copy(event.detail?.filters ?? event.detail?.values ?? event.detail ?? {})}, "filter");
        if (event.type === "sf-table-sort-change") return this.changeQuery({sort: event.detail?.direction ? [copy(event.detail)] : []}, "sort");
        if (event.type === "sf-page-change") return this.changeQuery({page: event.detail?.current}, "page-change");
        if (event.type === "sf-page-size-change") return this.changeQuery({per_page: event.detail?.pageSize}, "page-size-change");
        if (event.type === "sf-show-more") return this.changeQuery({page: this.query.page + 1}, "show-more");
        if (event.type === "sf-count-request") return this.count(event.detail?.reason ?? "total");
        if (event.type === "sf-table-column-settings-reset") return this.resetColumnSettings();
        // The panel's cross says the selection is over. The rows hold it, so
        // the view passes the word on rather than tracking a copy of its own.
        if (event.type === "sf-selection-clear") return this.clearSelection();
        if (event.type === "sf-action-change" || event.type === "sf-action-for-all-change") {
            // The chooser and For-all live in the band now; the pagination still
            // holds their state, so the band is redrawn when it changes.
            this._renderBulkBand();
            return undefined;
        }
        if (event.type === "sf-table-retry") return this.refresh("retry");
        if (event.type === "sf-table-tree-toggle") {
            const id = event.detail?.id;
            return event.detail?.expanded ? this.collapseNode(id) : this.expandNode(id);
        }
        if (event.type === "sf-table-tree-more") return this.loadMoreNodes(event.detail?.parent, event.detail?.cursor);
        if (event.type === "sf-table-action-intent") return this._action(event.detail);
        if (event.type === "sf-table-column-settings-change") {
            return this.saveColumnSettings(event.detail?.columnSettings ?? event.detail, {
                shared: event.detail?.target === "shared", base_revision: event.detail?.base_revision,
            });
        }
        if (event.type === "sf-action-apply") {
            if (event.detail?.actionForAll) return this.applyUnderFilter(event.detail.action, event.detail.total);
            return this._action({action_id: event.detail?.action, record_ids: this.table?.getSelectedRecordIds?.() ?? []}, {selectedBulk: true});
        }
        if (event.type === "sf-port-output") {
            this.pagination?.setState?.({selectedCount: event.detail?.value?.length ?? 0});
            this._renderBulkBand();
            return;
        }
        const intent = EVENTS[event.type];
        if (intent === "query.change") return this.refresh("table-context");
        if (intent === "view.save") {
            const view = event.detail ?? {};
            return this.saveView({key: view.key, label: view.label ?? view.name ?? view.key, revision: view.revision,
                level: view.level ?? "personal", roles: view.roles, layout: view.layout, settings: view.settings});
        }
        if (intent) return this._raise(intent, copy(event.detail ?? {}));
    }
}

// Not a Smart base element, so it carries the same brand by hand: quiet when
// the component arrives twice, loud when something else holds the tag.
SfDataView.sfElementBrand = "simai.smart-base.v1";
const definedDataView = customElements.get("sf-data-view");
if (!definedDataView) customElements.define("sf-data-view", SfDataView);
else if (definedDataView.sfElementBrand !== SfDataView.sfElementBrand) {
    console.warn("SfDataView.define(): sf-data-view is already defined", definedDataView);
}
export default SfDataView;
