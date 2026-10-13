import SfBaseElement from "../../core/js/smart-base";
import {renderTableTemplate} from "./js/templates/default";
import {optionBadgeProps} from "../helpers/option-badge.js";
import {renderCellField, renderFilterField} from "../field-renderers/index.js";
import {html, nothing, render} from "lit";
import {ref} from "lit/directives/ref.js";
import {pageText} from "../../core/js/page-language";
import {getParent, setParentSpace} from "../helpers/dom";
import {bindSortableDrag, getVerticalDragAfterElement} from "../helpers/draggable";
import {setLoaderDragState} from "../helpers/loaderDragState";
import BundledPosition from "../../core/js/position.js";

// Sized for what they hold rather than for reading, so they take no floor.
// Every word this table says, in the two languages the Framework speaks. The
// rule is the shared one -- `pageText` reads the nearest [lang] and falls back
// to the document's -- and it is here because the table said only Russian: on
// an English page the list's toolbar read «Фильтр» and «Создать» beside a
// record panel that had switched, which is the sort of thing only a language
// switch in an admin shows (owner, 2026-10-10).
//
// An attribute still wins over both: a consumer who names a word names it.
const TABLE_TEXT = {
    en: {
        filterText: "Filter",
        clearText: "Clear",
        searchPlaceholder: "Search",
        createText: "Create",
        pinColumnLabel: "Pin the column",
        templateMoveLabel: "Move the template",
        templatePinLabel: "Keep the template at the top",
        templateActionsLabel: "Template actions",
        unpinColumnLabel: "Unpin the column",
        loadingText: "Loading",
        emptyText: "No data",
        errorText: "The data could not be loaded",
        retryText: "Try again",
        columnMoveLabel: "Move the column",
        filterApplyText: "Apply the filter",
        columnSearchPlaceholder: "Search the fields",
        columnSearchEmptyText: "No field matches",
        editActionText: "Edit",
        viewActionText: "View",
        deleteActionText: "Delete",
        actionsColumnLabel: "Actions",
        settingsColumnLabel: "Settings",
        selectAllLabel: "Select every row",
        defaultTemplateText: "Default",
        fieldsTabText: "Fields",
        templatesTabText: "Templates",
        saveTemplateLabel: "Save the filter as a template",
        filterSettingsLabel: "Filter settings",
        fieldWordText: "field",
        rowActionsLabel: "Row actions",
        treeExpand: "Expand",
        listFilterLabel: "Filter, {count} active",
        sortLabel: "Sort",
        sortNone: "As the host orders it",
        sortAscending: "ascending",
        sortDescending: "descending",
        closeText: "Close",
        treeCollapse: "Collapse",
        treeMore: "Show more",
        createOptionsLabel: "Ways to create",
        resetText: "Reset",
        contextSaveTemplateLabel: "Saving the filter as a template",
        contextFilterSettingsLabel: "Filter settings",
        contextFieldFilterLabel: "Filtering by",
        contextRowActionsLabel: "Row actions",
        contextCreateOptionsLabel: "Ways to create",
        contextViewActionsLabel: "View actions",
        contextTableSettingsLabel: "Table settings",
        templateNamePlaceholder: "Name",
        makeDefaultLabel: "Make it the default",
        saveForEveryoneLabel: "Save for everyone",
        applyText: "Apply",
        renameText: "Rename",
        restoreText: "Restore",
        selectRowLabel: "Select the row",
        menuLabel: "Menu",
        contextTemplateActionsLabel: "Template actions",
        operatorEq: "Exactly",
        operatorGt: "Greater than",
        operatorGte: "Greater or equal",
        operatorLt: "Less than",
        operatorLte: "Less or equal",
        operatorBetween: "Range",
        operatorContains: "Contains",
        operatorStartsWith: "Starts with",
        operatorAny: "Any date",
        operatorToday: "Today",
        operatorWeek: "This week",
        operatorMonth: "This month",
        operatorIn: "In the list",
    },
    ru: {
        filterText: "Фильтр",
        clearText: "Очистить",
        searchPlaceholder: "Поиск",
        createText: "Создать",
        pinColumnLabel: "Закрепить столбец",
        templateMoveLabel: "Переместить шаблон",
        templatePinLabel: "Закрепить шаблон сверху",
        templateActionsLabel: "Действия с шаблоном",
        unpinColumnLabel: "Открепить столбец",
        loadingText: "Загрузка данных",
        emptyText: "Нет данных",
        errorText: "Не удалось загрузить данные",
        retryText: "Повторить",
        columnMoveLabel: "Переместить столбец",
        filterApplyText: "Применить фильтр",
        columnSearchPlaceholder: "Поиск по полям",
        columnSearchEmptyText: "Поля не найдены",
        editActionText: "Редактировать",
        viewActionText: "Посмотреть",
        deleteActionText: "Удалить",
        actionsColumnLabel: "Действия",
        settingsColumnLabel: "Настройки",
        selectAllLabel: "Выбрать все строки",
        defaultTemplateText: "По умолчанию",
        fieldsTabText: "Поля",
        templatesTabText: "Шаблоны",
        saveTemplateLabel: "Сохранение шаблона фильтра",
        filterSettingsLabel: "Настройки фильтра",
        fieldWordText: "поле",
        rowActionsLabel: "Действия со строкой",
        treeExpand: "Развернуть",
        listFilterLabel: "Фильтр, активных условий: {count}",
        sortLabel: "Сортировка",
        sortNone: "Как задано источником",
        sortAscending: "по возрастанию",
        sortDescending: "по убыванию",
        closeText: "Закрыть",
        treeCollapse: "Свернуть",
        treeMore: "Показать ещё",
        createOptionsLabel: "Варианты создания",
        resetText: "Сбросить",
        contextSaveTemplateLabel: "Сохранение шаблона фильтра",
        contextFilterSettingsLabel: "Настройки фильтра",
        contextFieldFilterLabel: "Настройка фильтра",
        contextRowActionsLabel: "Действия со строкой",
        contextCreateOptionsLabel: "Варианты создания",
        contextViewActionsLabel: "Действия с представлением",
        contextTableSettingsLabel: "Настройки таблицы",
        templateNamePlaceholder: "Название",
        makeDefaultLabel: "Сделать по умолчанию",
        saveForEveryoneLabel: "Сохранить для всех",
        applyText: "Применить",
        renameText: "Переименовать",
        restoreText: "Восстановить",
        selectRowLabel: "Выбрать строку",
        menuLabel: "Меню",
        contextTemplateActionsLabel: "Действия с шаблоном",
        operatorEq: "Точно",
        operatorGt: "Больше чем",
        operatorGte: "Больше или равно",
        operatorLt: "Меньше чем",
        operatorLte: "Меньше или равно",
        operatorBetween: "Диапазон",
        operatorContains: "Содержит",
        operatorStartsWith: "Начинается с",
        operatorAny: "Любая дата",
        operatorToday: "Сегодня",
        operatorWeek: "Неделя",
        operatorMonth: "Месяц",
        operatorIn: "В списке",
    },
};

const SYSTEM_COLUMN_KEYS = new Set(["select", "settings", "actions"]);

const positioning = () => globalThis.SF?.Position || BundledPosition;

// Published Composition ports (simai.composition.port-manifest.v1, sf-table@1.0.0).
const SF_TABLE_PORTS = Object.freeze({
    outputs: Object.freeze({selection: "record-ids.v1"}),
    inputs: Object.freeze({context: "record-ids.v1"}),
});
const MAX_PORT_RECORD_IDS = 1000;

function hasControlCharacter(value) {
    for (let index = 0; index < value.length; index += 1) {
        const code = value.charCodeAt(index);
        if (code < 32 || code === 127) return true;
    }
    return false;
}

function checkedRecordIds(value) {
    if (!Array.isArray(value) || value.length > MAX_PORT_RECORD_IDS
        || Array.from(value).some((id) => !(
            (typeof id === "string" && id.length > 0 && id.length <= 256 && !hasControlCharacter(id))
            || Number.isSafeInteger(id)
        ))
        || new Set(Array.from(value, (id) => `${typeof id}:${id}`)).size !== value.length) {
        throw new TypeError("Record identities must be unique nonempty strings or safe integers");
    }
    return Object.freeze([...value]);
}


// Two tables on one page share nothing: ids, the settings key fallback and the
// portal are all derived from this counter, never from the component name.
let tableInstances = 0;

class SfTable extends SfBaseElement {
    static get sfPorts() {
        return SF_TABLE_PORTS;
    }

    static get props() {
        return {
            templateName: {attribute: "template", default: "default"},
            ariaLabel: {attribute: "aria-label", default: "Data table"},
            selectable: {type: Boolean, default: true},
            // Who owns the vertical scroll. `self` keeps the rows in their own
            // scroll area, which is what a host that bounds the table's height
            // wants and what every page has had so far. `page` gives the
            // vertical scroll to the document, so the head stays visible while
            // the page scrolls; the rows then scroll sideways only.
            verticalScroll: {attribute: 'vertical-scroll', default: 'self', values: ['self', 'page']},
            // How the records are drawn. `auto` is a table while the table's own
            // container is at least `list-below` pixels wide and a compact list
            // of records below that -- a phone, or a list beside an open panel;
            // `table` and `list` hold one of them whatever the width (owner,
            // 2026-10-12: one mobile view, the compact list). A table is a table
            // unless asked: sf-data-view asks for auto on a page's list.
            layout: {default: 'table', values: ['auto', 'table', 'list']},
            listBelow: {attribute: 'list-below', type: Number, default: 600},
            // How many fields a record shows under its title in the list, chosen
            // by the columns' priority (lower first).
            listFields: {attribute: 'list-fields', type: Number, default: 3},
            toolbar: {type: Boolean, default: true},
            settings: {type: Boolean, default: true},
            pagin: {type: Boolean, default: false},
            actions: {type: Boolean, default: true},
            // The row action column alone; the create button still follows actions.
            rowActions: {attribute: "row-actions", type: Boolean, default: true},
            // The create button and its menu of kinds, inside actions: a data view
            // turns it off for a host on port 1.10.0 that does not announce
            // create-record, so nothing is offered that nobody would answer.
            creatable: {type: Boolean, default: true},
            // The button that saves the filter as a template. A data view turns it
            // off when the host keeps no saved views: a template saved there went
            // nowhere and was gone on the next load.
            filterTemplates: {attribute: "filter-templates", type: Boolean, default: true},
            // The gear in the head of the settings column, which opens the column
            // settings. Off, the column still carries each row's menu.
            settingsMenu: {attribute: "settings-menu", type: Boolean, default: true},
            // The step of the size ladder the table's own controls take: its
            // toolbar, its row boxes and menus, its filters and context menus.
            // 1/2 is the dense admin step -- 32px controls, 14/20 text, the
            // height of the admin menu beside it -- and the rows follow.
            size: {default: "1", values: ["1/2", "1"]},
            filterText: {attribute: "filter-text", default: "Filter"},
            clearText: {attribute: "clear-text", default: "Clear"},
            searchPlaceholder: {attribute: "search-placeholder", default: "Search"},
            createText: {attribute: "create-text", default: "Create"},
            pinColumnLabel: {attribute: "pin-column-label", default: "Pin the column"},
            unpinColumnLabel: {attribute: "unpin-column-label", default: "Unpin the column"},
            paginationTotal: {
                attribute: "pagination-total",
                type: Number,
                default: 10,
            },
            paginationPageSize: {
                attribute: "pagination-page-size",
                type: Number,
                default: 10,
            },
            pageSizes: {
                attribute: "page-sizes",
                default: "10,20,30,40",
            },
            contextMenuColumns: {
                attribute: "context-menu-columns",
                type: Number,
                // One, because one is what every table has drawn since this input
                // existed: the read was bare, so neither the declared 2 nor a value
                // a host passed ever reached the menu, which fell to `?? 1`. The
                // getter below makes the input work for the first time, and the
                // default is set to the shipped behaviour so that making it work
                // changes nothing for anyone who does not ask.
                default: 1,
            },
            tableSettingsKey: {
                attribute: "table-settings-key",
                // Empty, because the effective default was never a string at all:
                // the bare read fell through to the attribute, then the element's
                // id, then its instance id, so every table had its own key. The
                // declaration said "users" and the manifest said
                // "admin.collection" -- two different untruths about the same
                // input. Either one, read properly, would have made every table on
                // a page share one settings key and overwrite the others.
                default: "",
            },
            dataState: {
                attribute: "data-state",
                default: "auto",
                values: ["auto", "loading", "populated", "empty", "error"],
            },
            loadingText: {attribute: "loading-text", default: "Loading"},
            emptyText: {attribute: "empty-text", default: "No data"},
            errorText: {attribute: "error-text", default: "The data could not be loaded"},
            retryText: {attribute: "retry-text", default: "Try again"},
            columnMoveLabel: {
                attribute: "column-move-label",
                default: "Move the column",
            },
            filterApplyText: {
                attribute: "filter-apply-text",
                default: "Apply the filter",
            },
            columnSearchPlaceholder: {
                attribute: "column-search-placeholder",
                default: "Search the fields",
            },
            columnSearchEmptyText: {
                attribute: "column-search-empty-text",
                default: "No field matches",
            },
        };
    }

    // The manifest's declared defaults for these two were fixed in the same
    // commit as these getters and then lost: a rebase replayed a hand-picked list
    // of source files and classified smart.manifest.json as regenerable, which is
    // true of its provenance and false of its declarations. Two pairs shipped with
    // the getters resolving one thing and the manifest declaring another. The
    // other Framework session's defaults contract -- the manifest default against
    // the value a component resolves with no attribute set -- is what catches this
    // class, and it would have caught it here.
    // Declaring a prop creates no accessor in this Framework. These two were read
    // off `this` and were undefined, which the other Framework session's new
    // contract now names. Each is written so that reading it properly preserves
    // the behaviour that shipped, rather than applying a default that never
    // applied.
    get contextMenuColumns() {
        const declared = this.getNumberAttr?.("context-menu-columns", 1) ?? 1;
        return Number.isFinite(declared) && declared > 0 ? declared : 1;
    }

    get tableSettingsKey() {
        return this.getAttribute?.("table-settings-key") || "";
    }

    constructor() {
        super();

        this._portalContainer = null;
        this._instanceId = `sf-table-${++tableInstances}`;
        this._selectionPortKey = "[]";
        this._routeContext = null;
        this._querySequence = 0;
        this._pendingQuerySequence = null;
        this._abandonedQuery = false;
        this._filterOptionPages = new Map();
        this._contextViewportBound = false;
        this._filterTemplateDragCleanups = [];
        this._columnSettingsDragCleanups = [];
        this.contextKeyEvent = this.contextKeyEvent.bind(this);
        this.contextViewportEvent = this.contextViewportEvent.bind(this);

        this.ACTIONS_SETTINGS_ROW =
            [
                {
                    name: this.text('editActionText'),
                    id: 'edit',
                    component: {
                        type: 'icon-button',
                        props: {
                            kind: 'icon',
                            size: '1',
                            appearance: 'link',
                            scheme: 'on-surface',
                            icon: 'edit',
                            ariaLabel: this.text('editActionText'),
                            value: 'edit',
                        }
                    }
                },
                {
                    name: this.text('viewActionText'),
                    id: 'view',
                    component: {
                        type: 'icon-button',
                        props: {
                            kind: 'icon',
                            size: '1',
                            appearance: 'link',
                            scheme: 'on-surface',
                            icon: 'visibility',
                            ariaLabel: this.text('viewActionText'),
                            value: 'view',

                        }
                    }
                },
                {
                    name: this.text('deleteActionText'),
                    id: 'delete',
                    component: {
                        type: 'icon-button',
                        props: {
                            kind: 'icon',
                            size: '1',
                            appearance: 'link',
                            scheme: 'on-surface',
                            icon: 'delete',
                            ariaLabel: this.text('deleteActionText'),
                            value: 'delete'
                        }
                    }
                },
            ];

        // Row actions are a system column like select and settings: always last,
        // not resizable or draggable, and never part of saved column settings.
        this.TABLE_ACTIONS_COLUMN = {
            key: "actions",
            label: this.text("actionsColumnLabel"),
            system: true,
        }

        this.TABLE_SETTINGS_COLUMN = {
            key: "settings",
            label: "settings",
            system: true,
            width: 'calc(var(--sf-text-height-1) + (var(--sf-ui-1--space-y-tightness-default) * 2) + var(--sf-b6))',
            component: {
                type: "icon-button",
                props: {
                    size: "1",
                    appearance: "link",
                    scheme: "on-surface",
                    icon: "settings",
                    ariaLabel: this.text("settingsColumnLabel"),
                    value: "settings",
                    '@click': (e) => {
                        this.openContextMenu(e, {menu: 'table-settings'})
                    }
                },
            },
        };
        this.contextEvent = this.contextEvent.bind(this);
        this.hoverEvents = this.hoverEvents.bind(this);
        this.outEvent = this.outEvent.bind(this);
        this.onColumnResizePointerDown = this.onColumnResizePointerDown.bind(this);
        this.onColumnResizePointerMove = this.onColumnResizePointerMove.bind(this);
        this.onColumnResizePointerUp = this.onColumnResizePointerUp.bind(this);
        this.onColumnDragPointerDown = this.onColumnDragPointerDown.bind(this);
        this.onColumnDragPointerMove = this.onColumnDragPointerMove.bind(this);
        this.onColumnDragPointerUp = this.onColumnDragPointerUp.bind(this);
        this.onColumnDragPointerCancel = this.onColumnDragPointerCancel.bind(this);
        this._contextEventBound = false;
        this._hoverEventBound = false;
        this._columnResizeEventBound = false;
        this._columnDragEventBound = false;
        this._columnResizeState = null;
        this._columnDragState = null;
        this._columnAnimationToken = 0;
        this.searchChange = this.scheduleSearchChange.bind(this);
        this._searchDebounceTimer = null;
        this._searchPendingProps = null;
        this._searchHoldKey = '';
        this.refs = {
            contextMenu: this.createRef(),
            items: new Map(),
            templateForm: this.createRef(),
            renameInput: this.createRef(),
            table: this.createRef(),
            scroll: this.createRef(),
            resizeGuide: this.createRef(),
        }
        this._currentRowId = null;
        this._checkboxMeasureToken = 0;
        this._responsiveWidth = 0;
        this._responsiveObserver = null;
        this._filterTemplatesLoaded = false;
        this.tempSettings = null;
        this.state = {
            settingsChecked: false,
            bulkActions: [],
            createItems: [],
            // What the toolbar's own control offers, straight from the host.
            toolbarActions: [],
            // What is typed over the list of fields in the column settings.
            columnSettingsQuery: "",
            sort: {key: null, direction: null},

            saveInputValue: '',
            search: {
                searchActive: false,
                value: ''
            },
            contextMenu: {
                open: false,
            },
            actions: this.getActions().map((action) => Object.assign(action, {visible: true})),
            actionActive: this.getProp('actions'),
            columnSettings: {},
            selectIndeterminate: false,
            filterDataDirty: false,
            filter: {
                selectedTemplateKey: 'default',
                fields: [],
                templates: [],
                edit: {
                    rename: ''
                }
            },
        }
        this.TABLE_SELECT_COLUMN = {
            key: "select",
            label: "select",
            system: true,
            width: 'calc(var(--sf-text-height-1) + var(--sf-b6) + calc(var(--sf-px) * 2))',
            component: {
                type: "checkbox",
                props: {
                    position: 'end',
                    rootClass: 'content-main-between',
                    size: this.size,
                    '@change': (event) => {
                        const checked = Boolean(event.target.checked);
                        // Marking the page leaves no single row as the anchor a
                        // later Shift-click could extend from, so it is dropped
                        // rather than left pointing at whatever was clicked last.
                        this._selectionAnchorId = null;
                        this.set({selectIndeterminate: false, settingsChecked: checked});
                        this.updateRows((row) => ({
                            ...row,
                            select: this.getDefaultRowCell(this.TABLE_SELECT_COLUMN, {
                                ...row, selected: checked, checked,
                            }),
                        }), "select-all");
                    },
                    indeterminate: this.state.selectIndeterminate,
                    checked: this.state.settingsChecked,
                    ariaLabel: this.text("selectAllLabel"),
                },
            },
            rowComponent: {
                type: "checkbox",
                props: {
                    position: 'end',
                    rootClass: 'content-main-between',
                    size: this.size,

                },
            },
        };
    }

    getActions() {
        if (this.getProp('actions')) {
            return this.ACTIONS_SETTINGS_ROW.map((action) => {
                const {name, id} = action;
                return {name, id}
            })
        } else {
            return [];
        }
    }

    isSearchHoldKey(key = '') {
        return key === 'Backspace' || key === 'Delete';
    }

    clearSearchDebounce() {
        if (this._searchDebounceTimer) {
            clearTimeout(this._searchDebounceTimer);
            this._searchDebounceTimer = null;
        }
    }

    scheduleSearchChange(props = {}) {
        this._searchPendingProps = props;
        this.clearSearchDebounce();

        if (this._searchHoldKey) {
            return this;
        }

        this._searchDebounceTimer = setTimeout(() => {
            const pendingProps = this._searchPendingProps || {};
            this._searchPendingProps = null;
            this._searchDebounceTimer = null;
            this.updateSearchState(pendingProps);
        }, 500);

        return this;
    }

    handleSearchKeyDown(event) {
        if (!this.isSearchHoldKey(event?.key)) {
            return this;
        }

        this._searchHoldKey = event.key;
        this.clearSearchDebounce();
        return this;
    }

    handleSearchKeyUp(event) {
        if (!this._searchHoldKey || event?.key !== this._searchHoldKey) {
            return this;
        }

        this._searchHoldKey = '';
        if (this._searchPendingProps) {
            this.scheduleSearchChange(this._searchPendingProps);
        }

        return this;
    }

    updateSearchState(props = {}, isActiveState = true) {
        const currentSearch = this.state.search || {};

        let needUpdate = false;

        for (const [key, value] of Object.entries(props)) {
            if (!Object.is(currentSearch[key], value)) {
                needUpdate = true;
                break;
            }
        }
        if (!needUpdate) return this;
        this.set((prevState) => {
            return {
                search: {
                    ...prevState.search,
                    ...props
                }
            }
        }, () => {
            if(!isActiveState) return false;
            this.dispatchEvent(new CustomEvent('onSearchEnd', {
                bubbles: true,
                composed: true,
                detail: this.state.search.value,
            }));
            this.dispatchTableEvent('sf-table-search-change', this.state.search.value);
        })
    }

    dispatchTableEvent(name, detail = null) {
        this.dispatchEvent(new CustomEvent(name, {
            bubbles: true,
            composed: true,
            detail,
        }));
        return this;
    }

    requestActionIntent(actionId, recordIds) {
        if (typeof actionId !== 'string' || !actionId.trim()
            || !Array.isArray(recordIds) || recordIds.length === 0
            || Array.from(recordIds).some((id) => !(
                (typeof id === 'string' && id.length > 0) || Number.isSafeInteger(id)
            ))) {
            throw new TypeError('Action intent requires an opaque action ID and nonempty typed record IDs');
        }
        const detail = Object.freeze({
            action_id: actionId,
            record_ids: Object.freeze([...recordIds]),
        });
        return this.dispatchTableEvent('sf-table-action-intent', detail);
    }

    getSelectedRecordIds() {
        return this.getTableData().rows
            .filter((row) => row.select?.component?.props?.checked === true)
            .map((row) => row.id);
    }

    setBulkActions(actions = []) {
        if (!Array.isArray(actions) || Array.from(actions).some((action) =>
            !action || typeof action !== 'object'
            || typeof action.id !== 'string' || !action.id.trim()
            || typeof action.name !== 'string' || !action.name.trim()
            || Object.keys(action).some((key) => key !== 'id' && key !== 'name'))) {
            throw new TypeError('Bulk actions require opaque id and name only');
        }
        return this.set({bulkActions: actions.map(({id, name}) => ({id, name}))});
    }

    renderBulkActions() {
        if (!this.state.bulkActions?.length) return nothing;
        const selected = this.getSelectedRecordIds();
        return html`<div class="flex flex-wrap gap-1/3">
            ${this.state.bulkActions.map((action) => html`<sf-button
                text=${action.name} ?disabled=${selected.length === 0}
                @click=${() => {
                    const ids = this.getSelectedRecordIds();
                    if (ids.length) this.requestActionIntent(action.id, ids);
                }}></sf-button>`)}
        </div>`;
    }

    // Items for the create split button menu. Choosing one (or the main
    // button, with item null) emits sf-table-create-intent; the host opens its form.
    setCreateItems(items = []) {
        if (!Array.isArray(items) || Array.from(items).some((item) =>
            !item || typeof item !== 'object'
            || typeof item.id !== 'string' || !item.id.trim()
            || typeof item.name !== 'string' || !item.name.trim()
            || Object.keys(item).some((key) => key !== 'id' && key !== 'name'))) {
            throw new TypeError('Create items require opaque id and name only');
        }
        return this.set({createItems: items.map(({id, name}) => ({id, name}))});
    }

    requestCreateIntent(itemId = null) {
        if (itemId !== null && !this.state.createItems.some((item) => item.id === itemId)) {
            throw new TypeError(`sf-table has no create item ${String(itemId)}`);
        }
        return this.dispatchTableEvent('sf-table-create-intent', Object.freeze({item: itemId}));
    }

    // The actions of the view itself, set by the host: the toolbar's control is
    // a menu of them. Round three took it off column settings, which the gear
    // in the head already owns, and gave it the job the Bitrix-like tables give
    // it -- everything the page used to carry as its own buttons.
    setToolbarActions(items = []) {
        if (!Array.isArray(items) || Array.from(items).some((item) =>
            !item || typeof item !== 'object'
            || typeof item.id !== 'string' || !item.id.trim()
            || typeof item.label !== 'string' || !item.label.trim()
            || (item.icon != null && typeof item.icon !== 'string')
            || (item.href != null && !this.isSameSiteHref(item.href))
            || Object.keys(item).some((key) => !['id', 'label', 'icon', 'href'].includes(key)))) {
            throw new TypeError('Toolbar actions require id and label, and an optional icon and same-site href');
        }
        return this.set({
            toolbarActions: items.map(({id, label, icon, href}) => ({
                id, label, ...(icon ? {icon} : {}), ...(href ? {href} : {}),
            })),
        });
    }

    getToolbarActions() {
        return (this.state.toolbarActions || []).map((item) => ({...item}));
    }

    // A link out of the table may only go to this same site. A host that sends
    // another origin is sending the person somewhere the table cannot vouch for.
    isSameSiteHref(href = "") {
        const value = String(href ?? "").trim();
        if (!value || value.startsWith("//")) return false;
        try {
            return new URL(value, document.baseURI).origin === new URL(document.baseURI).origin;
        } catch {
            return false;
        }
    }

    // The event carries the id, and an item with an href also goes there. The
    // event is cancelable, so a host that routes its own pages keeps the view
    // on the page: it calls preventDefault and navigates itself.
    requestToolbarAction(actionId) {
        const action = this.getToolbarActions().find((item) => item.id === actionId);
        if (!action) {
            throw new TypeError(`sf-table has no toolbar action ${String(actionId)}`);
        }
        const event = new CustomEvent('sf-table-toolbar-action', {
            bubbles: true,
            composed: true,
            cancelable: true,
            detail: Object.freeze({action: action.id, href: action.href ?? null}),
        });
        const allowed = this.dispatchEvent(event);
        if (allowed && action.href && this.isSameSiteHref(action.href)) {
            window.location.assign(action.href);
        }

        return this;
    }

    renderToolbarActionsContextMenu(data) {
        const {x, y} = data;
        // The standard menu's entries, not link buttons: a link button in a
        // menu drew centred text on a light block that looked like no other
        // menu in the admin (owner, 2026-10-13).
        const actions = this.getToolbarActions();
        const entries = actions.map((action) => ({
            id: String(action.id),
            text: action.label,
            ...(action.icon ? {icon: action.icon} : {}),
        }));
        const select = (event) => {
            const action = actions.find((item) => String(item.id) === event.detail?.id);
            this.closeContextMenu();
            if (action) this.requestToolbarAction(action.id);
        };
        return html`
            <sf-context-menu
                    :key=${x + y}
                    aria-label=${this.getContextMenuLabel({...data, menu: 'toolbar-actions'})}
                    style="
            position: fixed;
            left: ${x}px;
            inset-block-start: calc(${y}px + calc(var(--sf-focus-outline-width) * 2));
            z-index: var(--sf-table-context-menu--z-index, var(--sf-z-index-9));
          "
                    data-context-main-menu
                    .entries=${entries}
                    @sf-context-menu-select=${select}
            ></sf-context-menu>
        `;
    }

    // The list has no column heads to sort by, so its sort is a menu: the host's
    // own order, then each sortable column one way and the other. Choosing one
    // raises sf-table-sort-change exactly as a head does.
    renderListSortContextMenu(data) {
        const {x, y} = data;
        const current = this.state.sort || {};
        const choose = (key, direction) => {
            this.closeContextMenu();
            this.setSort(key, direction);
            this.dispatchTableEvent('sf-table-sort-change', Object.freeze({key, direction}));
        };
        const choices = new Map();
        const option = (text, key, direction) => {
            const id = `${key ?? ''}:${direction ?? ''}`;
            choices.set(id, [key, direction]);
            return {id, text, kind: 'radio', checked: current.key === key && current.direction === direction};
        };
        const select = (event) => {
            const choice = choices.get(event.detail?.id);
            if (choice) choose(...choice);
        };
        const sortable = this.getColumns().filter((column) => column?.sortable && !column.system
            && !["select", "settings", "actions"].includes(column.key));
        const items = [
            option(this.text('sortNone'), null, null),
            ...sortable.flatMap((column) => [
                option(`${column.label ?? column.key}, ${this.text('sortAscending')}`, column.key, 'asc'),
                option(`${column.label ?? column.key}, ${this.text('sortDescending')}`, column.key, 'desc'),
            ]),
        ];
        return html`
            <sf-context-menu
                    :key=${x + y}
                    aria-label=${this.getContextMenuLabel({...data, menu: 'list-sort'})}
                    style="
            position: fixed;
            left: ${x}px;
            inset-block-start: calc(${y}px + calc(var(--sf-focus-outline-width) * 2));
            z-index: var(--sf-table-context-menu--z-index, var(--sf-z-index-9));
          "
                    data-context-main-menu
                    .entries=${items}
                    @sf-context-menu-select=${select}
            ></sf-context-menu>
        `;
    }

    renderCreateContextMenu(data) {
        const {x, y} = data;
        const createItems = this.state.createItems;
        const entries = createItems.map((item) => ({id: String(item.id), text: item.name}));
        const select = (event) => {
            const item = createItems.find((entry) => String(entry.id) === event.detail?.id);
            this.closeContextMenu();
            if (item) this.requestCreateIntent(item.id);
        };
        return html`
            <sf-context-menu
                    :key=${x + y}
                    aria-label=${this.getContextMenuLabel({...data, menu: 'create'})}
                    style="
            position: fixed;
            left: ${x}px;
            inset-block-start: calc(${y}px + calc(var(--sf-focus-outline-width) * 2));
            z-index: var(--sf-table-context-menu--z-index, var(--sf-z-index-9));
          "
                    data-context-main-menu
                    .entries=${entries}
                    @sf-context-menu-select=${select}
            ></sf-context-menu>
        `;
    }

    // Sorting reports intent only: the table marks the header and emits
    // sf-table-sort-change; the host reorders or reloads the rows.
    getSort() {
        return {...this.state.sort};
    }

    setSort(key = null, direction = null) {
        if (key === null || direction === null) {
            return this.set({sort: {key: null, direction: null}});
        }
        if (typeof key !== 'string' || !key || !['asc', 'desc'].includes(direction)) {
            throw new TypeError('Sort requires a column key and direction asc or desc');
        }
        return this.set({sort: {key, direction}});
    }

    toggleSort(key) {
        const current = this.state.sort || {};
        const direction = current.key !== key ? 'asc'
            : current.direction === 'asc' ? 'desc'
            : current.direction === 'desc' ? null
            : 'asc';
        this.setSort(direction ? key : null, direction);
        return this.dispatchTableEvent('sf-table-sort-change', Object.freeze({key, direction}));
    }

    // Pointer capture sends the click that ends a column drag or resize to the
    // header cell; that click must not also sort the column.
    suppressNextHeaderClick() {
        this._suppressHeaderClick = true;
        setTimeout(() => {
            this._suppressHeaderClick = false;
        }, 0);
    }

    handleHeaderSortClick(event, column = {}) {
        if (column.sortable !== true || column.system || this._suppressHeaderClick) return;
        const target = event.composedPath?.()[0] || event.target;
        // A click on the width strip is not a click on the header's own title.
        if (target instanceof Element && target.closest('.sf-table-column-resizer')) return;
        this.toggleSort(column.key);
    }

    // Emits the selection output once per change of the explicit selection.
    syncSelectionPort() {
        if (!this._isMounted) return this;
        const ids = this.getSelectedRecordIds();
        const key = JSON.stringify(ids.map((id) => [typeof id, id]));
        if (key === this._selectionPortKey) return this;
        this._selectionPortKey = key;
        this.dispatchEvent(new CustomEvent("sf-port-output", {
            bubbles: true,
            composed: true,
            detail: Object.freeze({port: "selection", value: Object.freeze([...ids])}),
        }));
        return this;
    }

    // One row's box has been marked. A plain click marks that row; Shift+click
    // marks every row between the last one marked and this one, with the value
    // the clicked box just took, which is what makes unmarking a range work too.
    applyRowSelection(rowId, checked, {range = false} = {}) {
        const rows = this.getTableData().rows;
        const sameRow = (row, id) => String(row?.id ?? "") === String(id ?? "");
        const index = rows.findIndex((row) => sameRow(row, rowId));
        if (index < 0) return this;
        const anchorIndex = range && this._selectionAnchorId != null
            ? rows.findIndex((row) => sameRow(row, this._selectionAnchorId))
            : -1;
        const from = anchorIndex < 0 ? index : Math.min(anchorIndex, index);
        const to = anchorIndex < 0 ? index : Math.max(anchorIndex, index);
        // The page box is cleared first: a row cell asks `settingsChecked`
        // whether it is marked, so leaving it set would mark the rows this call
        // is unmarking back again.
        this.set({settingsChecked: false});
        this.updateRows((row, position) => (position < from || position > to ? row : {
            ...row,
            selected: checked,
            checked,
            select: this.getDefaultRowCell(this.TABLE_SELECT_COLUMN, {...row, selected: checked, checked}),
        }), range && anchorIndex >= 0 ? "select-range" : "select-row");
        this._selectionAnchorId = rowId;
        return this.syncSelectAllState();
    }

    // The page box answers for the rows under it: all of them, some of them, or
    // none. It is computed from the rows rather than counted as rows are
    // clicked, so appended rows and a replaced page cannot drift from it.
    syncSelectAllState() {
        const count = this.getSelectedRecordIds().length;
        const total = this.getTableData().rows.length;
        return this.set({
            settingsChecked: total > 0 && count === total,
            selectIndeterminate: count > 0 && count < total,
        });
    }

    // What a pointer press has to settle before the click arrives: whether a
    // range is being extended, and that the press must not start a text
    // selection. A Shift-click or a second click otherwise selects the text of
    // every row it passes over, which is what the host reported.
    handleRowPointerDown(event) {
        this._selectRangeModifier = event?.shiftKey === true;
        if (event?.shiftKey === true || event?.detail > 1) {
            event.preventDefault?.();
        }
        return this;
    }

    // Controls inside a row answer for themselves. A click on a box, a row
    // menu, a link, a button or a field is that control's click, and neither
    // focuses the row nor opens the record.
    isRowControlTarget(target) {
        if (!(target instanceof Element)) return false;
        return Boolean(target.closest(
            'td[data-key="select"], td[data-key="settings"], a[href], button, input, select, textarea,'
            + ' [role="button"], [role="link"], sf-checkbox, sf-dropdown, sf-button, sf-icon-button',
        ));
    }

    // A single click makes the row the current one — wherever in the row it
    // lands. It does not mark it: marking is the box's job, and a click that did
    // both would let a stray click destroy a selection the person had built up.
    //
    // The first version skipped a click on anything interactive, which read
    // well until a consumer's cells turned out to be links: clicking the only
    // content a cell has left the row unmarked as current, and the rule looked
    // broken rather than careful. A control still does its own work — a link
    // still follows, a box still marks — and the row moving under the pointer
    // takes nothing away from that. Only opening the record stays off limits
    // from a control.
    handleRowClick(event, row, index = -1) {
        this.focusRow(this.getRowKey(row, index));
        return this;
    }

    // Which row this is, for the purpose of being the current one. The rest of
    // the template already keys a row by `id`, then `value`, then its position,
    // because the table is given rows keyed all three ways; the current row
    // followed `id` alone, so in a view whose rows carry another key a click
    // marked nothing and looked broken. Opening a record still needs a real id
    // — there is nothing to open without one.
    getRowKey(row, index = -1) {
        const value = row?.id ?? row?.value;
        if (value !== undefined && value !== null && String(value) !== "") {
            return String(value);
        }

        return index >= 0 ? `index:${index}` : "";
    }

    isCurrentRow(row, index = -1) {
        const key = this.getRowKey(row, index);
        return key !== "" && String(this._currentRowId ?? "") === key;
    }

    // The current row is marked in the page rather than through state. Going
    // through state re-rendered the rows on every click, and a render between a
    // box's click and its change event swallowed the change — one click on a
    // row was enough to lose a Shift-range selection. The field is read back by
    // the template, so a later render keeps the mark.
    focusRow(rowId) {
        if (rowId == null || rowId === "") return this;
        this._currentRowId = String(rowId);
        const table = this.refs.table?.value;
        if (!table) return this;
        const selector = `tbody tr[data-row-id="${String(rowId).replace(/"/gu, '\\"')}"]`;
        for (const marked of table.querySelectorAll("tbody tr[data-current]")) {
            marked.removeAttribute("data-current");
        }
        const row = table.querySelector(selector);
        row?.setAttribute("data-current", "");
        row?.focus?.({preventScroll: true});
        return this;
    }

    // A double click opens the record, and only when the record says it can be
    // opened: the row has to offer the view action. Without it the double click
    // does nothing at all — silently, because a person double-clicking a row
    // that cannot be opened has not made a mistake worth a message.
    canOpenRow(row) {
        return Array.isArray(row?.actions) && row.actions.includes("view");
    }

    handleRowOpen(event, row) {
        if (this.isRowControlTarget(event?.target) || !this.canOpenRow(row)) return this;
        const id = row?.id;
        if (id == null) return this;
        return this.requestActionIntent("view", [id]);
    }

    // The keyboard reaches the same two answers as the pointer: Enter opens the
    // row, Space marks it. Without them the row would be focusable and mute,
    // and marking would be reachable only by pointing at the box.
    // ─── the tree (data view port 1.8.0) ─────────────────────────────────────
    // A table is a tree when its rows carry __tree, which the data view writes
    // from the host's levels. The table only draws it and says what a person
    // asked for; which nodes exist and what opening one means is the view's.
    isTree() {
        return (this.getTableData?.().rows ?? []).some((row) => row?.__tree);
    }

    // The column a tree hangs from: the one the host marked as the title, or
    // the first column that holds data.
    treeColumnKey(columns = this.getColumns()) {
        if (!this.isTree()) return null;
        const data = columns.filter((column) => column?.key && !["select", "settings", "actions"].includes(column.key) && !column.system);
        return (data.find((column) => column.role === "title") ?? data[0])?.key ?? null;
    }

    toggleTreeRow(row) {
        if (!row?.__tree?.hasChildren) return this;
        return this.dispatchTableEvent("sf-table-tree-toggle", Object.freeze({id: row.id, expanded: row.__tree.expanded === true}));
    }

    requestTreeMore(row) {
        if (!row?.__tree?.more) return this;
        return this.dispatchTableEvent("sf-table-tree-more", Object.freeze({parent: row.__tree.parent, cursor: row.__tree.cursor ?? null}));
    }

    handleRowKeydown(event, row) {
        if (event?.target !== event?.currentTarget) return this;
        // A treegrid row: the right arrow opens a closed node, the left arrow
        // closes an open one.
        if (row?.__tree?.hasChildren && (event.key === "ArrowRight" || event.key === "ArrowLeft")) {
            const open = row.__tree.expanded === true;
            if ((event.key === "ArrowRight") !== open) {
                event.preventDefault?.();
                return this.toggleTreeRow(row);
            }
        }
        if (event.key === "Enter") {
            return this.handleRowOpen({target: event.currentTarget}, row);
        }
        if (event.key === " " || event.key === "Spacebar") {
            event.preventDefault?.();
            const checked = row?.select?.component?.props?.checked !== true;
            return this.applyRowSelection(row?.id, checked, {range: event.shiftKey === true});
        }
        return this;
    }

    clearSelection() {
        this._selectionAnchorId = null;
        this.set({settingsChecked: false, selectIndeterminate: false});
        if (!this.getSelectedRecordIds().length) return this;
        return this.updateRows((row) => ({
            ...row,
            selected: false,
            checked: false,
            select: this.getDefaultRowCell(this.TABLE_SELECT_COLUMN, {...row, selected: false, checked: false}),
        }), "select-clear");
    }

    // Composition input port. Only declared ports are accepted; the table never
    // resolves a relation itself. It asks the host through a typed query intent.
    sfPortInput(port, value, meta = {}) {
        if (!Object.hasOwn(SF_TABLE_PORTS.inputs, port)) {
            throw new TypeError(`sf-table has no input port ${String(port)}`);
        }
        const recordIds = checkedRecordIds(value);
        const sequence = Number.isSafeInteger(meta?.sequence) && meta.sequence > this._querySequence
            ? meta.sequence
            : this._querySequence + 1;
        this._routeContext = Object.freeze({record_ids: recordIds});
        this._querySequence = sequence;
        this.clearSelection();
        this.requestContextQuery(sequence);
        return undefined;
    }

    requestContextQuery(sequence) {
        this._pendingQuerySequence = sequence;
        this._abandonedQuery = false;
        this.setDataState("loading");
        return this.dispatchTableEvent("sf-table-query-intent", Object.freeze({
            reason: "context",
            context: this._routeContext,
            sequence,
        }));
    }

    onConnected() {
        super.onConnected();
        if (typeof ResizeObserver !== "undefined" && !this._responsiveObserver) {
            this._responsiveObserver = new ResizeObserver(([entry]) => {
                const width = Math.floor(entry.contentRect.width);
                if (width > 0 && width !== this._responsiveWidth) {
                    this._responsiveWidth = width;
                    this.requestComponentUpdate("responsive-columns");
                }
                this.measurePinnedColumns();
            });
            // SfBaseElement is display: contents, so its own box has no width.
            // Observe the nearest visible container instead of the custom tag.
            const candidates = [this.querySelector('.sf-table'), this.parentElement, this.getRootNode()?.host];
            const target = candidates.find((element) => element?.getBoundingClientRect?.().width > 0)
                ?? candidates.find(Boolean) ?? this;
            this._responsiveObserver.observe(target);
        }
        if (this._abandonedQuery && this._routeContext) {
            this._querySequence += 1;
            this.requestContextQuery(this._querySequence);
        }
    }

    // Latest result wins: only the answer to the newest pending context query
    // is applied, and at most once.
    applyQueryResult(sequence, rows) {
        if (!Number.isSafeInteger(sequence) || sequence !== this._pendingQuerySequence || !Array.isArray(rows)) {
            return false;
        }
        this._pendingQuerySequence = null;
        this.setRows(rows);
        this.setDataState("auto");
        return true;
    }

    getQueryContext() {
        return this._routeContext;
    }

    getItemRef(id) {
        if (!this.refs.items.has(id)) {
            this.refs.items.set(id, this.createRef());
        }

        return this.refs.items.get(id);
    }

    // The table had its own copy of this, which created an element on every
    // render; the base's one keeps the element a position in the template
    // already has. Nothing else about the call changes.

    setIndeterminate() {
        const checked = this.state.data.rows.filter((row) => {
            return row.select.component.props.checked;
        })
        return checked.length;
    }


    getTableData() {
        const data = this.state?.data;

        return data && typeof data === "object"
            ? data
            : {
                columns: [],
                rows: [],
            };
    }

    isContextMenuEvent(event) {
        const target = event?.target;

        if (!(target instanceof Node)) {
            return false;
        }

        const portal = this._portalContainer;
        if (portal instanceof Node && (portal === target || portal.contains(target))) {
            return true;
        }

        const contextMenu = this.refs?.contextMenu?.value;
        if (!(contextMenu instanceof Node)) {
            return false;
        }

        if (contextMenu === target || contextMenu.contains(target)) {
            return true;
        }

        return event.composedPath?.().includes(contextMenu) || false;
    }

    contextEvent(event) {
        if (this.isContextMenuEvent(event)) {
            return;
        }

        // Nothing in the column settings window is pending any more -- the pin,
        // the tick and the order each apply as they are made -- so a click away
        // has nothing of theirs to lose. What it still drops is the filter form
        // a person was filling in, which is a query rather than a setting.
        this.tempSettings = null;
        this.closeContextMenu(false);
    }

    splitItemsByColumns(items = [], columnCount = 1) {
        const normalizedItems = Array.isArray(items) ? items : [];
        const normalizedColumnCount = Math.max(1, Number(columnCount) || 1);
        const itemsPerColumn = Math.ceil(
            normalizedItems.length / normalizedColumnCount,
        );

        if (!itemsPerColumn) {
            return [];
        }

        return Array.from({length: normalizedColumnCount}, (_, index) =>
            normalizedItems.slice(
                index * itemsPerColumn,
                (index + 1) * itemsPerColumn,
            ),
        ).filter((columnItems) => columnItems.length);
    }

    getPendingFilterState(filter = this.state?.filter || {}) {
        const tempFilter = this.tempSettings?.filter || {};
        const hasTempSelectedTemplateKey =
            Object.prototype.hasOwnProperty.call(
                tempFilter,
                "selectedTemplateKey",
            );

        return {
            ...filter,
            ...tempFilter,
            selectedTemplateKey:
                hasTempSelectedTemplateKey
                    ? tempFilter.selectedTemplateKey
                    : filter.selectedTemplateKey,
            templates: tempFilter.templates || filter.templates || [],
        };
    }

    getSortedFilterTemplates(filter = this.state?.filter || {}) {
        const templates = this.getFilterTemplates(filter);

        return templates
            .map((template, sourceIndex) => ({
                ...template,
                sourceIndex,
                order:
                    typeof template.order === "number"
                        ? template.order
                        : sourceIndex,
            }))
            .sort((left, right) => {
                if (Boolean(left.pinned) !== Boolean(right.pinned)) {
                    return left.pinned ? -1 : 1;
                }

                if (left.order !== right.order) {
                    return left.order - right.order;
                }

                return left.sourceIndex - right.sourceIndex;
            });
    }

    changeTempFilterTemplateSettings(key, patch = {}) {
        if (!key) return;

        const currentFilter = this.getPendingFilterState();
        const templates = this.getFilterTemplates(currentFilter).map((template) => {
                for (const [k, v] of Object.entries(patch)) {
                    template[k] = key === template.key ? v : !v;
                }
                return template;
            }
        );

        this.tempSettings = {
            ...(this.tempSettings || {}),
            filter: {
                ...(this.tempSettings?.filter || {}),
                selectedTemplateKey: currentFilter.selectedTemplateKey,
                templates,
            },
        };
        this.requestComponentUpdate("temp-filter-template");
    }

    patchTempFilterTemplateSettings(key, patch = {}) {
        if (!key) return;

        const currentFilter = this.getPendingFilterState();
        const templates = this.getFilterTemplates(currentFilter).map((template) =>
            template.key === key
                ? {
                    ...template,
                    ...(typeof patch === "function" ? patch(template) : patch),
                }
                : template,
        );

        this.tempSettings = {
            ...(this.tempSettings || {}),
            filter: {
                ...(this.tempSettings?.filter || {}),
                selectedTemplateKey: currentFilter.selectedTemplateKey,
                templates,
            },
        };
        this.requestComponentUpdate("temp-filter-template");
    }

    selectTempFilterTemplate(key) {
        if (!key) return;

        const currentFilter = this.getPendingFilterState();
        const templates = this.getFilterTemplates(currentFilter).map(
            (template) => ({
                ...template,
                selected: template.key === key,
            }),
        );

        this.tempSettings = {
            ...(this.tempSettings || {}),
            filter: {
                ...(this.tempSettings?.filter || {}),
                selectedTemplateKey: key,
                templates,
            },
        };
        this.requestComponentUpdate("temp-filter-template");
    }

    normalizeFilterTemplate(template = {}, sourceIndex = 0) {
        const key = template.key || `filter_template_${sourceIndex + 1}`;
        const data = template.data && typeof template.data === "object"
            ? template.data
            : {};

        return {
            ...template,
            key,
            label: template.label || data.template_name || key,
            data: {
                ...data,
                values: {...(data.values || {})},
                tags: {...(data.tags || {})},
            },
        };
    }

    getFilterTemplates(filter = this.state?.filter || {}) {
        const templates = (filter.templates || []).map((template, index) =>
            this.normalizeFilterTemplate(template, index),
        );

        if (templates.length) {
            return templates;
        }

        return [
            this.normalizeFilterTemplate({
                key: filter.selectedTemplateKey || "default",
                label: this.text("defaultTemplateText"),
                selected: true,
                order: 0,
                data: {},
            }),
        ];
    }

    getFilterTemplateByKey(key, filter = this.state?.filter || {}) {
        if (!key || !filter) return null;

        return (
            this.getFilterTemplates(filter).find(
                (template) => template.key === key,
            ) || null
        );
    }

    getSelectedFilterTemplate(filter = this.state?.filter || {}) {
        const templates = this.getFilterTemplates(filter);
        const hasSelectedTemplateKey =
            Object.prototype.hasOwnProperty.call(
                filter,
                "selectedTemplateKey",
            );

        if (hasSelectedTemplateKey && filter.selectedTemplateKey === "") {
            return null;
        }

        const selectedKey =
            filter.selectedTemplateKey ||
            templates.find((template) => template.selected)?.key ||
            templates[0]?.key ||
            "";

        return (
            this.getFilterTemplateByKey(selectedKey, {
                ...filter,
                templates,
            }) ||
            templates[0] ||
            null
        );
    }

    getCurrentFilterValues(filter = this.state?.filter || {}) {
        const selectedTemplate = this.getSelectedFilterTemplate(filter);
        return {...(selectedTemplate?.data?.values || {})};
    }

    getCurrentFilterTags(filter = this.state?.filter || {}) {
        const selectedTemplate = this.getSelectedFilterTemplate(filter);
        return {...(selectedTemplate?.data?.tags || {})};
    }

    normalizeFilterDataForCompare(value) {
        if (Array.isArray(value)) {
            return value.map((item) => this.normalizeFilterDataForCompare(item));
        }

        if (value && typeof value === "object") {
            return Object.keys(value)
                .sort()
                .reduce((result, key) => ({
                    ...result,
                    [key]: this.normalizeFilterDataForCompare(value[key]),
                }), {});
        }

        return value;
    }

    isSameFilterData(current, next) {
        return JSON.stringify(this.normalizeFilterDataForCompare(current || {})) ===
            JSON.stringify(this.normalizeFilterDataForCompare(next || {}));
    }

    getMergedFilterDraftData(
        filter = this.state?.filter || {},
        tempFilter = this.tempSettings?.filter || {},
    ) {
        const baseFilter = this.getPendingFilterState({
            ...filter,
            selectedTemplateKey:
                Object.prototype.hasOwnProperty.call(
                    tempFilter,
                    "selectedTemplateKey",
                )
                    ? tempFilter.selectedTemplateKey
                    : filter.selectedTemplateKey,
            templates: tempFilter.templates || filter.templates || [],
        });
        const currentTags = this.getCurrentFilterTags(baseFilter);
        const currentValues = this.getCurrentFilterValues(baseFilter);
        const tags = tempFilter.replaceTags
            ? {...(tempFilter.tags || {})}
            : {
                ...currentTags,
                ...(tempFilter.tags || {}),
            };
        const values = tempFilter.replaceValues
            ? {...(tempFilter.values || {})}
            : {
                ...currentValues,
                ...(tempFilter.values || {}),
            };

        Object.entries(tempFilter.values || {}).forEach(([key, value]) => {
            if (!this.isEmptyFilterEntry(value)) {
                return;
            }

            delete values[key];
            delete tags[key];
        });

        return {
            filter: baseFilter,
            tags,
            values,
        };
    }

    hasPendingFilterDataChanges(filter = this.state?.filter || {}) {
        const tempFilter = this.tempSettings?.filter;

        if (
            !tempFilter ||
            (
                !Object.prototype.hasOwnProperty.call(tempFilter, "tags") &&
                !Object.prototype.hasOwnProperty.call(tempFilter, "values") &&
                !tempFilter.replaceTags &&
                !tempFilter.replaceValues
            )
        ) {
            return false;
        }

        const draft = this.getMergedFilterDraftData(filter, tempFilter);

        return !this.isSameFilterData(
            this.getCurrentFilterTags(draft.filter),
            draft.tags,
        ) || !this.isSameFilterData(
            this.getCurrentFilterValues(draft.filter),
            draft.values,
        );
    }

    // The filter window waits for its button, so a draft and a live query look
    // alike -- the same confusion the pins had, in the window where waiting is
    // the right behaviour. This counts what a press would change, by field: a
    // field whose tag was switched on or off, or whose value or operator
    // differs. Fields are what a person reads as conditions.
    countPendingFilterConditionChanges(filter = this.state?.filter || {}) {
        const tempFilter = this.tempSettings?.filter;
        if (!tempFilter) return 0;

        const draft = this.getMergedFilterDraftData(filter, tempFilter);
        const current = {
            tags: this.getCurrentFilterTags(draft.filter),
            values: this.getCurrentFilterValues(draft.filter),
        };
        const same = (left, right) => this.isSameFilterData(left, right);
        const keys = new Set([
            ...Object.keys(current.tags || {}),
            ...Object.keys(draft.tags || {}),
            ...Object.keys(current.values || {}),
            ...Object.keys(draft.values || {}),
        ]);

        let changed = 0;
        for (const key of keys) {
            const wasActive = Boolean((current.tags || {})[key]?.active);
            const willBeActive = Boolean((draft.tags || {})[key]?.active);
            if (wasActive !== willBeActive) {
                changed += 1;
                continue;
            }
            if (!same((current.values || {})[key], (draft.values || {})[key])) {
                changed += 1;
            }
        }

        return changed;
    }

    hasUnsavedFilterDataChanges() {
        return Boolean(
            this.state?.filterDataDirty &&
            this.getSelectedFilterTemplate(this.state?.filter || {}),
        );
    }

    saveCurrentFilterTemplateData() {
        const template = this.getSelectedFilterTemplate(
            this.state?.filter || {},
        );

        if (!template) {
            return this;
        }

        return this.set({
            filterDataDirty: false,
        }, () => {
            this.dispatchFilterTemplateSave(
                template.key,
                this.state.filter,
                this.getFilterTemplateByKey(template.key),
            );
        });
    }

    mergeObjectDeep(target = {}, patch = {}) {
        return Object.entries(patch || {}).reduce(
            (result, [key, value]) => {
                const currentValue = result[key];
                const shouldMerge =
                    value &&
                    typeof value === "object" &&
                    !Array.isArray(value) &&
                    currentValue &&
                    typeof currentValue === "object" &&
                    !Array.isArray(currentValue);

                return {
                    ...result,
                    [key]: shouldMerge
                        ? this.mergeObjectDeep(currentValue, value)
                        : value,
                };
            },
            {...(target || {})},
        );
    }

    patchFilterTemplateByKey(key, filter = this.state.filter, props = {}) {
        if (!key || !filter) return {
            filter,
            template: null,
        };

        let patchedTemplate = null;
        const nextFilter = {
            ...filter,
            templates: this.getFilterTemplates(filter).map((template) => {
                if (key !== template.key) {
                    return template;
                }

                const nextPatch =
                    typeof props === "function" ? props(template) : props;
                patchedTemplate = this.mergeObjectDeep(template, nextPatch);
                return patchedTemplate;
            }),
        };

        return {
            filter: nextFilter,
            template: patchedTemplate,
        };
    }

    createFilterTemplatePatchState(filter, options = {}) {
        const nextState = {
            filter,
        };

        if (Object.prototype.hasOwnProperty.call(options, "saveInputValue")) {
            nextState.saveInputValue = options.saveInputValue;
        }

        return nextState;
    }

    // Which fields the filter is showing, in the order the host gave them.
    // These are the chips a person adds under "Поля": without them a saved view
    // came back with the fields it was saved without.
    getActiveFilterFieldKeys(filter = this.state?.filter || {}) {
        const tags = this.getCurrentFilterTags(filter);
        const order = this.getFilterFields().map((field) => field?.key);
        const chosen = Object.keys(tags).filter((key) => tags[key]?.active);
        return [
            ...order.filter((key) => chosen.includes(key)),
            ...chosen.filter((key) => !order.includes(key)),
        ];
    }

    // Puts those fields back. The template keeps every other part of itself;
    // only which fields are shown is replaced, because that is what was saved.
    setActiveFilterFields(keys = [], filter = this.state?.filter || {}) {
        if (!Array.isArray(keys) || keys.some((key) => typeof key !== 'string' || !key.trim())) {
            throw new TypeError('Filter field keys must be nonempty strings');
        }
        const template = this.getSelectedFilterTemplate(filter);
        if (!template?.key) return this;
        const fields = this.getFilterFields();
        const current = this.getCurrentFilterTags(filter);
        const tags = {};
        for (const key of Object.keys(current)) {
            tags[key] = {...current[key], active: false};
        }
        for (const key of keys) {
            const field = fields.find((item) => item?.key === key);
            tags[key] = {
                ...(current[key] || {}),
                active: true,
                label: field?.label ?? current[key]?.label ?? key,
                control: field?.filter?.control || field?.type || field?.dataType
                    || current[key]?.control || 'text',
            };
        }
        const patched = this.patchFilterTemplateByKey(template.key, filter, (item) => ({
            ...item,
            data: {...(item.data || {}), tags},
        }));

        return this.set({filter: patched.filter});
    }

    // The port passes `settings` through as it is, so the chosen fields ride
    // there. Every save path goes through here: the form's own Save is the one
    // the consumer uses, and it used to send a template without them.
    withFilterFieldSettings(detail, filter = this.state?.filter || {}) {
        if (Array.isArray(detail)) {
            return detail.map((item) => this.withFilterFieldSettings(item, filter));
        }
        if (!detail || typeof detail !== 'object') return detail;
        const fields = Array.isArray(detail.data?.tags)
            ? []
            : Object.keys(detail.data?.tags || {}).filter((key) => detail.data.tags[key]?.active);
        return {
            ...detail,
            settings: {
                ...(detail.settings || {}),
                filter_fields: fields.length ? fields : this.getActiveFilterFieldKeys(filter),
            },
        };
    }

    dispatchFilterTemplateSave(key, filter, template = null) {
        const detail = this.withFilterFieldSettings(
            template || this.getFilterTemplateByKey(key, filter),
            filter,
        );
        this.dispatchEvent(
            new CustomEvent("onTemplateSave", {
                bubbles: true,
                composed: true,
                detail,
            }),
        );
        this.dispatchTableEvent('sf-table-template-save', detail);

        return this;
    }

    getChangedFilterTemplates(prevFilter = {}, nextFilter = {}, keys = []) {
        const prevTemplates = new Map(
            this.getFilterTemplates(prevFilter).map((template) => [
                template.key,
                template,
            ]),
        );

        return this.getFilterTemplates(nextFilter).filter((template) => {
            const prevTemplate = prevTemplates.get(template.key);

            if (!prevTemplate) {
                return true;
            }

            return keys.some((key) => !Object.is(prevTemplate[key], template[key]));
        });
    }

    dispatchFilterTemplatesSave(templates = []) {
        const filteredTemplates = templates.filter((template) => template?.key);

        if (!filteredTemplates.length) {
            return this;
        }

        const detail = this.withFilterFieldSettings(
            filteredTemplates.length === 1 ? filteredTemplates[0] : filteredTemplates,
        );
        this.dispatchEvent(
            new CustomEvent("onTemplateSave", {
                bubbles: true,
                composed: true,
                detail,
            }),
        );
        this.dispatchTableEvent('sf-table-template-save', detail);

        return this;
    }

    saveFilterTemplatePatch(
        key,
        props = {},
        options = {},
        filter = this.state.filter,
    ) {
        if (!key || !filter) return this;

        const patched = this.patchFilterTemplateByKey(key, filter, props);
        const nextFilter = {
            ...patched.filter,
            ...(options.filterPatch || {}),
        };

        return this.set(
            () => this.createFilterTemplatePatchState(nextFilter, options),
            () => {
                if (options.dispatch !== false) {
                    this.dispatchFilterTemplateSave(
                        key,
                        nextFilter,
                        patched.template,
                    );
                }
            },
        );
    }

    setDefaultFilterTemplate(key) {
        if (!key) return this;


        return this.set((prevState) => {
            const currentFilter = prevState.filter || {};
            const filter = {
                ...currentFilter,
                templates: this.getFilterTemplates(currentFilter).map(
                    (template) => ({
                        ...template,
                        default: template.key === key,
                    }),
                ),
            };


            return {filter};
        });
    }

    patchFilterTemplateData(filter = {}, patch = {}) {
        const filterRest = {...filter};
        delete filterRest.tags;
        delete filterRest.values;

        const templates = this.getFilterTemplates(filterRest);
        const selectedTemplate = this.getSelectedFilterTemplate({
            ...filterRest,
            templates,
        });

        if (!selectedTemplate) {
            return {
                ...filterRest,
                templates,
            };
        }
        const nextValues = Object.prototype.hasOwnProperty.call(patch, "values")
            ? patch.values || {}
            : selectedTemplate.data?.values || {};
        const nextTags = Object.prototype.hasOwnProperty.call(patch, "tags")
            ? patch.tags || {}
            : selectedTemplate.data?.tags || {};
        const nextTemplates = templates.map((template) =>
            template.key === selectedTemplate.key
                ? {
                    ...template,
                    data: {
                        ...(template.data || {}),
                        ...(patch.data || {}),
                        values: nextValues,
                        tags: nextTags,
                    },
                }
                : template,
        );

        return {
            ...filterRest,
            selectedTemplateKey: selectedTemplate.key,
            templates: nextTemplates,
        };
    }

    getFilterPayload(filter = this.state?.filter || {}) {
        const selectedTemplate = this.getSelectedFilterTemplate(filter);

        return {
            ...(selectedTemplate?.data || {}),
            templateKey: selectedTemplate?.key || "",
            templateLabel: selectedTemplate?.label || "",
            values: this.getCurrentFilterValues(filter),
            tags: this.getCurrentFilterTags(filter),
        };
    }

    selectFilterTemplate(key) {
        if (!key || this.state?.filter?.selectedTemplateKey === key) {
            return this;
        }

        this.set((prevState) => {
            const currentFilter = prevState.filter || {};
            const nextFilter = {
                ...currentFilter,
                selectedTemplateKey: key,
                templates: (prevState.filter?.templates || []).map(
                    (template) => ({
                        ...template,
                        selected: template.key === key,
                    }),
                ),
            };
            const selectedTemplate =
                this.getFilterTemplateByKey(key, nextFilter) ||
                nextFilter.templates[0] ||
                null;
            return {
                filter: nextFilter,
                saveInputValue: selectedTemplate?.label || "",
            };
        });

        return this;
    }

    bindFilterTemplateDrag() {
        this.clearFilterTemplateDragBindings();
        const contextMenu = this.refs?.contextMenu?.value;
        const container = contextMenu?.querySelector?.(
            "[data-filter-template-list]",
        );

        if (!container) return;

        container
            .querySelectorAll("[data-filter-template-key]")
            .forEach((item) => {
                const handle = item.querySelector("[data-move]");

                const cleanup = bindSortableDrag({
                    handle,
                    item,
                    container,
                    boundAttr: "data-sf-table-template-drag-bound",
                    getSortableItems: (currentContainer, currentItem) =>
                        this.getFilterTemplateSortableItems(
                            currentContainer,
                            currentItem?.dataset?.pinned === "1",
                        ),
                    getAfterElement: (currentContainer, y) =>
                        this.getFilterTemplateDragAfterElement(
                            currentContainer,
                            y,
                            item.dataset.pinned === "1",
                        ),
                    insertPlaceholder: (
                        currentContainer,
                        placeholder,
                        afterElement,
                    ) => {
                        this.insertFilterTemplatePlaceholder(
                            currentContainer,
                            placeholder,
                            afterElement,
                            item.dataset.pinned === "1",
                        );
                    },
                    onDrop: () => {
                        this.syncFilterTemplateOrderFromContainer(container);
                    },
                });

                if (cleanup) {
                    this._filterTemplateDragCleanups.push(cleanup);
                }
            });
    }

    // The order of the columns, by the handle on each row of the settings list.
    // The same binding the filter templates use, grouped the same way: a pinned
    // column moves among pinned columns and a loose one among loose ones, so a
    // drag cannot silently unpin a column.
    bindColumnSettingsDrag() {
        this.clearColumnSettingsDragBindings();
        const contextMenu = this.refs?.contextMenu?.value;
        const container = contextMenu?.querySelector?.('[data-column-settings-list]');

        if (!container) return;

        container.querySelectorAll('[data-column-key]').forEach((item) => {
            const handle = item.querySelector('[data-move]');
            const pinnedGroup = item.dataset.pinned === '1';
            const items = (currentContainer) => Array.from(currentContainer?.children || []).filter(
                (candidate) => candidate instanceof HTMLElement
                    && candidate.dataset.columnKey
                    && candidate.dataset.pinned === (pinnedGroup ? '1' : '0')
                    && !candidate.classList.contains('is-dragging')
                    && !candidate.classList.contains('drag-placeholder'),
            );

            const cleanup = bindSortableDrag({
                handle,
                item,
                container,
                boundAttr: 'data-sf-table-column-drag-bound',
                getSortableItems: (currentContainer) => items(currentContainer),
                getAfterElement: (currentContainer, y) =>
                    getVerticalDragAfterElement(currentContainer, y, {
                        accept: (candidate) => candidate instanceof HTMLElement
                            && Boolean(candidate.dataset.columnKey)
                            && candidate.dataset.pinned === (pinnedGroup ? '1' : '0'),
                    }),
                insertPlaceholder: (currentContainer, placeholder, afterElement) => {
                    const group = items(currentContainer);
                    if (afterElement) currentContainer.insertBefore(placeholder, afterElement);
                    else if (group.length) group[group.length - 1].after(placeholder);
                    else currentContainer.append(placeholder);
                },
                onDrop: () => this.syncColumnOrderFromContainer(container),
            });

            if (cleanup) this._columnSettingsDragCleanups.push(cleanup);
        });
    }

    clearColumnSettingsDragBindings() {
        this._columnSettingsDragCleanups.forEach((cleanup) => cleanup());
        this._columnSettingsDragCleanups = [];
    }

    // What the list now reads, written back as `order` in the pending settings:
    // ten apart, so a later move has room between two columns without renumbering
    // the rest.
    syncColumnOrderFromContainer(container) {
        const rows = Array.from(container?.children || []).filter(
            (item) => item instanceof HTMLElement
                && item.dataset.columnKey
                && !item.classList.contains('drag-placeholder'),
        );
        rows.forEach((item, index) => {
            this.patchTempColumnSettings(item.dataset.columnKey, {order: (index + 1) * 10});
        });
        this.requestComponentUpdate('column-order');
        // The consumer's owner read the drag as applying at once, because the
        // list redraws from the draft. It did not: a click outside threw the new
        // order away with everything else. It does now, so the two controls that
        // look immediate are immediate.
        this.commitColumnSettings('column-order');

        return this;
    }

    clearFilterTemplateDragBindings() {
        this._filterTemplateDragCleanups.forEach((cleanup) => cleanup());
        this._filterTemplateDragCleanups = [];
    }

    getFilterTemplateSortableItems(container, pinned = false) {
        return Array.from(container?.children || []).filter(
            (item) =>
                item instanceof HTMLElement &&
                item.dataset.filterTemplateKey &&
                item.dataset.pinned === (pinned ? "1" : "0") &&
                !item.classList.contains("hidden") &&
                !item.classList.contains("is-dragging") &&
                !item.classList.contains("drag-placeholder"),
        );
    }

    getFilterTemplateDragAfterElement(
        container,
        y,
        pinned = false,
        itemsLayout = null,
    ) {
        return getVerticalDragAfterElement(container, y, {
            itemsLayout: Array.isArray(itemsLayout)
                ? itemsLayout.filter(
                    (layout) =>
                        layout.item?.dataset?.filterTemplateKey &&
                        layout.item?.dataset?.pinned ===
                        (pinned ? "1" : "0") &&
                        !layout.item.classList.contains("hidden"),
                )
                : null,
            accept: (item) =>
                item.dataset.filterTemplateKey &&
                item.dataset.pinned === (pinned ? "1" : "0") &&
                !item.classList.contains("hidden"),
        });
    }

    insertFilterTemplatePlaceholder(
        container,
        placeholder,
        afterElement,
        pinned = false,
    ) {
        if (afterElement) {
            container.insertBefore(placeholder, afterElement);
            return;
        }

        const group = pinned ? "1" : "0";
        const groupItems = Array.from(container?.children || []).filter(
            (item) =>
                item instanceof HTMLElement &&
                item.dataset.filterTemplateKey &&
                item.dataset.pinned === group &&
                !item.classList.contains("is-dragging") &&
                !item.classList.contains("drag-placeholder"),
        );
        const lastGroupItem = groupItems.at(-1);

        if (lastGroupItem?.nextSibling) {
            container.insertBefore(placeholder, lastGroupItem.nextSibling);
            return;
        }

        if (pinned) {
            const firstUnpinnedItem = Array.from(
                container?.children || [],
            ).find(
                (item) =>
                    item instanceof HTMLElement &&
                    item.dataset.filterTemplateKey &&
                    item.dataset.pinned === "0" &&
                    !item.classList.contains("is-dragging") &&
                    !item.classList.contains("drag-placeholder"),
            );

            if (firstUnpinnedItem) {
                container.insertBefore(placeholder, firstUnpinnedItem);
                return;
            }
        }

        container.appendChild(placeholder);
    }

    syncFilterTemplateOrderFromContainer(container) {
        const orderedByGroup = {
            pinned: new Map(),
            normal: new Map(),
        };

        Array.from(container?.children || [])
            .filter(
                (item) =>
                    item instanceof HTMLElement &&
                    item.dataset.filterTemplateKey &&
                    !item.classList.contains("drag-placeholder"),
            )
            .forEach((item) => {
                const group =
                    item.dataset.pinned === "1" ? "pinned" : "normal";
                orderedByGroup[group].set(
                    item.dataset.filterTemplateKey,
                    orderedByGroup[group].size,
                );
            });

        const currentFilter = this.getPendingFilterState();
        const templates = this.getFilterTemplates(currentFilter).map(
            (template, sourceIndex) => {
                const group = template.pinned ? "pinned" : "normal";
                const nextOrder = orderedByGroup[group].get(template.key);

                if (typeof nextOrder !== "number") {
                    return template;
                }

                return {
                    ...template,
                    order: nextOrder,
                    sourceIndex,
                };
            },
        );

        this.tempSettings = {
            ...(this.tempSettings || {}),
            filter: {
                ...(this.tempSettings?.filter || {}),
                selectedTemplateKey: currentFilter.selectedTemplateKey,
                templates: templates.sort((left, right) => {
                    if (Boolean(left.pinned) !== Boolean(right.pinned)) {
                        return left.pinned ? -1 : 1;
                    }

                    if (left.order !== right.order) {
                        return left.order - right.order;
                    }

                    return left.sourceIndex - right.sourceIndex;
                }),
            },
        };
        this.requestComponentUpdate("temp-filter-template-order");
    }


    // Whether there is room for every data column to be readable.
    //
    // A floor cannot be a width: write one on a column and it becomes the
    // width, so a cell that needs more wraps and a typed row grows -- measured
    // at 81px against 57 where the typed-cells probe watches it. And a minimum
    // on a cell is ignored in a fixed layout. So the floor is applied only when
    // it is actually needed: while there is room the columns share it as they
    // always have, and when there is not they stop at the floor and the box
    // scrolls, which is what the consumer asked for (Larena, 2026-10-06).
    get columnsAreCramped() {
        // The box that would do the scrolling is the one to measure. The
        // responsive observer's width is what it last saw, and in an embedded
        // list it had seen nothing at all -- it reported 0 while the columns
        // were 77px wide and wrapping their headings over four lines (owner,
        // 2026-10-09).
        const width = this.refs.scroll?.value?.clientWidth || this._responsiveWidth;
        if (!width) return false;
        const columns = this.getColumns?.() ?? [];
        const floored = columns.filter((column) => this.columnTakesFloor(column));
        if (!floored.length) return false;
        const host = this.querySelector(".sf-table") ?? this;
        const raw = globalThis.getComputedStyle?.(host)
            ?.getPropertyValue("--sf-table--column-min-inline-size")?.trim();
        if (!raw) return false;
        const root = Number.parseFloat(globalThis.getComputedStyle?.(document.documentElement)?.fontSize) || 16;
        const floor = raw.endsWith("rem") ? Number.parseFloat(raw) * root : Number.parseFloat(raw);
        if (!Number.isFinite(floor) || floor <= 0) return false;
        // What the columns that are not floored already take, measured rather
        // than assumed: the select box, the gear, the actions.
        // The actions column counts at its declared width, never more: measured,
        // it had already swallowed the room the data columns were denied, and
        // the measurement made the denial permanent.
        const taken = [...this.querySelectorAll("thead th")]
            .filter((cell) => !floored.some((column) => column.key === cell.dataset.key))
            .reduce((total, cell) => total + (cell.dataset.key === "actions"
                ? Math.min(cell.getBoundingClientRect().width, floor)
                : cell.getBoundingClientRect().width), 0);
        return width - taken < floored.length * floor;
    }

    // The actions column as wide as its buttons: each a size-1 icon button, the
    // same measure the settings column is given, with the cell's padding once.
    // Whether the records are a compact list rather than a table: chosen by the
    // width of the box the table sits in, which is what a list beside an open
    // panel is narrow in, not the window.
    get isList() {
        const layout = this.getProp?.("layout") ?? "table";
        if (layout === "list") return true;
        if (layout === "table") return false;
        const below = Number(this.getProp?.("listBelow")) || 600;
        return this._responsiveWidth > 0 && this._responsiveWidth < below;
    }

    get actionsColumnWidth() {
        const inline = (this.ACTIONS_SETTINGS_ROW ?? []).filter((action) => action?.inline !== false).length;
        if (!inline) return "";
        return `calc(${inline} * (var(--sf-text-height-1) + var(--sf-ui-1--space-y-tightness-default) * 2)`
            + ` + ${inline - 1} * var(--sf-space-1\\/3) + var(--sf-b6))`;
    }

    // Each data column as wide as its content wants on one line -- the heading
    // and the values shown -- up to a cap, read from the laid-out table: for a
    // moment the table is laid out by its content at its widest (auto layout,
    // max-content), the headings are measured, and the layout goes back. The
    // columns used to share the room equally and stop at a floor, so a long
    // value wrapped in a column a short one did not need (owner, 2026-10-13).
    // A width the host or the person set wins; what does not fit scrolls.
    measureContentWidths() {
        const table = this.refs.table?.value;
        if (!(table instanceof HTMLTableElement) || !table.isConnected) return false;
        if (this._columnResizeState || this._columnDragState?.started) return false;
        // The skeleton and an empty or failed list say nothing about the content.
        if (table.querySelector('tbody[data-state]') || !table.querySelector('tbody tr td')) return false;
        const measured = new Map();
        table.classList.add('sf-table-measuring');
        try {
            for (const cell of table.querySelectorAll('thead th[data-key]')) {
                const key = cell.dataset.key;
                if (SYSTEM_COLUMN_KEYS.has(key)) continue;
                measured.set(key, Math.ceil(cell.getBoundingClientRect().width));
            }
        } finally {
            table.classList.remove('sf-table-measuring');
        }
        const previous = this._contentWidths ?? new Map();
        const changed = measured.size !== previous.size
            || [...measured].some(([key, width]) => Math.abs((previous.get(key) ?? 0) - width) > 2);
        if (changed) this._contentWidths = measured;
        return changed;
    }

    contentWidth(column) {
        const width = this._contentWidths?.get(column?.key);
        return width ? `min(${width}px, var(--sf-table--column-max-inline-size))` : '';
    }

    columnTakesFloor(column) {
        if (!column?.key || SYSTEM_COLUMN_KEYS.has(column.key)) return false;
        return !(column.colWidth ?? column.width ?? column.size ?? column.min_width ?? column.minWidth);
    }

    getSystemColumns() {
        const columns = [];
        const props = this.getPropsContext();
        if (props.selectable) {
            columns.push(this.getSelectColumn());
        }
        if (props.settings) {
            columns.push(this.TABLE_SETTINGS_COLUMN);
        }
        if (props.actions && props.rowActions && this.state.actionActive) {
            columns.push(this.TABLE_ACTIONS_COLUMN);
        }

        return columns;
    }

    setTemplates(data) {
        const nextTemplates = Object.values(data || {}).map((template, index) =>
            this.normalizeFilterTemplate(template, index),
        );

        return this.set((prevState) => {
            const currentFilter = prevState.filter || {};
            const templatesByKey = new Map();

            this.getFilterTemplates(currentFilter).forEach((template) => {
                templatesByKey.set(template.key, template);
            });

            nextTemplates.forEach((template) => {
                templatesByKey.set(template.key, {
                    ...(templatesByKey.get(template.key) || {}),
                    ...template,
                });
            });

            const templates = Array.from(templatesByKey.values());
            const hasCurrentSelectedTemplateKey =
                Object.prototype.hasOwnProperty.call(
                    currentFilter,
                    "selectedTemplateKey",
                );
            const defaultTemplate = templates.find(
                (template) => template.default === true,
            );
            const incomingSelectedKey =
                nextTemplates.find((template) => template?.selected)?.key;
            const fallbackSelectedKey =
                defaultTemplate?.key || templates[0]?.key || "";
            const selectedTemplateKey = this._filterTemplatesLoaded
                ? incomingSelectedKey ??
                (hasCurrentSelectedTemplateKey
                    ? currentFilter.selectedTemplateKey
                    : fallbackSelectedKey)
                : incomingSelectedKey ?? fallbackSelectedKey;

            const nextFilter = {
                ...currentFilter,
                selectedTemplateKey,
                templates: templates.map((template) => ({
                    ...template,
                    selected: template.key === selectedTemplateKey,
                })),
            };

            const selectedTemplate =
                selectedTemplateKey === ""
                    ? null
                    : this.getFilterTemplateByKey(selectedTemplateKey, {
                        ...nextFilter,
                        templates,
                    }) ||
                    templates[0] ||
                    null;

            this._filterTemplatesLoaded = true;

            return {
                filter: nextFilter,
                saveInputValue: selectedTemplate?.label || "",
            };
        });
    }

    // The identity of this instance: the author's id when there is one, its own
    // counter otherwise. Never the component name, so a second table on the page
    // cannot inherit the first one's ids or preferences.
    getInstanceId() {
        return this.id || this._instanceId;
    }

    getColumnResizerId(columnKey) {
        return `${this.getInstanceId()}--column-resizer--${columnKey}`;
    }

    getTableSettingsKey() {
        return (
            this.tableSettingsKey ||
            this.getAttribute?.("table-settings-key") ||
            this.id ||
            this.getInstanceId()
        );
    }

    normalizeTableSettings(data = {}, tableSettingsKey = this.getTableSettingsKey()) {
        const settingsMap =
            data?.tableSettings &&
            typeof data.tableSettings === "object" &&
            !Array.isArray(data.tableSettings) &&
            !data.tableSettings.columnSettings
                ? data.tableSettings
                : null;
        const source =
            data?.item ||
            settingsMap?.[tableSettingsKey] ||
            data?.tableSettings ||
            data?.settings ||
            data ||
            {};
        const columnSettings =
            source?.columnSettings ||
            data?.columnSettings ||
            {};

        return {
            ...source,
            columnSettings:
                columnSettings &&
                typeof columnSettings === "object" &&
                !Array.isArray(columnSettings)
                    ? columnSettings
                    : {},
        };
    }

    setTableSettings(data = {}, reason = "table-settings-load", tableSettingsKey = this.getTableSettingsKey()) {
        const settings = this.normalizeTableSettings(data, tableSettingsKey);
        return this.setColumnSettings(settings.columnSettings, reason, false);
    }

    getContextMenuLabel(data = {}) {
        switch (data.menu || data.type) {
            case 'filter-favorites':
                return this.text('contextSaveTemplateLabel');
            case 'filter-settings':
                return this.text('contextFilterSettingsLabel');
            case 'filter-template-actions':
                return `${this.text('contextTemplateActionsLabel')}: ${data.template?.label || data.template?.key || ''}`.trim();
            case 'tag-settings': {
                const fieldKey = data.fieldKey || '';
                const field = this.getFilterField(fieldKey);
                const label = field?.label || this.getCurrentFilterTags()?.[fieldKey]?.label || fieldKey;
                return `${this.text('contextFieldFilterLabel')}: ${label || this.text('fieldWordText')}`;
            }
            case 'row-settings':
                return this.text('contextRowActionsLabel');
            case 'create':
                return this.text('contextCreateOptionsLabel');
            case 'list-sort':
                return this.text('sortLabel');
            case 'toolbar-actions':
                return this.text('contextViewActionsLabel');
            case 'table-settings':
            default:
                return this.text('contextTableSettingsLabel');
        }
    }

    renderFilterFavoritesContextMenu(data) {
        const {
            x,
            y,
            position,
        } = data;
        const template = this.getSelectedFilterTemplate();
        return html`
            <sf-context-menu
                    :key=${x + y + this._renderToken}
                    aria-label=${this.getContextMenuLabel({...data, menu: 'filter-favorites'})}
                    style="
            position: fixed;
            left: ${x}px;
            inset-block-start: calc(${y}px + calc(var(--sf-focus-outline-width) * 2));
            z-index: var(--sf-table-context-menu--z-index, var(--sf-z-index-9));
          "
                    root-class="sf-table-context-menu ${this.size === "1/2" ? "sf-table-context-menu--dense " : ""}flex flex-col"
                    data-context-main-menu
                    position="${position || ""}"
            >
                <div class="flex flex-col flex-1 gap-2" slot="content">
                    <div class="sf-table-context-top"><span class="sf-text-2 bold">Сохранение шаблона</span></div>
                    <div class="sf-table-context-main">
                        <form ${ref(this.refs.templateForm)} id="save_template" class="flex flex-col gap-1/2">
                            <sf-input
                                    size=${this.size}
                                    appearance="filled"
                                    placeholder="${this.text('templateNamePlaceholder')}"
                                    value="${this.state.saveInputValue}"
                                    name="template_name"
                                    @change=${(e) => {
                                        this.set({
                                            saveInputValue: e.target.value
                                        })
                                    }}
                                    @input=${(e) => {
                                        this.set({
                                            saveInputValue: e.target.value
                                        })
                                    }}
                            ></sf-input>
                            <div class="flex flex-col gap-1/2">
                                <sf-checkbox size=${this.size}
                                        name="template_default"
                                        position="end"
                                        root-class="content-main-between"
                                        value="1"
                                        ?checked=${template.data.template_default}
                                        label="${this.text('makeDefaultLabel')}"
                                ></sf-checkbox>
                                <sf-checkbox size=${this.size}
                                        name="template_save_for_all"
                                        position="end"
                                        root-class="content-main-between"
                                        value="1"
                                        ?checked=${template.data.template_save_for_all}
                                        label="${this.text('saveForEveryoneLabel')}"
                                ></sf-checkbox>
                        </form>
                    </div>
                </div>
                <div class="sf-table-context-bottom flex gap-1/3">
                    <sf-button size=${this.size} @click="${() => {
                        this.tempSettings = null;
                        this.requestComponentUpdate('table')
                    }
                    }" appearance="outline" scheme="on-surface" root-class="flex-1"
                               text="${this.text('resetText')}"></sf-button>
                    <sf-button size=${this.size}
                            ?disabled=${this.state.saveInputValue.length === 0}
                            @click="${() => {
                                this.saveFilterTemplate();
                            }}" root-class="flex-1" text="${this.text('applyText')}"></sf-button>
                </div>
                </div>
            </sf-context-menu>
        `;
    }

    saveFilterTemplate() {
        const form = this.refs.templateForm.value;
        if (!form) return false;
        const formData = Object.fromEntries(new FormData(form));
        const {templates} = this.state.filter;
        const prevTemplate = templates.filter((template) => {
            return template.label === formData.template_name;
        })?.[0]
        const values = this.getCurrentFilterValues();
        const tags = this.getCurrentFilterTags();
        let template = {
            key: prevTemplate ? prevTemplate.key : `filter_template_${templates.length + 1}`,
            label: formData.template_name,
            selected: true,
            data: {
                ...formData,
                values,
                tags
            }
        };

        const detail = this.withFilterFieldSettings(template);
        this.dispatchEvent(new CustomEvent('onTemplateSave', {
            bubbles: true,
            composed: true,
            detail,
        }));
        this.dispatchTableEvent('sf-table-template-save', detail);
        this.tempSettings = null;
        this.closeContextMenu();
    }

    renderRowSettingsContextMenu(data) {
        const {
            x,
            y,
            position,
            transform,
            items = this.getActionRowItems(data.row || {}).map((action) => ({
                component: "sf-button",
                props: {
                    appearance: "link",
                    scheme: "on-surface",
                    text: action.name,
                    "@click": (event) => {
                        const handler = action.component.props["@click"] || action.component.props.onClick;
                        handler?.(event);
                        this.closeContextMenu();
                    },
                },
            })),
        } = data;
        let pos = `translateY(calc(${transform !== undefined ? transform : -50}%))`;
        if (position) {
            switch (position) {
                case "left-bottom":
                case "right-bottom":
                    pos = `translateY(calc(${transform !== undefined ? transform : -100}% + var(--sf-context-menu-tail-corner-offset)))`;
                    break;
            }
        }
        return html`
            <sf-context-menu
                    :key=${x + y}
                    aria-label=${this.getContextMenuLabel({...data, menu: 'row-settings'})}
                    style="
            position: fixed;
            left: calc(${x}px + var(--sf-context-menu-tail-corner-offset));
            inset-block-start: ${y}px;
            transform: ${pos};
            z-index: var(--sf-table-context-menu--z-index, var(--sf-z-index-9));
          "
                    .items=${items}
                    position="${position || "left"}"
            >
            </sf-context-menu>
        `;
    }

    getColumnItems() {
        const dataColumns = this.getTableData().columns || [];
        const originalIndexByKey = new Map(
            dataColumns.map((column, index) => [column?.key, index]),
        );
        const getOrder = (column) => {
            const order = column?.order;

            return order !== null &&
            typeof order !== "undefined" &&
            order !== "" &&
            Number.isFinite(Number(order))
                ? Number(order)
                : originalIndexByKey.get(column?.key) ?? 0;
        };

        return dataColumns
            .map((column) => {
                const settings = this.getColumnSetting(column?.key);
                const nextColumn = {
                    ...column,
                    ...settings,
                };

                if (settings.width) {
                    nextColumn.colWidth = settings.width;
                }

                return nextColumn;
            })
            .sort((left, right) => {
                // The list reads like the table: the pinned group first, then
                // the rest of the visible columns, then the hidden ones. Sorting
                // by order alone put the hidden columns wherever their stored
                // order happened to fall — a consumer opened the settings and
                // found eight columns it had never shown standing above its own,
                // with the pinned one in the middle.
                const group = (column) => {
                    if (this.isColumnPinned(column)) return 0;
                    return this.isColumnVisible(column) ? 1 : 2;
                };
                const leftGroup = group(left);
                const rightGroup = group(right);

                if (leftGroup !== rightGroup) {
                    return leftGroup - rightGroup;
                }

                const leftOrder = getOrder(left);
                const rightOrder = getOrder(right);

                if (leftOrder === rightOrder) {
                    return (
                        (originalIndexByKey.get(left.key) ?? 0) -
                        (originalIndexByKey.get(right.key) ?? 0)
                    );
                }

                return leftOrder - rightOrder;
            });
    }

    // Whether a column is shown. The answer the person is editing right now
    // wins, then what the host has stored, then what the column itself says.
    isColumnVisible(column) {
        if (!column?.key) return false;
        const pending = this.tempSettings?.cols?.[column.key];
        const setting = this.getColumnSetting(column.key) || {};

        return pending?.visible ?? setting.visible ?? column.visible !== false;
    }

    getColumnSettings() {
        const dataSettings = this.getTableData().columnSettings;
        const stateSettings = this.state?.columnSettings;
        const normalizedDataSettings =
            dataSettings && typeof dataSettings === "object" && !Array.isArray(dataSettings)
                ? dataSettings
                : {};
        const normalizedStateSettings =
            stateSettings && typeof stateSettings === "object" && !Array.isArray(stateSettings)
                ? stateSettings
                : {};

        return {
            ...normalizedDataSettings,
            ...normalizedStateSettings,
        };
    }

    getColumnSetting(columnKey) {
        if (!columnKey) return {};

        const settings = this.getColumnSettings()?.[columnKey];

        return settings && typeof settings === "object" && !Array.isArray(settings)
            ? settings
            : {};
    }

    normalizeColumnSetting(settings = {}) {
        const nextSettings = {};

        Object.entries(settings || {}).forEach(([key, value]) => {
            if (typeof value === "undefined") {
                return;
            }

            if (key === "colWidth") {
                nextSettings.width = value;
                return;
            }

            nextSettings[key] = value;
        });

        if (
            typeof nextSettings.order !== "undefined" &&
            nextSettings.order !== null &&
            nextSettings.order !== ""
        ) {
            nextSettings.order = Number(nextSettings.order);
        }

        return nextSettings;
    }

    mergeColumnSettings(currentSettings = {}, patch = {}) {
        const nextSettings = {...(currentSettings || {})};

        Object.entries(patch || {}).forEach(([columnKey, columnPatch]) => {
            if (!columnKey || !columnPatch || typeof columnPatch !== "object") {
                return;
            }

            nextSettings[columnKey] = this.normalizeColumnSetting({
                ...(nextSettings[columnKey] || {}),
                ...columnPatch,
            });
        });

        return nextSettings;
    }

    // The person asked for the default columns back. The component names no
    // defaults of its own — it says the layer should go, and the host answers
    // with whatever it considers default for this view.
    requestColumnSettingsReset() {
        return this.dispatchTableEvent("sf-table-column-settings-reset", Object.freeze({
            target: "personal",
            tableSettingsKey: this.getTableSettingsKey?.() ?? null,
        }));
    }

    emitColumnSettingsChange(reason = "column-settings") {
        const detail = {
            reason,
            columnSettings: this.getColumnSettings(),
            tableSettingsKey: this.getTableSettingsKey()
        };

        this.dispatchEvent(
            new CustomEvent("sf-table:column-settings-change", {
                bubbles: true,
                composed: true,
                detail,
            }),
        );
        this.dispatchTableEvent('sf-table-column-settings-change', detail);
        this.dispatchEvent(
            new CustomEvent("onColumnSettingsChange", {
                bubbles: true,
                composed: true,
                detail,
            }),
        );

        return this;
    }

    setColumnSettings(settings = {}, reason = "column-settings", emit = true) {
        const nextSettings =
            typeof settings === "function"
                ? settings(this.getColumnSettings())
                : settings;
        return this.set(
            {
                columnSettings:
                    nextSettings && typeof nextSettings === "object"
                        ? nextSettings
                        : {},
            },
            reason,
            () => emit ? this.emitColumnSettingsChange(reason) : false,
        );
    }

    patchColumnSettings(columnKey, patch = {}, reason = "column-settings") {
        if (!columnKey) return this;

        const currentSetting = this.getColumnSetting(columnKey);
        const nextPatch =
            typeof patch === "function" ? patch(currentSetting) : patch;

        return this.patchColumnSettingsBatch(
            {
                [columnKey]: nextPatch || {},
            },
            reason,
        );
    }

    patchColumnSettingsBatch(patch = {}, reason = "column-settings") {
        const currentSettings = this.getColumnSettings();
        const nextSettings = this.mergeColumnSettings(currentSettings, patch);

        if (JSON.stringify(currentSettings) === JSON.stringify(nextSettings)) {
            return this;
        }

        return this.setColumnSettings(nextSettings, reason);
    }

    getFilterFields() {
        const fields = this.state?.filter?.fields;
        return Array.isArray(fields) ? fields : [];
    }

    getFilterField(fieldKey) {
        if (!fieldKey) return null;

        return this.getFilterFields().find((field) => field?.key === fieldKey) || null;
    }

    setFilterFields(fields = [], reason = "filter-fields") {
        for (const page of this._filterOptionPages.values()) {
            page.controller?.abort();
            clearTimeout(page.searchTimer);
        }
        this._filterOptionPages.clear();
        return this.set(
            (prevState) => ({
                filter: {
                    ...(prevState.filter || {}),
                    fields: Array.isArray(fields) ? fields : [],
                },
            }),
            reason,
        );
    }

    patchTempColumnSettings(columnKey, patch = {}) {
        if (!columnKey) return;

        this.tempSettings = {
            ...(this.tempSettings || {}),
            cols: {
                ...(this.tempSettings?.cols || {}),
                [columnKey]: {
                    ...(this.tempSettings?.cols?.[columnKey] || {}),
                    ...(typeof patch === "function" ? patch() : patch),
                },
            },
        };
    }

    patchTempFilterTagSettings(columnKey, patch = {}) {
        if (!columnKey) return;

        this.tempSettings = {
            ...(this.tempSettings || {}),
            filter: {
                ...(this.tempSettings?.filter || {}),
                tags: {
                    ...(this.tempSettings?.filter?.tags || {}),
                    [columnKey]: {
                        ...(this.tempSettings?.filter?.tags?.[columnKey] || {}),
                        ...(typeof patch === "function" ? patch() : patch),
                    },
                },
            },
        };
        // The same defect the pins had: a patch is a plain property, so nothing
        // redrew and anything reading the draft kept the state it was drawn
        // with -- here the button that says how many conditions are pending.
        // Switching a condition on is a checkbox, so a redraw costs nothing a
        // person can feel.
        this.requestComponentUpdate('filter-tag');
    }

    patchTempFilterValue(fieldKey, patch = {}) {
        if (!fieldKey) return;

        const currentValues = this.getCurrentFilterValues(
            this.getPendingFilterState(),
        );
        const currentValue =
            this.tempSettings?.filter?.values?.[fieldKey] ||
            currentValues[fieldKey] ||
            {};
        const nextPatch =
            typeof patch === "function" ? patch(currentValue) : patch;
        const nextValue = {
            ...currentValue,
            ...(nextPatch || {}),
        };

        const isSameValue = (current, next) => {
            if (!Array.isArray(current) || !Array.isArray(next)) {
                return Object.is(current, next);
            }

            if (current.length !== next.length) {
                return false;
            }

            return current.every((item, index) => Object.is(item, next[index]));
        };
        const changed = Object.keys(nextValue).some(
            (key) => !isSameValue(currentValue[key], nextValue[key]),
        );

        if (!changed) {
            return;
        }

        this.tempSettings = {
            ...(this.tempSettings || {}),
            filter: {
                ...(this.tempSettings?.filter || {}),
                values: {
                    ...(this.tempSettings?.filter?.values || {}),
                    [fieldKey]: nextValue,
                },
            },
        };
        this.requestComponentUpdate('temp-filter-value');
    }

    isEmptyFilterValue(value) {
        if (value === null || typeof value === "undefined") {
            return true;
        }

        if (Array.isArray(value)) {
            return value.every((item) => this.isEmptyFilterValue(item));
        }

        if (typeof value === "string") {
            return value.trim() === "";
        }

        if (value && typeof value === "object") {
            return Object.values(value).every((item) => this.isEmptyFilterValue(item));
        }

        return false;
    }

    isEmptyFilterEntry(entry) {
        if (!entry || typeof entry !== "object") {
            return this.isEmptyFilterValue(entry);
        }

        if (Object.prototype.hasOwnProperty.call(entry, "values")) {
            return this.isEmptyFilterValue(entry.values);
        }

        if (Object.prototype.hasOwnProperty.call(entry, "value")) {
            return this.isEmptyFilterValue(entry.value);
        }

        return Object.entries(entry)
            .filter(([key]) => !["operator", "control", "type"].includes(key))
            .every(([, value]) => this.isEmptyFilterValue(value));
    }

    mergeFilterState(prevFilter = {}, tempFilter = {}) {
        const {
            filter,
            tags,
            values,
        } = this.getMergedFilterDraftData(prevFilter, tempFilter);

        return this.patchFilterTemplateData(filter, {
            tags,
            values,
        });
    }

    // What the person typed over the list of fields. Kept in state so the list
    // and the field agree after a render; there can be hundreds of fields and
    // scrolling for one is not an answer.
    setColumnSettingsQuery(value = "") {
        return this.set({columnSettingsQuery: String(value ?? "")});
    }

    // Case and ё/е are not differences a person means when searching.
    matchesColumnQuery(label = "", query = "") {
        const normalise = (value) => String(value ?? "").toLowerCase().replace(/ё/gu, "е");
        const needle = normalise(query).trim();
        if (!needle) return true;

        return normalise(label).includes(needle);
    }

    getColumnCheckboxItems(options = {}) {
        const {
            checked,
            onChange,
            refPrefix = "column",
        } = options;

        const {pin = false, handle = false, query = ""} = options;

        return this.getColumnItems().filter(
            (col) => !pin || col.system || this.matchesColumnQuery(col.label, query),
        ).map((col) => {
            const itemRef = this.getItemRef(`${refPrefix}:${col.key}`) || false;
            const isChecked = typeof checked === "function" ? Boolean(checked(col)) : Boolean(checked);
            const box = html`
                <sf-checkbox
                    size=${this.size}
                    label=${col.label}
                    position="end"
                    root-class="content-main-between"
                    ?checked=${isChecked}
                    ${ref(itemRef)}
                    @change=${event => onChange?.(col, event)}
                ></sf-checkbox>
            `;
            if (!pin || col.system) {
                return box;
            }
            // The pin sits beside the visibility box, because both are answers
            // about the same column. The box and the row menu are always pinned
            // and are offered nothing.
            const pinned = this.isColumnPinned(col);

            return html`
                <div class="sf-table-column-option flex items-cross-center gap-1/3"
                     data-column-key=${col.key}
                     data-pinned=${pinned ? "1" : "0"}>
                    ${handle
                        ? html`<sf-icon-button
                                size="1/2"
                                appearance="link"
                                scheme="on-surface"
                                icon="drag_indicator"
                                data-move
                                root-class="sf-table-column-move cursor-grab"
                                aria-label=${`${this.text("columnMoveLabel", "column-move-label")}: ${col.label || col.key}`}
                            ></sf-icon-button>`
                        : nothing}
                    <span class="flex-1 min-w-0">${box}</span>
                    <!-- A pinned column's pin is filled and a loose one's is
                         outlined, the way a pinned filter template already
                         shows itself. Colour alone had to carry the state, and
                         the two schemes are close enough that a person reading
                         a list of twenty pins could not tell which were set. -->
                    <sf-icon-button
                        size="1/2"
                        appearance="link"
                        scheme=${pinned ? "primary" : "on-surface"}
                        ?filled=${pinned}
                        icon="push_pin"
                        title=${pinned ? this.text("unpinColumnLabel", "unpin-column-label") : this.text("pinColumnLabel", "pin-column-label")}
                        aria-label=${pinned ? this.text("unpinColumnLabel", "unpin-column-label") : this.text("pinColumnLabel", "pin-column-label")}
                        aria-pressed=${pinned ? "true" : "false"}
                        @click=${() => this.toggleColumnPinned(col)}
                    ></sf-icon-button>
                </div>
            `;
        });
    }

    // «Сбросить» in the column settings: the state the window opened with,
    // applied at once and announced once -- not the host's defaults, which is
    // what the filter window's own Reset asks for.
    restoreColumnSettingsOnOpen(reason = 'column-settings-restore') {
        const columnSettings = JSON.parse(JSON.stringify(this._columnSettingsOnOpen || {}));
        this.tempSettings = null;
        this.set(
            (prevState) => ({...prevState, columnSettings}),
            reason,
            () => this.emitColumnSettingsChange(reason),
        );

        return this;
    }

    // What «Применить» did, as a method, so a control that applies on the spot
    // does exactly the same thing rather than its own version of it.
    commitColumnSettings(reason = 'table-settings') {
        const tempSettings = this.tempSettings || {};
        const {cols: tempColumns, ...rest} = tempSettings;
        if (!tempColumns) return this;

        const columnSettings = this.mergeColumnSettings(
            this.getColumnSettings(),
            tempColumns,
        );
        this.tempSettings = Object.keys(rest).length ? rest : null;
        this.set(
            (prevState) => ({...prevState, columnSettings}),
            reason,
            () => this.emitColumnSettingsChange(reason),
        );

        return this;
    }

    // Pinning a column puts it at the end of the pinned block, unpinning it at
    // the start of the loose ones — the place a person expects what they just
    // moved to be, rather than wherever its old order number happens to land it.
    toggleColumnPinned(col) {
        if (!col?.key || col.system) return this;
        const next = !this.isColumnPinned(col);
        const columns = this.getColumnItems().filter((column) => column?.key && !column.system);
        const orders = columns.map((column, index) => {
            const pending = this.tempSettings?.cols?.[column.key];
            const setting = this.getColumnSetting(column.key) || {};
            const value = Number(pending?.order ?? setting.order ?? column.order);
            return Number.isFinite(value) ? value : (index + 1) * 10;
        });
        const group = columns
            .map((column, index) => ({column, order: orders[index]}))
            .filter((entry) => entry.column.key !== col.key
                && this.isColumnPinned(entry.column) === next);
        const order = next
            ? Math.max(0, ...group.map((entry) => entry.order)) + 10
            // Never below 0: the host refuses a negative order, and a loose
            // column already at 0 made an unpin send -10 and fail.
            : Math.max(0, Math.min(...group.map((entry) => entry.order), 10) - 10);

        this.patchTempColumnSettings(col.key, {pinned: next, order});
        // The patch is a plain property, so nothing re-rendered and the pin kept
        // the state it was drawn with: every pin read aria-pressed="false", a
        // press looked like it had done nothing, and the only visible effect was
        // the row moving into the pinned group on some later render.
        this.requestComponentUpdate('column-pin');
        // A pin applies on the spot and reaches the host, by the consumer
        // owner's decision of 2026-10-05. Before it, a press wrote a draft that
        // a click outside the window threw away without saying so, and with the
        // window's own buttons below the fold that read as "pins do not work".
        this.commitColumnSettings('column-pin');

        return this;
    }

    getTableColsData() {
        return this.getColumnCheckboxItems({
            pin: true,
            handle: true,
            query: this.state.columnSettingsQuery || "",
            refPrefix: "table-column",
            checked: (col) => this.isColumnVisible(col),
            onChange: (col, event) => {
                this.patchTempColumnSettings(col.key, {
                    visible: Boolean(event.target?.checked),
                });
                // Everything in this window applies at once, by the consumer
                // owner's decision of 2026-10-05, so a click outside loses
                // nothing and no «Применить» is left to press.
                this.commitColumnSettings('column-visibility');
            },
        });
    }

    getFilterTagColsData() {
        const fields = this.getFilterFields().filter(
            (field) => field?.filter?.enabled !== false,
        );
        const items = fields.length ? fields : this.getColumnItems();

        return items.map((field) => {
            const pending = this.tempSettings?.filter?.tags?.[field.key];
            const current = this.getCurrentFilterTags(
                this.getPendingFilterState(),
            )?.[field.key];
            const data = {
                type: "checkbox",
                props: {
                    size: this.size,
                    label: field.label,
                    position: 'end',
                    rootClass: 'content-main-between',
                    checked: pending?.active ?? current?.active ?? false,
                    ':ref': this.getItemRef(`filter-tag-column:${field.key}`) || false,
                    '@change': (event) => {
                        this.patchTempFilterTagSettings(field.key, {
                            active: Boolean(event.target?.checked),
                            label: field.label,
                            control: field.filter?.control || field.type || field.dataType || 'text',
                        });
                    },
                }
            }
            return this.renderSmartElement(data.type, data.props);
        });
    }

    getFilterControlValue(fieldKey) {
        return {
            ...(this.getCurrentFilterValues()?.[fieldKey] || {}),
            ...(this.tempSettings?.filter?.values?.[fieldKey] || {}),
        };
    }

    getFilterOptions(field = {}) {
        const filter = field.filter || {};
        const source = filter.optionsSource || {};

        if (source.type === 'endpoint') {
            const page = this.getEndpointFilterPage(field);
            const selected = new Set(this.getFilterControlValue(field.key).values || []);
            const options = new Map();
            for (const option of [...(page?.selected || []), ...(page?.items || [])]) {
                if (typeof option?.value === 'string' && typeof option?.label === 'string') {
                    if (selected.has(option.value) || (page?.items || []).includes(option)) {
                        options.set(option.value, option);
                    }
                }
            }
            return [...options.values()];
        }

        if (Array.isArray(filter.options)) {
            return filter.options;
        }

        if (Array.isArray(source.items)) {
            return source.items;
        }

        return [];
    }

    getEndpointFilterPage(field = {}) {
        const source = field.filter?.optionsSource;
        if (source?.type !== 'endpoint' || typeof source.url !== 'string' || !source.url.trim() || !field.key) {
            return null;
        }
        let page = this._filterOptionPages.get(field.key);
        if (!page || page.url !== source.url) {
            page?.controller?.abort();
            page = {url: source.url, items: [], selected: [], nextCursor: null, status: 'idle', sequence: 0, searchable: false};
            this._filterOptionPages.set(field.key, page);
            queueMicrotask(() => {
                if (this.isConnected && this._filterOptionPages.get(field.key) === page) {
                    this.loadEndpointFilterPage(field);
                }
            });
        }
        return page;
    }

    async loadEndpointFilterPage(field = {}, cursor = null, search = '') {
        const page = this.getEndpointFilterPage(field);
        if (!page || !this.isConnected) return false;
        let url;
        try {
            url = new URL(page.url, globalThis.location?.href);
            if (url.origin !== globalThis.location?.origin) throw new TypeError('filter_options_cross_origin');
        } catch {
            page.status = 'error';
            this.requestComponentUpdate('filter-options');
            return false;
        }
        page.controller?.abort();
        const controller = new AbortController();
        const sequence = ++page.sequence;
        page.controller = controller;
        page.status = 'loading';
        url.searchParams.set('limit', '50');
        url.searchParams.set('sequence', String(sequence));
        if (cursor) url.searchParams.set('cursor', cursor);
        else url.searchParams.delete('cursor');
        if (search && page.searchable) url.searchParams.set('search', search);
        else url.searchParams.delete('search');
        url.searchParams.delete('selected_values[]');
        for (const value of this.getFilterControlValue(field.key).values || []) {
            if (typeof value === 'string') url.searchParams.append('selected_values[]', value);
        }
        this.requestComponentUpdate('filter-options');
        try {
            const response = await fetch(url, {credentials: 'same-origin', signal: controller.signal, headers: {Accept: 'application/json'}});
            const answer = await response.json();
            if (page.sequence !== sequence || this._filterOptionPages.get(field.key) !== page || !this.isConnected) return false;
            if (!response.ok || answer?.status !== 'ok' || answer.sequence !== sequence
                || !Array.isArray(answer.items) || !Array.isArray(answer.selected)
                || (answer.next_cursor !== null && typeof answer.next_cursor !== 'string')) {
                throw new TypeError('filter_options_answer_invalid');
            }
            const clean = (items) => items.filter((item) => typeof item?.value === 'string' && item.value
                && typeof item?.label === 'string' && item.label).map(({value, label}) => ({value, label}));
            const merged = new Map((cursor ? page.items : []).map((item) => [item.value, item]));
            for (const item of clean(answer.items)) merged.set(item.value, item);
            page.items = [...merged.values()];
            page.selected = clean(answer.selected);
            page.nextCursor = answer.next_cursor;
            page.searchable = answer.searchable === true;
            page.status = 'ready';
            this.requestComponentUpdate('filter-options');
            return true;
        } catch (error) {
            if (page.sequence !== sequence || error?.name === 'AbortError') return false;
            page.status = 'error';
            this.requestComponentUpdate('filter-options');
            return false;
        }
    }

    getFilterOptionSearch(fieldKey) {
        if (!fieldKey) return '';
        return this.tempSettings?.filterSearch?.[fieldKey] || '';
    }

    patchTempFilterOptionSearch(fieldKey, value = '') {
        if (!fieldKey) return;

        this.tempSettings = {
            ...(this.tempSettings || {}),
            filterSearch: {
                ...(this.tempSettings?.filterSearch || {}),
                [fieldKey]: String(value || ''),
            },
        };
        this.requestComponentUpdate('filter-option-search');

        const field = this.getFilterField(fieldKey);
        const page = field && this.getEndpointFilterPage(field);
        if (page?.searchable) {
            clearTimeout(page.searchTimer);
            page.searchTimer = setTimeout(() => this.loadEndpointFilterPage(field, null, String(value || '').trim()), 200);
        }

    }

    filterUpdate() {
        return this.getFilterPayload();
    }

    getFilterOptionSearchKeys(field = {}) {
        const keys = field.filter?.searchKeys || field.filter?.optionSearchKeys;

        if (Array.isArray(keys) && keys.length) {
            return keys;
        }

        return ['label', 'title', 'name', 'text', 'value', 'id'];
    }

    getObjectPathValue(source = {}, path = '') {
        return String(path || '')
            .split('.')
            .filter(Boolean)
            .reduce((value, key) => {
                if (value === null || typeof value === 'undefined') {
                    return '';
                }

                return value[key];
            }, source);
    }

    normalizeFilterSearchValue(value = '') {
        return String(value || '').trim().toLowerCase();
    }

    optionMatchesSearch(option = {}, search = '', keys = []) {
        const query = this.normalizeFilterSearchValue(search);

        if (!query) return true;

        return keys.some((key) => {
            const value = this.getObjectPathValue(option, key);
            return this.normalizeFilterSearchValue(value).includes(query);
        });
    }

    getVisibleFilterOptions(field = {}) {
        const options = this.getFilterOptions(field);
        const search = this.getFilterOptionSearch(field.key);

        if (!search) {
            return options;
        }

        const keys = this.getFilterOptionSearchKeys(field);
        return options.filter((option) =>
            this.optionMatchesSearch(option, search, keys),
        );
    }

    // A cell for a column that names a property type: the Framework's renderer
    // for that type, or nothing, in which case the value renders as itself. A
    // host that passes its own component never reaches here -- the escape hatch
    // is that the host's choice is consulted first, by the template.
    // The set answers with a cell value in the shape a host would pass -- a
    // string, an object with a component, or a list of those -- so the template
    // renders it through the path it already had. The set adds no second way to
    // draw a cell.
    getTypedCellValue(column = {}, value) {
        // The host is passed so `formatFieldValue` is reachable from a cell. It
        // was reachable from the record panel and not from the list -- the
        // panel hands the renderer set its port, the table handed it nothing --
        // so the same date read «25 сентября 2026» in a card and
        // «2026-09-25T07:12:00+03:00» in a row, and a consumer had no way to
        // change the second. The set still refuses to guess a format; this only
        // lets the host say one (admin polygon, 2026-10-10).
        return renderCellField(column, value, this.cellHost ?? null);
    }

    // The filter controls were the Framework's only type-aware rendering, and
    // Larena's request asks for one renderer set shared by the record view and
    // the table's cells. Keeping these here as well would have made two maps
    // for one question, so they moved into src/smart/field-renderers and this
    // asks the set. The host interface is small on purpose: the set renders,
    // the table owns the state.
    renderFilterControl(field = {}) {
        return renderFilterField(field, {
            getFilterControlValue: (key) => this.getFilterControlValue(key),
            patchTempFilterValue: (key, patch) => this.patchTempFilterValue(key, patch),
            getFilterOperatorLabel: (operator) => this.getFilterOperatorLabel(operator),
            getFilterOptionSearch: (key) => this.getFilterOptionSearch(key),
            patchTempFilterOptionSearch: (key, search) => this.patchTempFilterOptionSearch(key, search),
            getVisibleFilterOptions: (item) => this.getVisibleFilterOptions(item),
            renderFilterOption: (item, option) => this.renderFilterOption(item, option),
            getEndpointFilterPage: (item) => this.getEndpointFilterPage(item),
            loadEndpointFilterPage: (item, cursor, search) => this.loadEndpointFilterPage(item, cursor, search),
        });
    }

    renderFilterOption(field = {}, option = {}, selected = new Set()) {
        const value = option.value ?? option.id ?? option.key ?? option.label ?? option.title;
        const label = option.label || option.title || String(value || '');
        const badge = field.filter?.optionRenderer === 'badge' ? optionBadgeProps(option) : null;

        return html`
            <div
                    ${ref(this.getItemRef(option.label))}
                    class="flex items-cross-center gap-1/2 content-main-between">
                <div class="flex items-cross-center gap-1/2 min-w-0">
                    ${badge ? html`
                        <sf-badge
                                appearance="tonal"
                                scheme=${badge.scheme}
                                root-style=${badge.rootStyle || nothing}
                                icon=${option.icon || nothing}
                                text=${label}
                        ></sf-badge>
                    ` : html`
                        ${this.renderFilterOptionVisual(field, option)}
                        <span class="sf-text-1 truncate">${label}</span>
                    `}
                </div>
                <sf-checkbox
                        size=${this.size}
                        position="end"
                        root-class="content-main-between"
                        ?checked=${selected.has(value)}
                        @change=${(event) => {
                            const nextSelected = new Set(selected);
                            if (event.target?.checked) {
                                nextSelected.add(value);
                            } else {
                                nextSelected.delete(value);
                            }
                            this.patchTempFilterValue(field.key, {
                                values: Array.from(nextSelected),
                                operator: field.filter?.defaultOperator || 'in',
                            });
                        }}
                ></sf-checkbox>
            </div>
        `;
    }

    renderFilterOptionVisual(field = {}, option = {}) {
        if (field.filter?.optionRenderer !== 'avatar-checkbox') {
            return '';
        }

        if (option.imageUrl) {
            return html`
                <sf-avatar
                        size=${this.size}
                        image-url="${option.imageUrl}"
                        title="${option.title || option.label || ''}"
                ></sf-avatar>
            `;
        }

        return html`
            <sf-avatar
                    size=${this.size}
                    title="${option.avatarText || option.title || option.label || ''}"
            ></sf-avatar>
        `;
    }

    getFilterOperatorLabel(operator) {
        const keys = {
            eq: 'operatorEq', gt: 'operatorGt', gte: 'operatorGte',
            lt: 'operatorLt', lte: 'operatorLte', between: 'operatorBetween',
            contains: 'operatorContains', startsWith: 'operatorStartsWith',
            any: 'operatorAny', today: 'operatorToday', week: 'operatorWeek',
            month: 'operatorMonth', in: 'operatorIn',
        };

        // An operator nobody has a word for is shown as it is stored, which is
        // better than a blank and says what to add.
        return keys[operator] ? this.text(keys[operator]) : operator;
    }


    renderFilterSettingsContextMenu(data) {
        const {
            x,
            y,
            position,
            columnCount,
            columnsCount,
            transform,
        } = data;
        let pos = `translateX(${transform !== undefined ? transform : 0}%)`;
        const pendingFilter = this.getPendingFilterState();
        // What a press would change, so the button can say it. The button is
        // already a filled primary one, so an extra fill or ring would say
        // nothing: the count is the marked state, and with nothing pending the
        // button reads exactly as it did before. sf-button carries no `filled`
        // property -- that belongs to the icon button -- so setting one would
        // have been an attribute nobody reads.
        const pendingConditions = this.countPendingFilterConditionChanges();
        const selectedTemplateKey = pendingFilter.selectedTemplateKey;
        const sortTemplates = this.getSortedFilterTemplates(pendingFilter).map((col) => {
            let component;
            const isActiveEdit = this.state.filter.rename === col.key;
            if (this.state.filter.rename !== col.key) {
                const data = {
                    type: "radio",
                    props: {
                        size: this.size,
                        label: col.label,
                        name: 'filter_templates',
                        checked: selectedTemplateKey
                            ? selectedTemplateKey === col.key
                            : col.selected || false,
                        '@change': () => {
                            this.selectTempFilterTemplate(col.key);
                        }
                    }
                }
                component = this.renderSmartElement(data.type, data.props);
            } else {
                component = html`
                    <div class="flex items-cross-center gap-1/4 flex-1">
                        <sf-input size=${this.size} ${ref(this.refs.renameInput)} root-class="flex-1"
                                  default-value="${col.label}"></sf-input>
                        <div class="flex items-cross-center flex-none">
                            <sf-icon-button size=${this.size} segment="start" appearance="outline" scheme="on-surface" @click="${() => {
                                this.set((prevState) => ({
                                    filter: {
                                        ...(prevState.filter || {}),
                                        rename: '',
                                    },
                                }));
                            }}"
                                            icon="close"></sf-icon-button>
                            <sf-icon-button size=${this.size} segment="end" icon="check" @click="${() => {
                                const name = this.refs.renameInput.value?.value;
                                this.saveFilterTemplatePatch(col.key, {
                                    label: name,
                                }, {
                                    filterPatch: {
                                        rename: '',
                                    },
                                    saveInputValue: name,
                                });

                            }}"></sf-icon-button>
                        </div>
                    </div>`
            }

            return html`
                <div
                        :key=${col.key}
                        ${ref(this.getItemRef(col.label))}
                        class="sf-settings-context-menu-item flex items-cross-center flex-1 gap-1/3 ${col.deleted ? 'opacity-3' : ''}"
                        data-filter-template-key="${col.key}"
                        data-pinned="${col.pinned ? "1" : "0"}"
                >
                    ${!isActiveEdit ? html`
                        <sf-icon-button size=${this.size}
                                id="${this.getInstanceId()}--template-move--${col.key}"
                                appearance="link"
                                data-move
                                scheme="on-surface"
                                aria-label=${`${this.text("templateMoveLabel")}: ${col.label}`}
                                icon="drag_indicator"
                                root-class="cursor-move"
                        ></sf-icon-button>` : nothing}
                    ${component}
                    ${!isActiveEdit ? html`
                        <div class="flex items-cross-center m-inline-start-auto gap-1/3">
                            <sf-icon-button
                                    appearance="link"
                                    data-keep
                                    size="1/3"
                                    scheme="on-surface"
                                    aria-label=${`${this.text("templatePinLabel")}: ${col.label}`}
                                    ?filled="${col.pinned}"
                                    root-class="${col.pinned ? 'sf-button-pinned' : ''}"
                                    icon="keep"
                                    @click=${() => {
                                        this.patchTempFilterTemplateSettings(col.key, {
                                            pinned: !col.pinned,
                                        });
                                    }}
                            ></sf-icon-button>
                            <sf-icon-button size=${this.size}
                                    appearance="link"
                                    data-settings
                                    data-context-submenu-anchor="${col.key}"
                                    scheme="on-surface"
                                    aria-label=${`${this.text("templateActionsLabel")}: ${col.label}`}
                                    icon="more_vert"
                                    @click=${(event) => {
                                        this.openContextSubmenu(event, {
                                            type: 'filter-template-actions',
                                            template: col,
                                        });
                                    }}
                            ></sf-icon-button>
                        </div>` : nothing}
                </div>`;
        })
        const menuColumnCount =
            columnCount ?? columnsCount ?? this.contextMenuColumns ?? 1;
        const colsData = this.getFilterTagColsData()
        const columnGroups = this.splitItemsByColumns(colsData, menuColumnCount);
        let contextItems = `${this.text('templatesTabText')}|${this.text('fieldsTabText')}`;
        return html`
            <sf-context-menu
                    :key=${x + y + this._renderToken}
                    aria-label=${this.getContextMenuLabel({...data, menu: 'filter-settings'})}
                    style="
            position: fixed;
            left: ${x}px;
            inset-block-start: calc(${y}px + calc(var(--sf-focus-outline-width) * 2));
            transform: ${pos};
            z-index: var(--sf-table-context-menu--z-index, var(--sf-z-index-9));
            --sf-table-context-menu--max-block-size: calc(100dvh - ${y}px - var(--sf-space-4));
          "
                    root-class="sf-table-context-menu ${this.size === "1/2" ? "sf-table-context-menu--dense " : ""}flex flex-col"
                    position="${position || ""}"
            >
                <div class="sf-table-context-body flex flex-col flex-1" slot="content">
                    <sf-tabs size=${this.size} items="${contextItems}" type="underline">
                        <div slot="panel-0" class="flex">
                            <div
                                    class="sf-context-menu-columns flex flex-col gap-1/4 overflow-auto flex-1"
                                    data-filter-template-list
                            >
                                ${sortTemplates}
                            </div>
                        </div>
                        <div slot="panel-1">
                            <div class="sf-context-menu-columns flex flex-1 gap-1 overflow-auto">
                                ${columnGroups.map(
                                        (group) => html`
                                            <div class="flex flex-col gap-1 flex-1">
                                                ${group}
                                            </div>
                                        `,
                                )}
                            </div>
                        </div>

                    </sf-tabs>
                    <div class="sf-table-context-bottom flex gap-1/3">
                        <sf-button size=${this.size} @click="${() => {
                            // Reset means the defaults, not "forget what I just
                            // typed here": the draft goes, and the host is asked
                            // to clear the person's own layer — order, widths,
                            // visibility and pinning — because the defaults are
                            // the host's to decide, not this component's.
                            this.tempSettings = null;
                            this.requestComponentUpdate('table');
                            this.requestColumnSettingsReset();
                        }
                        }" appearance="outline" scheme="on-surface" root-class="flex-1"
                                   text="${this.text('resetText')}"></sf-button>
                        <sf-button size=${this.size} @click="${() => {
                            const tempFilter = this.tempSettings?.filter;

                            if (tempFilter) {
                                let changedTemplates = [];
                                let deletedKeys = [];
                                let selectedKey = null;
                                this.set((prevState) => {
                                    const hasDataChanges =
                                            this.hasPendingFilterDataChanges(
                                                    prevState.filter || {},
                                            );
                                    const filter = this.mergeFilterState(
                                            prevState.filter || {},
                                            tempFilter,
                                    );
                                    const selectedTemplate =
                                            this.getSelectedFilterTemplate(filter);
                                    changedTemplates = tempFilter.templates
                                            ? this.getChangedFilterTemplates(
                                                    prevState.filter || {},
                                                    filter,
                                                    ['order', 'pinned', 'deleted', 'selected', 'default'],
                                            )
                                            : [];
                                    deletedKeys = filter.templates
                                            .filter((template) => template.deleted && template.key)
                                            .map((template) => template.key);
                                    filter.templates = filter.templates.filter(el => !el.deleted)
                                    const previousKey =
                                            this.getSelectedFilterTemplate(prevState.filter || {})?.key || null;
                                    selectedKey = selectedTemplate?.key && selectedTemplate.key !== previousKey
                                            ? selectedTemplate.key
                                            : null;
                                    return {
                                        filter,
                                        filterDataDirty:
                                                prevState.filterDataDirty ||
                                                hasDataChanges,
                                        saveInputValue:
                                                selectedTemplate?.label || "",
                                    };
                                }, () => {
                                    if (changedTemplates.length) {
                                        this.dispatchFilterTemplatesSave(changedTemplates);
                                    }
                                    for (const key of deletedKeys) {
                                        this.dispatchTableEvent('sf-table-template-delete', Object.freeze({key}));
                                    }
                                    if (selectedKey) {
                                        this.dispatchTableEvent('sf-table-template-select',
                                                Object.freeze({key: selectedKey}));
                                    }
                                });
                            }

                            this.tempSettings = null;
                            this.closeContextMenu();
                        }}" root-class="flex-1"
                                   aria-label=${pendingConditions
                                       ? `${this.text("filterApplyText", "filter-apply-text")}: ${pendingConditions}`
                                       : this.text("filterApplyText", "filter-apply-text")}
                                   text=${pendingConditions
                                       ? `${this.text("filterApplyText", "filter-apply-text")} (${pendingConditions})`
                                       : this.text("filterApplyText", "filter-apply-text")}></sf-button>
                    </div>
                </div>
            </sf-context-menu>
        `;
    }

    renderTagsSettingsContextMenu(data) {
        const {
            x,
            y,
            position,
            fieldKey,
        } = data;
        const field = this.getFilterField(fieldKey) || {
            key: fieldKey,
            label: this.getCurrentFilterTags()?.[fieldKey]?.label || fieldKey || '',
            filter: {
                control: this.getCurrentFilterTags()?.[fieldKey]?.control || 'text',
            },
        };
        return html`
            <sf-context-menu
                    :key=${x + y + this._renderToken}
                    aria-label=${this.getContextMenuLabel({...data, menu: 'tag-settings'})}
                    style="
            position: fixed;
            left: ${x}px;
            inset-block-start: calc(${y}px + calc(var(--sf-focus-outline-width) * 2));
            z-index: var(--sf-table-context-menu--z-index, var(--sf-z-index-9));
          "
                    root-class="sf-table-context-menu ${this.size === "1/2" ? "sf-table-context-menu--dense " : ""}flex flex-col"
                    position="${position || ""}"
            >
                <div class="flex flex-col flex-1 ${this.size === "1/2" ? "p-top-1" : "p-top-3"}" slot="content">
                    <div class="sf-table-context-main">
                        <div class="sf-table-tags-search flex flex-col">
                            <div class="flex flex-col ${this.size === "1/2" ? "p-bottom-1" : "p-bottom-3"}">
                                ${this.renderFilterControl(field)}
                            </div>
                        </div>
                    </div>
                    <div class="sf-table-context-bottom flex gap-1/3">
                        <sf-button size=${this.size} @click="${() => {
                            this.tempSettings = null;
                            this.requestComponentUpdate('table')
                        }
                        }" appearance="outline" scheme="on-surface" root-class="flex-1"
                                   text="${this.text('resetText')}"></sf-button>
                        <sf-button size=${this.size} @click="${() => {
                            const tempFilter = this.tempSettings?.filter;

                            if (tempFilter) {
                                this.set((prevState) => {
                                    const hasDataChanges =
                                            this.hasPendingFilterDataChanges(
                                                    prevState.filter || {},
                                            );

                                    return {
                                        filter: this.mergeFilterState(
                                                prevState.filter || {},
                                                tempFilter,
                                        ),
                                        filterDataDirty:
                                                prevState.filterDataDirty ||
                                                hasDataChanges,
                                    };
                                }, () => {
                                    const detail = this.filterUpdate();
                                    this.dispatchEvent(new CustomEvent('onFilterUpdate', {
                                        bubbles: true,
                                        composed: true,
                                        detail,
                                    }));
                                    this.dispatchTableEvent('sf-table-filter-change', detail);
                                });
                            }

                            this.tempSettings = null;
                            this.closeContextMenu();
                        }}" root-class="flex-1" text="${this.text('applyText')}"></sf-button>
                    </div>
                </div>
            </sf-context-menu>
        `;
    }

    removeFilterTag(id = null) {
        if (id) {
            this.tempSettings = null;
            this.set((prevState) => {
                const prevFilter = prevState.filter || {};
                const tags = {...this.getCurrentFilterTags(prevFilter)};
                const values = {...this.getCurrentFilterValues(prevFilter)};

                delete tags[id];
                delete values[id];

                return {
                    filter: this.patchFilterTemplateData(prevFilter, {
                        tags,
                        values,
                    }),
                    filterDataDirty: true,
                };
            }, () => {
                const detail = this.filterUpdate();
                this.dispatchEvent(new CustomEvent('onFilterUpdate', {
                    bubbles: true,
                    composed: true,
                    detail,
                }));
                this.dispatchTableEvent('sf-table-filter-change', detail);
            });
            return this;
        }

        this.tempSettings = null;
        this.set((prevState) => {
            const prevFilter = prevState.filter || {};

            return {
                filter: {
                    ...prevFilter,
                    selectedTemplateKey: "",
                    templates: this.getFilterTemplates(prevFilter).map(
                        (template) => ({
                            ...template,
                            selected: false,
                        }),
                    ),
                },
                saveInputValue: "",
                filterDataDirty: false,
            };

        }, () => {
            const detail = this.filterUpdate();
            this.dispatchEvent(new CustomEvent('onFilterUpdate', {
                bubbles: true,
                composed: true,
                detail,
            }));
            this.dispatchTableEvent('sf-table-filter-change', detail);
        });

        return this;
    }

    renderTableSettingsContextMenu(data) {
        const {
            x,
            y,
            position,
            transform,
            columnCount,
            columnsCount,
        } = data;
        let pos = `translateX(${transform !== undefined ? transform : 0}%)`;
        const menuColumnCount =
            columnCount ?? columnsCount ?? this.contextMenuColumns ?? 1;
        const cols = this.getTableColsData()
        const query = this.state.columnSettingsQuery || "";
        // The settings window is about the columns. Its "Actions" tab was asked
        // to go on 2026-10-02: row actions are offered in the row's own menu and
        // bulk actions in the band under the table, so a third place to find
        // them taught nobody anything.
        return html`
            <sf-context-menu
                    :key=${x + y + this._renderToken}
                    aria-label=${this.getContextMenuLabel({...data, menu: 'table-settings'})}
                    style="
            position: fixed;
            left: ${x}px;
            inset-block-start: calc(${y}px + calc(var(--sf-focus-outline-width) * 2));
            transform: ${pos};
            z-index: var(--sf-table-context-menu--z-index, var(--sf-z-index-9));
            --sf-table-context-menu--max-block-size: calc(100dvh - ${y}px - var(--sf-space-4));
          "
                    root-class="sf-table-context-menu ${this.size === "1/2" ? "sf-table-context-menu--dense " : ""}flex flex-col"
                    position="${position || ""}"
            >
                <div class="sf-table-context-body flex flex-col flex-1" slot="content">
                    <!-- One tab held one panel, so its title said only that the
                         window is about fields, which the window already says.
                         The search is here instead: a site can have hundreds of
                         fields, and scrolling for one is not an answer. -->
                    <label class="sf-table-column-search flex flex-col">
                        <sf-input
                            size=${this.size}
                            appearance="bordered"
                            root-class="sf-table-column-search-input"
                            name="sf_table_column_search"
                            .value=${query}
                            placeholder=${this.getProp('columnSearchPlaceholder')}
                            aria-label=${this.getProp('columnSearchPlaceholder')}
                            @input=${(event) => this.setColumnSettingsQuery(event.target?.value ?? "")}
                        ></sf-input>
                    </label>
                    <div class="sf-context-menu-columns flex flex-col flex-1 gap-1/3 overflow-auto"
                         data-column-settings-list>
                        ${cols.length
                            ? cols
                            : html`<div class="sf-table-column-empty p-1 color-on-surface-variant">
                                ${this.getProp('columnSearchEmptyText')}
                            </div>`}
                    </div>
                    <!-- «Применить» went with the consumer owner's decision of
                         2026-10-05: every control in this window applies as it
                         is pressed, so a button that commits has nothing left
                         to commit. «Сбросить» is the way back -- the settings as
                         they stood when the window was opened, applied at once
                         and announced once. -->
                    <div class="sf-table-context-bottom flex gap-1/3">
                        <sf-button size=${this.size} @click="${() => this.restoreColumnSettingsOnOpen()}"
                                   appearance="outline" scheme="on-surface" root-class="flex-1"
                                   text="${this.text('resetText')}"></sf-button>
                    </div>
                </div>
            </sf-context-menu>
        `;
    }

    renderMainContextMenu(data) {
        const menu = data.menu || data.menuType || data.contextMenu;

        switch (menu) {
            case 'row-settings':
                return this.renderRowSettingsContextMenu(data);
            case 'create':
                return this.renderCreateContextMenu(data);
            case 'list-sort':
                return this.renderListSortContextMenu(data);
            case 'toolbar-actions':
                return this.renderToolbarActionsContextMenu(data);
            case 'filter-favorites':
                return this.renderFilterFavoritesContextMenu(data);
            case 'filter-settings':
                return this.renderFilterSettingsContextMenu(data);
            case 'table-settings':
                return this.renderTableSettingsContextMenu(data);
            case 'tag-settings':
                return this.renderTagsSettingsContextMenu(data);
            default:
                return data.type === 'left'
                    ? this.renderRowSettingsContextMenu(data)
                    : this.renderTableSettingsContextMenu(data);
        }
    }

    renderTemplateActionsContextSubmenu(data) {
        const {
            x,
            y,
            position,
            transform,
            template = {},
        } = data;
        let pos = `translateY(${transform !== undefined ? transform : 0}%)`;
        const temp = this.getFilterTemplateByKey(template.key, this.tempSettings?.filter || null);
        let isDeleted = (temp || template).deleted;
        const isDefaultTemplate = (temp || template).default === true;
        return html`
            <sf-context-menu
                    :key=${`${template.key || ''}-${x}-${y}`}
                    aria-label=${this.getContextMenuLabel({...data, type: 'filter-template-actions'})}
                    style="
          position: fixed;
        left: calc(${x}px + var(--sf-context-menu-tail-corner-offset));
            inset-block-start: ${y}px;
          z-index: var(--sf-table-context-submenu--z-index, var(--sf-z-index-9));
          transform: ${pos}
        "
                    data-context-submenu
                    root-class="sf-table-context-submenu flex flex-col"
                    position="${position || "right"}"
            >
                <div class="flex flex-col flex-1" slot="content">
                    <sf-button size=${this.size} appearance="link" scheme="on-surface" icon-start="edit" text="${this.text('renameText')}" @click="${() => {
                        this.set((prevState) => ({
                            filter: {
                                ...(prevState.filter || {}),
                                rename: template.key,
                            },
                            contextMenu: {
                                ...(prevState.contextMenu || {}),
                                submenu: null,
                            },
                        }));
                    }}"></sf-button>
                    <sf-button size=${this.size} appearance="link" scheme="on-surface" icon-start="${isDefaultTemplate ? 'remove' : 'check'}"
                               text="${isDefaultTemplate ? this.text('defaultTemplateText') : this.text('makeDefaultLabel')}" @click="${() => {
                        this.changeTempFilterTemplateSettings(template.key, {
                            default: !template.default,
                        });
                        this.closeContextSubmenu();
                    }}"></sf-button>
                    <sf-button size=${this.size} appearance="link" scheme="on-surface" icon-start="delete" @click="${() => {
                        this.patchTempFilterTemplateSettings(template.key, {
                            deleted: !isDeleted,
                        });
                    }}" text="${isDeleted ? this.text('restoreText') : this.text('deleteActionText')}"></sf-button>
                </div>
            </sf-context-menu>
        `;
    }

    renderContextSubmenu(data) {
        switch (data?.type) {
            case 'filter-template-actions':
                return this.renderTemplateActionsContextSubmenu(data);
            default:
                return nothing;
        }
    }

    renderContextMenu(data) {
        if (!data?.open) {
            render(null, this.getPortalContainer());
            this.hidePortal();
            return nothing;
        }

        render(
            html`
                <div
                        class="sf-table-context-layer"
                        data-sf-sheet=${this.isList && ["filter-settings", "tag-settings"].includes(data.menu) ? "" : nothing}
                        ${ref(this.refs.contextMenu)}
                        @click=${(event) => this.handleContextLayerClick(event)}
                >
                    ${this.renderMainContextMenu(data)}
                    ${data.submenu?.open ? this.renderContextSubmenu(data.submenu) : nothing}
                    ${this.isList && ["filter-settings", "tag-settings"].includes(data.menu) ? html`
                        <div class="sf-table-sheet-close">
                            <sf-icon-button size=${this.size} appearance="link" scheme="on-surface"
                                            icon="close" aria-label=${this.text('closeText')}
                                            @click=${() => this.closeContextMenu()}></sf-icon-button>
                        </div>` : nothing}
                </div>
            `,
            this.getPortalContainer(),
        );
        this.showPortal();
        this.bindContextEvent();
        this.bindContextViewport();
        requestAnimationFrame(() => {
            this.bindFilterTemplateDrag();
            this.bindColumnSettingsDrag();
            this.clampContextMenusToViewport();
            // The menu lives in the portal, not in this element's own tree.
            this.redrawWhenComponentsUpgrade(this.getPortalContainer());
        });
        return nothing;
    }

    getActionItems() {
        return this.state.actions.map((action) => {
            const data = {
                type: "checkbox",
                props: {
                    size: this.size,
                    label: action.name,
                    position: 'end',
                    rootClass: 'content-main-between',
                    checked: action.visible,
                    "@change": (e) => {
                        let actionActive = false;
                        let arr = this.tempSettings && this.tempSettings.actions ? this.tempSettings.actions : this.state.actions;
                        const items = arr.map((item) => {
                            let visible = item.id === action.id ? e.target.checked : item.visible;
                            if (visible) {
                                actionActive = true;
                            }
                            return {
                                ...item,
                                visible: visible
                            }
                        })
                        this.tempSettings = {
                            ...(this.tempSettings || {}),
                            actionActive,
                            actions: items,
                        };
                    }
                }
            }
            return this.renderSmartElement(data.type, data.props);
        })
    }

    hoverEvents(e) {
        if (this._columnResizeState || this._columnDragState?.started) {
            return;
        }

        const th = e.target.closest('th');

        if (!th) {
            return;
        }
        const wrap = th.querySelector('.sidebar-resizer');
        if (!wrap) {
            return;
        }
        th.classList.add('hover');
        // The line belongs to the handle, not to the header. Shown on any
        // header hover it read as if the column were already being resized, and
        // it crossed the rows whenever the pointer merely passed over a title.
        if (this.getColumnResizeHandle(e.target)) {
            this.showColumnResizeGuide(th);
        } else {
            this.hideColumnResizeGuide();
        }
    }

    outEvent(e) {
        if (this._columnResizeState || this._columnDragState?.started) {
            return;
        }

        const th = e.target.closest('th');

        if (!th) {
            return;
        }
        const wrap = th.querySelector('.sidebar-resizer');
        if (!wrap) {
            return;
        }
        th.classList.remove('hover');
        this.hideColumnResizeGuide();
    }

    /**
     * The line that shows where a column edge will land runs through the rows
     * only, from the bottom of the head to the last row. A head cell cannot draw
     * it: a pseudo-element of a `th` has no way to reach into the body, and the
     * body's height is not known to CSS. So it is one element in the scroll
     * area, placed against the edge being dragged.
     */
    showColumnResizeGuide(th) {
        const guide = this.refs.resizeGuide?.value;
        const scroll = this.refs.scroll?.value;
        const table = this.refs.table?.value;
        if (!guide || !scroll || !th || !table) {
            return;
        }
        const head = table.querySelector('thead');
        const body = table.querySelector('tbody');
        if (!head || !body) {
            this.hideColumnResizeGuide();
            return;
        }
        const area = scroll.getBoundingClientRect();
        const cell = th.getBoundingClientRect();
        const headBox = head.getBoundingClientRect();
        const bodyBox = body.getBoundingClientRect();
        if (bodyBox.height <= 0) {
            this.hideColumnResizeGuide();
            return;
        }
        // The line continues the header's separator, so it is placed on that
        // separator rather than on the cell's outer edge: the separator is the
        // cell's own border, which occupies the last pixels inside the box.
        //
        // Nothing here is rounded. Rounding to whole pixels put the line beside
        // the separator whenever the cell's edge fell on a fraction -- which is
        // what a column width in rem or a root size of 87.5% produces, and what
        // the consumer measured as one pixel aside.
        const border = parseFloat(getComputedStyle(th).borderInlineEndWidth) || 0;
        const inlineStart = this.isRightToLeft()
            ? cell.left - area.left + scroll.scrollLeft
            : cell.right - area.left + scroll.scrollLeft - border;
        guide.style.insetInlineStart = `${inlineStart}px`;
        guide.style.insetBlockStart = `${headBox.bottom - area.top + scroll.scrollTop}px`;
        guide.style.blockSize = `${bodyBox.height}px`;
        guide.hidden = false;
    }

    hideColumnResizeGuide() {
        const guide = this.refs.resizeGuide?.value;
        if (guide) {
            guide.hidden = true;
        }
    }

    isRightToLeft() {
        return getComputedStyle(this).direction === 'rtl';
    }


    bindHoverEvent() {
        if (this._hoverEventBound) return;
        this._hoverEventBound = true;
        const table = this.refs.table?.value;
        table?.addEventListener('mouseover', this.hoverEvents);
        table?.addEventListener('mouseout', this.outEvent);
        //
        // this.addEventListener('mouseout', () => {
        //     this.querySelectorAll('.hover')
        //         .forEach(el => el.classList.remove('hover'));
        // });
    }

    unBindHoverEvent() {
        if (!this._hoverEventBound) {
            return;
        }
        const table = this.refs.table?.value;
        table?.removeEventListener('mouseover', this.hoverEvents);
        table?.removeEventListener('mouseout', this.outEvent);
        this._hoverEventBound = false;
    }

    bindColumnResizeEvent() {
        if (this._columnResizeEventBound) return;

        const table = this.refs.table?.value;
        if (!table) return;

        table.addEventListener('pointerdown', this.onColumnResizePointerDown);
        this._columnResizeEventBound = true;
    }

    unbindColumnResizeEvent() {
        if (!this._columnResizeEventBound) {
            return;
        }

        const table = this.refs.table?.value;
        table?.removeEventListener('pointerdown', this.onColumnResizePointerDown);
        this._columnResizeEventBound = false;
    }

    bindColumnDragEvent() {
        if (this._columnDragEventBound) return;

        const table = this.refs.table?.value;
        if (!table) return;

        table.addEventListener('pointerdown', this.onColumnDragPointerDown);
        this._columnDragEventBound = true;
    }

    unbindColumnDragEvent() {
        if (!this._columnDragEventBound) {
            return;
        }

        const table = this.refs.table?.value;
        table?.removeEventListener('pointerdown', this.onColumnDragPointerDown);
        this._columnDragEventBound = false;
    }

    getColumnResizeHandle(target) {
        if (!(target instanceof Element)) {
            return null;
        }

        // The handle is found by the attribute it carries, not by a prefix of its
        // id. The id is built from the instance, so it never began with
        // `column_resizer_`: pointerdown on the handle was not recognised as a
        // resize and fell through to the column-move handler, which is why
        // dragging the edge moved the column instead of changing its width.
        return target.closest('[data-sf-column-resizer]');
    }

    getColumnDragHeader(target) {
        if (!(target instanceof Element)) {
            return null;
        }

        if (this.getColumnResizeHandle(target)) {
            return null;
        }

        return target.closest('th[data-key]');
    }

    parseColumnSize(value, fallback = null) {
        if (typeof value === 'number' && Number.isFinite(value)) {
            return value;
        }

        if (typeof value === 'string') {
            const normalized = value.trim();

            if (/^-?\d+(\.\d+)?px$/.test(normalized)) {
                return parseFloat(normalized);
            }
        }

        return fallback;
    }

    resolveCssLength(value, fallback = 0) {
        const normalized = String(value || '').trim();
        const match = normalized.match(/^(-?\d+(?:\.\d+)?)(px|rem|em)$/);

        if (!match) {
            return fallback;
        }

        const amount = Number(match[1]);
        const unit = match[2];

        if (unit === 'px') {
            return amount;
        }

        const reference = unit === 'rem' ? document.documentElement : this;
        const fontSize = parseFloat(getComputedStyle(reference).fontSize);
        return Number.isFinite(fontSize) ? amount * fontSize : fallback;
    }

    getColumnResizeLimits(column = {}, fallbackWidth = 0) {
        const semanticMinWidth = this.resolveCssLength(
            getComputedStyle(this).getPropertyValue('--sf-c4'),
            fallbackWidth,
        );
        const minWidth =
            this.parseColumnSize(column.minWidth, null) ??
            semanticMinWidth;
        const maxWidth = this.parseColumnSize(column.maxWidth, Infinity);

        return {
            minWidth,
            maxWidth: Math.max(maxWidth, minWidth),
        };
    }

    resizeColumnByKeyboard(event, key) {
        if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) {
            return;
        }

        const table = this.refs.table?.value;
        const escapedKey = this.escapeColumnKey(key);
        const th = table?.querySelector(`th[data-key="${escapedKey}"]`);
        const column = this.getColumns().find((item) => item?.key === key);

        if (!th || !column || column.system || column.resizable === false) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const currentWidth = th.getBoundingClientRect().width;
        const {minWidth, maxWidth} = this.getColumnResizeLimits(column, currentWidth);
        const step = this.resolveCssLength(
            getComputedStyle(this).getPropertyValue('--sf-space-1'),
            1,
        );
        const direction = getComputedStyle(table).direction === 'rtl' ? -1 : 1;
        const keyDirection = event.key === 'ArrowRight' ? 1 : -1;
        const nextWidth = Math.min(
            Math.max(currentWidth + step * direction * keyDirection, minWidth),
            maxWidth,
        );

        this.applyColumnResizeWidths(key, nextWidth);
        requestAnimationFrame(() => {
            // The strip is a button itself, so it is the focus target; it used
            // to be the inner control of an icon button.
            this.querySelector(`th[data-key="${escapedKey}"] .sf-table-column-resizer`)?.focus?.();
            this.showColumnResizeGuide(th);
        });
    }

    hasColumnSize(column = {}) {
        return [
            column.colWidth,
            column.width,
            column.size,
        ].some(
            (value) =>
                value !== null &&
                typeof value !== 'undefined' &&
                String(value).trim() !== '',
        );
    }

    escapeColumnKey(key) {
        const value = String(key || '');

        if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
            return CSS.escape(value);
        }

        return value.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
    }

    freezeColumnsWidthForResize(table) {
        const frozenWidths = {};
        const columns = this.getColumns();

        columns.forEach((column) => {
            if (!column?.key) {
                return;
            }

            const key = String(column.key);
            const escapedKey = this.escapeColumnKey(key);
            const th = table?.querySelector(`th[data-key="${escapedKey}"]`);
            const col = table?.querySelector(`col[data-key="${escapedKey}"]`);
            const width = th?.getBoundingClientRect?.().width || 0;

            if (!width) {
                return;
            }

            const nextWidth = `${Math.round(width)}px`;

            if (col) {
                col.style.width = nextWidth;
            }

            if (!column.system && !this.hasColumnSize(column)) {
                frozenWidths[key] = nextWidth;
            }
        });

        return frozenWidths;
    }

    applyColumnResizeWidths(key, width, frozenWidths = {}) {
        if (!key) {
            return this;
        }

        const nextWidths = {
            ...(frozenWidths || {}),
            [key]: `${Math.round(width)}px`,
        };
        const settingsPatch = Object.fromEntries(
            Object.entries(nextWidths).map(([columnKey, columnWidth]) => [
                columnKey,
                {
                    width: columnWidth,
                },
            ]),
        );

        return this.patchColumnSettingsBatch(
            settingsPatch,
            'column-resize',
        );
    }

    getColumnDragLayout(table) {
        const columns = this.getColumns();

        return columns
            .filter((column) => column?.key && !column.system && column.draggable !== false)
            .map((column) => {
                const key = String(column.key);
                const escapedKey = this.escapeColumnKey(key);
                const th = table?.querySelector(`th[data-key="${escapedKey}"]`);
                const rect = th?.getBoundingClientRect?.();

                if (!th || !rect) {
                    return null;
                }

                return {
                    key,
                    column,
                    th,
                    left: rect.left,
                    right: rect.right,
                    width: rect.width,
                    middle: rect.left + rect.width / 2,
                    cellIndex: th.cellIndex,
                };
            })
            .sort((left, right) => left.cellIndex - right.cellIndex)
            .filter(Boolean);
    }

    getColumnDragDropIndex(layout, x, draggingKey) {
        const items = layout.filter((item) => item.key !== draggingKey);

        for (let index = 0; index < items.length; index += 1) {
            if (x < items[index].middle) {
                return index;
            }
        }

        return items.length;
    }

    getVisibleUserColumnKeysFromDom(table) {
        const systemKeys = new Set(
            this.getSystemColumns().map((column) => String(column.key)),
        );
        const headerCells = Array.from(table?.tHead?.rows?.[0]?.cells || []);

        return headerCells
            .map((cell) => cell?.dataset?.key)
            .filter((key) => key && !systemKeys.has(String(key)));
    }

    reorderColumnDomByKeys(table, userKeys = []) {
        if (!table || !Array.isArray(userKeys) || !userKeys.length) {
            return;
        }

        const moveKeyedChildren = (container, selector) => {
            if (!container) {
                return;
            }

            const userKeySet = new Set(userKeys);
            let insertAfter = null;

            for (const child of Array.from(container.children || [])) {
                const childKey = child?.dataset?.key;

                if (childKey && userKeySet.has(childKey)) {
                    break;
                }

                if (childKey) {
                    insertAfter = child;
                }
            }

            userKeys.forEach((key) => {
                const escapedKey = this.escapeColumnKey(key);
                const child = container.querySelector(
                    `${selector}[data-key="${escapedKey}"]`,
                );

                if (child) {
                    container.insertBefore(
                        child,
                        insertAfter?.nextSibling || container.firstChild,
                    );
                    insertAfter = child;
                }
            });
        };

        moveKeyedChildren(table.querySelector('colgroup'), 'col');
        moveKeyedChildren(table.tHead?.rows?.[0], 'th');
        Array.from(table.tBodies || []).forEach((tbody) => {
            Array.from(tbody.rows || []).forEach((row) => {
                moveKeyedChildren(row, 'td');
            });
        });
    }

    getColumnAnimationCells(table) {
        return Array.from(
            table?.querySelectorAll('thead th[data-key], tbody td[data-key]') || [],
        ).filter((cell) => cell instanceof HTMLElement);
    }

    snapshotColumnCellRects(table) {
        const rects = new Map();

        this.getColumnAnimationCells(table).forEach((cell) => {
            rects.set(cell, cell.getBoundingClientRect());
        });

        return rects;
    }

    clearColumnAnimationStyles(table = this.refs.table?.value) {
        this._columnAnimationToken += 1;

        this.getColumnAnimationCells(table).forEach((cell) => {
            cell.style.transition = '';
            cell.style.transform = '';
            cell.style.willChange = '';
        });
    }

    clearColumnDragDom(table = this.refs.table?.value) {
        table
            ?.querySelectorAll(
                [
                    '.is-column-dragging',
                    '.is-column-drag-placeholder-cell',
                    '.is-column-drag-before',
                    '.is-column-drag-after',
                ].join(','),
            )
            .forEach((cell) => {
                cell.classList.remove(
                    'is-column-dragging',
                    'is-column-drag-placeholder-cell',
                    'is-column-drag-before',
                    'is-column-drag-after',
                );
            });

        document
            .querySelectorAll('.sf-table-column-drag-ghost')
            .forEach((ghost) => ghost.remove());
        document.documentElement.classList.remove('sf-table-column-dragging');
    }

    animateColumnReorder(table, beforeRects) {
        if (!table || !(beforeRects instanceof Map)) {
            return;
        }

        const animationToken = ++this._columnAnimationToken;
        const cells = this.getColumnAnimationCells(table);

        cells.forEach((cell) => {
            const before = beforeRects.get(cell);

            if (!before) {
                return;
            }

            const after = cell.getBoundingClientRect();
            const deltaX = before.left - after.left;

            if (Math.abs(deltaX) < 1) {
                return;
            }

            cell.style.transition = 'none';
            cell.style.transform = `translate3d(${deltaX}px, 0, 0)`;
            cell.style.willChange = 'transform';
        });

        requestAnimationFrame(() => {
            if (animationToken !== this._columnAnimationToken) {
                return;
            }

            cells.forEach((cell) => {
                if (!cell.style.transform) {
                    return;
                }

                cell.style.transition = 'transform 140ms ease';
                cell.style.transform = 'translate3d(0, 0, 0)';
            });

            window.setTimeout(() => {
                if (animationToken !== this._columnAnimationToken) {
                    return;
                }

                cells.forEach((cell) => {
                    cell.style.transition = '';
                    cell.style.transform = '';
                    cell.style.willChange = '';
                });
            }, 160);
        });
    }

    updateColumnDragPreview(state, dropIndex) {
        if (!state || state.dropIndex === dropIndex) {
            return;
        }

        state.layout.forEach((item) => {
            item.th.classList.remove(
                'is-column-drag-before',
                'is-column-drag-after',
            );
        });

        state.dropIndex = dropIndex;

        const items = state.layout.filter((item) => item.key !== state.key);
        const nextKeys = items.map((item) => item.key);

        nextKeys.splice(
            Math.max(0, Math.min(dropIndex, nextKeys.length)),
            0,
            state.key,
        );

        if (nextKeys.join('|') === state.currentKeys?.join('|')) {
            return;
        }

        state.currentKeys = nextKeys;
        const beforeRects = this.snapshotColumnCellRects(state.table);
        this.reorderColumnDomByKeys(state.table, nextKeys);
        this.animateColumnReorder(state.table, beforeRects);
        state.layout = this.getColumnDragLayout(state.table);
        this.setColumnDragPlaceholderClass(state.key, true, state.table);

        const target = state.layout.find((item) => item.key === state.key);

        if (!target) {
            return;
        }

        target.th.classList.add('is-column-dragging');
    }

    setColumnDragPlaceholderClass(key, active, table = this.refs.table?.value) {
        if (!key || !table) {
            return;
        }

        const escapedKey = this.escapeColumnKey(key);

        table
            .querySelectorAll(`th[data-key="${escapedKey}"], td[data-key="${escapedKey}"]`)
            .forEach((cell) => {
                cell.classList.toggle('is-column-dragging', Boolean(active));
                cell.classList.toggle('is-column-drag-placeholder-cell', Boolean(active));
            });
    }

    createColumnDragGhost(th) {
        const rect = th.getBoundingClientRect();
        const ghost = document.createElement('div');
        const label =
            th?.dataset?.label ||
            th?.textContent?.replace(/\s+/g, ' ').trim() ||
            '';

        ghost.textContent = label;
        ghost.classList.add('sf-table-column-drag-ghost');
        Object.assign(ghost.style, {
            position: 'fixed',
            left: `${rect.left}px`,
            top: `${rect.top}px`,
            width: `${rect.width}px`,
            height: `${rect.height}px`,
            zIndex: 'var(--sf-table-drag-ghost--z-index, var(--sf-z-index-9))',
            pointerEvents: 'none',
            boxSizing: 'border-box',
        });
        ghost.setAttribute('aria-hidden', 'true');

        document.body.append(ghost);

        return ghost;
    }

    clearColumnDragState(commit = false, event = null) {
        const state = this._columnDragState;

        document.removeEventListener('pointermove', this.onColumnDragPointerMove);
        document.removeEventListener('pointerup', this.onColumnDragPointerUp);
        document.removeEventListener('pointercancel', this.onColumnDragPointerCancel);
        document.documentElement.classList.remove('sf-table-column-dragging');

        if (!state) {
            this.clearColumnAnimationStyles();
            this.clearColumnDragDom();
            setLoaderDragState(false);
            return;
        }

        // A drop is decided in a frame, and a short drag can end before that
        // frame ever runs: the pointer goes down, moves a few times and comes
        // up inside one frame. Cancelling then threw the decision away and the
        // move did nothing at all, which is exactly what a consumer saw on a
        // quick drag. The pending decision is made now instead.
        if (state.frame) {
            if (commit) {
                this.flushColumnDragPreview(state);
            } else {
                cancelAnimationFrame(state.frame);
                state.frame = null;
            }
        }

        state.handle?.releasePointerCapture?.(event?.pointerId);
        if (state.started) {
            this.suppressNextHeaderClick();
        }
        state.ghost?.remove();
        this.clearColumnAnimationStyles(state.table);
        this.setColumnDragPlaceholderClass(state.key, false, state.table);
        state.th?.classList.remove('is-column-dragging');
        state.layout?.forEach((item) => {
            item.th.classList.remove(
                'is-column-drag-before',
                'is-column-drag-after',
            );
        });

        // The order to commit is the one the drag worked out, not the one the
        // cells happen to be in. The preview moves cells by hand, so reading
        // the order back from the DOM worked only as long as nothing re-rendered
        // in between — and a host hands its rows back on every answer, which
        // puts the cells back and made the whole move disappear without a word.
        // The DOM is still read when the drag never updated a preview, so a
        // reorder by any other means is not lost either.
        const previewKeys = Array.isArray(state.currentKeys) ? state.currentKeys : [];
        const domKeys = this.getVisibleUserColumnKeysFromDom(state.table);
        const nextKeys = previewKeys.length ? previewKeys : domKeys;
        const shouldCommit =
            commit &&
            state.started &&
            nextKeys.length &&
            nextKeys.join('|') !== state.originalKeys?.join('|');

        this._columnDragState = null;
        this.clearColumnDragDom(state.table);
        setLoaderDragState(false);

        // The preview moved header and body cells by hand, between Lit's part
        // markers. Put every node back exactly as it was, so a committed order
        // renders over the DOM Lit knows (otherwise moved cells stay and the
        // columns appear twice) and a cancelled drag leaves nothing behind.
        // The order update runs in a microtask, before the next paint.
        if (state.started) {
            this.restoreColumnDom(state.domSnapshot);
        }

        if (!shouldCommit) {
            return;
        }

        this.applyColumnOrder(nextKeys, state.frozenWidths);
    }

    snapshotColumnDom(table) {
        if (!table) return [];
        const containers = [
            table.querySelector('colgroup'),
            table.tHead?.rows?.[0],
            ...Array.from(table.tBodies || []).flatMap((tbody) => Array.from(tbody.rows || [])),
        ].filter(Boolean);
        return containers.map((container) => ({container, nodes: Array.from(container.childNodes)}));
    }

    restoreColumnDom(snapshot = []) {
        for (const {container, nodes} of snapshot || []) {
            if (!container.isConnected || nodes.some((node) => node.parentNode !== container)) continue;
            container.append(...nodes);
        }
    }

    applyColumnOrder(userKeys = [], frozenWidths = {}) {
        // Moving a column rearranges the columns the person can see, among the
        // places those columns already occupy. Numbering every draggable
        // column from ten upward instead renumbered the hidden ones too, and a
        // hidden column has no place on screen to be numbered from, so they
        // came out ahead of everything: a consumer dropped one column one place
        // to the left and found its nine visible columns pushed behind seven it
        // had never shown. Hidden columns keep their order, so they keep their
        // place between the visible ones when they are shown again.
        if (!Array.isArray(userKeys) || !userKeys.length) {
            return this;
        }

        const settingsPatch = {};
        const slots = this.getColumnOrderSlots(userKeys);

        userKeys.forEach((key, index) => {
            settingsPatch[key] = {
                order: slots[index],
            };

            if (frozenWidths?.[key]) {
                settingsPatch[key].width = frozenWidths[key];
            }
        });

        return this.patchColumnSettingsBatch(
            settingsPatch,
            'column-drag',
        );
    }

    // The places the moved columns already occupy, in order. A column whose
    // order nobody has ever set takes the place its position gives it, so a
    // first move on an untouched layout still numbers in tens.
    getColumnOrderSlots(userKeys = []) {
        const all = (this.getTableData().columns || []).filter((column) => column?.key && !column.system);
        const effective = (column, index) => {
            const setting = this.getColumnSetting(column.key) || {};
            const value = setting.order ?? column.order;
            const parsed = Number(value);
            return Number.isFinite(parsed) ? parsed : (index + 1) * 10;
        };
        const current = new Map(all.map((column, index) => [String(column.key), effective(column, index)]));
        const taken = userKeys
            .map((key) => current.get(String(key)))
            .filter((value) => Number.isFinite(value))
            .sort((left, right) => left - right);
        // Two columns can share a place when a host sends the same order twice.
        // Then the places are rebuilt from the positions, which cannot collide.
        const distinct = new Set(taken);
        if (taken.length !== userKeys.length || distinct.size !== taken.length) {
            return userKeys.map((_, index) => (index + 1) * 10);
        }

        return taken;
    }

    setColumnResizeClasses(th, delta = 0) {
        if (!th) return;

        th.classList.add('is-resizing');
        th.classList.toggle('is-resizing-forward', delta >= 0);
        th.classList.toggle('is-resizing-backward', delta < 0);
        // The line follows the edge while it is dragged, not only on hover.
        this.showColumnResizeGuide(th);
        // Resizing a pinned column moves every pinned column after it.
        this.measurePinnedColumns();
    }

    clearColumnResizeClasses(th) {
        if (!th) return;

        th.classList.remove(
            'is-resizing',
            'is-resizing-forward',
            'is-resizing-backward',
        );
        this.hideColumnResizeGuide();
        this.measurePinnedColumns();
    }

    onColumnDragPointerDown(event) {
        if (event.button !== 0 || this._columnResizeState) {
            return;
        }

        const th = this.getColumnDragHeader(event.target);
        const key = th?.dataset?.key;

        if (!th || !key) {
            return;
        }

        const table = this.refs.table?.value;
        this.clearColumnAnimationStyles(table);
        this.clearColumnDragDom(table);

        const layout = this.getColumnDragLayout(table);
        const column = layout.find((item) => item.key === key);
        const userKeys = layout.map((item) => item.key);

        if (!column || layout.length < 2) {
            return;
        }

        this._columnDragState = {
            key,
            th,
            table,
            handle: th,
            startRect: th.getBoundingClientRect(),
            startX: event.clientX,
            startY: event.clientY,
            shiftX: event.clientX - column.left,
            shiftY: event.clientY - th.getBoundingClientRect().top,
            originalIndex: layout.findIndex((item) => item.key === key),
            originalKeys: userKeys,
            currentKeys: userKeys,
            dropIndex: null,
            layout,
            started: false,
            ghost: null,
            frozenWidths: null,
        };

        th.setPointerCapture?.(event.pointerId);
        document.addEventListener('pointermove', this.onColumnDragPointerMove);
        document.addEventListener('pointerup', this.onColumnDragPointerUp, {
            once: true,
        });
        document.addEventListener('pointercancel', this.onColumnDragPointerCancel, {
            once: true,
        });
    }

    // A render in the middle of a drag replaces the header cells, and the drag
    // was left holding the old ones: the ghost kept moving while the drop was
    // computed against elements no longer in the page. The cells are found
    // again by the column key, which a render cannot change.
    reconnectColumnDragState(state) {
        const table = this.refs.table?.value;

        if (!table) {
            return false;
        }

        if (state.table !== table || !state.th?.isConnected) {
            const escapedKey = this.escapeColumnKey(state.key);
            const th = table.querySelector(`th[data-key="${escapedKey}"]`);

            if (!th) {
                return false;
            }

            state.table = table;
            state.th = th;
            state.handle = th;
            // The preview's own marks went with the old cells, and so did the
            // widths frozen on them.
            state.frozenWidths = this.freezeColumnsWidthForResize(table);
            state.domSnapshot = this.snapshotColumnDom(table);
            state.dropIndex = null;
            state.layout = this.getColumnDragLayout(table);
            this.setColumnDragPlaceholderClass(state.key, true, table);
            th.classList.add('is-column-dragging');
        }

        return true;
    }

    onColumnDragPointerMove(event) {
        const state = this._columnDragState;

        if (!state) {
            return;
        }

        if (state.started && !this.reconnectColumnDragState(state)) {
            this.clearColumnDragState(false, event);
            return;
        }

        const deltaX = event.clientX - state.startX;
        const deltaY = event.clientY - state.startY;

        if (!state.started) {
            if (Math.abs(deltaX) < 4 && Math.abs(deltaY) < 4) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            state.started = true;
            state.domSnapshot = this.snapshotColumnDom(state.table);
            setLoaderDragState(true);
            state.frozenWidths = this.freezeColumnsWidthForResize(state.table);
            state.ghost = this.createColumnDragGhost(state.th);
            this.setColumnDragPlaceholderClass(state.key, true, state.table);
            document.documentElement.classList.add('sf-table-column-dragging');
        }

        if (state.frame) {
            state.latestEvent = event;
            return;
        }

        state.latestEvent = event;
        state.frame = requestAnimationFrame(() => {
            const latestEvent = state.latestEvent;
            state.frame = null;

            if (!latestEvent || !state.ghost) {
                return;
            }

            state.ghost.style.left = `${latestEvent.clientX - state.shiftX}px`;
            state.ghost.style.top = `${latestEvent.clientY - state.shiftY}px`;
            state.layout = this.getColumnDragLayout(state.table);

            const dropIndex = this.getColumnDragDropIndex(
                state.layout,
                latestEvent.clientX,
                state.key,
            );

            this.updateColumnDragPreview(state, dropIndex);
        });
    }

    flushColumnDragPreview(state) {
        if (!state?.frame) {
            return this;
        }

        cancelAnimationFrame(state.frame);
        state.frame = null;
        const latestEvent = state.latestEvent;

        if (!latestEvent || !state.ghost) {
            return this;
        }

        state.layout = this.getColumnDragLayout(state.table);
        const dropIndex = this.getColumnDragDropIndex(
            state.layout,
            latestEvent.clientX,
            state.key,
        );
        this.updateColumnDragPreview(state, dropIndex);

        return this;
    }

    onColumnDragPointerUp(event) {
        this.clearColumnDragState(true, event);
    }

    onColumnDragPointerCancel(event) {
        this.clearColumnDragState(false, event);
    }

    onColumnResizePointerDown(event) {
        const handle = this.getColumnResizeHandle(event.target);

        if (!handle) {
            return;
        }

        const th = handle.closest('th');
        const key = th?.dataset?.key;

        if (!th || !key) {
            return;
        }

        const column = this.getColumns().find((item) => item?.key === key);

        if (!column || column.system || column.resizable === false) {
            return;
        }

        const table = this.refs.table?.value;
        const escapedKey = this.escapeColumnKey(key);
        const col = table?.querySelector(`col[data-key="${escapedKey}"]`);
        const startWidth = th.getBoundingClientRect().width;
        const limits = this.getColumnResizeLimits(column, startWidth);
        const frozenWidths = this.freezeColumnsWidthForResize(table);

        event.preventDefault();
        event.stopPropagation();

        this._columnResizeState = {
            key,
            th,
            col,
            startX: event.clientX,
            startWidth,
            width: startWidth,
            frozenWidths,
            ...limits,
        };

        // this.setColumnResizeClasses(th);
        setLoaderDragState(true);
        document.documentElement.classList.add('sf-table-column-resizing');
        document.addEventListener('pointermove', this.onColumnResizePointerMove);
        document.addEventListener('pointerup', this.onColumnResizePointerUp, {
            once: true,
        });
        document.addEventListener('pointercancel', this.onColumnResizePointerUp, {
            once: true,
        });
    }

    onColumnResizePointerMove(event) {
        const state = this._columnResizeState;

        if (!state) {
            return;
        }

        const delta = event.clientX - state.startX;
        state.width = Math.min(
            Math.max(state.startWidth + delta, state.minWidth),
            state.maxWidth,
        );
        state.delta = delta;

        if (state.frame) {
            return;
        }

        state.frame = requestAnimationFrame(() => {
            state.frame = null;

            // this.setColumnResizeClasses(state.th, state.delta);

            if (state.col) {
                state.col.style.width = `${state.width}px`;
            }

            state.th.style.width = `${state.width}px`;
            // The line is placed from the cell's own edge, so it has to be
            // placed again in the same frame the edge moved in. Without this it
            // stayed where the drag began while the header moved away from it.
            this.showColumnResizeGuide(state.th);
        });
    }

    onColumnResizePointerUp() {
        const state = this._columnResizeState;

        document.removeEventListener('pointermove', this.onColumnResizePointerMove);
        document.removeEventListener('pointerup', this.onColumnResizePointerUp);
        document.removeEventListener('pointercancel', this.onColumnResizePointerUp);
        document.documentElement.classList.remove('sf-table-column-resizing');

        if (!state) {
            return;
        }

        setLoaderDragState(false);
        this.suppressNextHeaderClick();
        // this.clearColumnResizeClasses(state.th);
        this._columnResizeState = null;
        this.outEvent({
            target: state.th,
        });
        state.th.style.width = '';

        this.applyColumnResizeWidths(
            state.key,
            state.width,
            state.frozenWidths,
        );
    }

    bindContextEvent() {
        if (this._contextEventBound) {
            return;
        }

        document.addEventListener("click", this.contextEvent);
        document.addEventListener("keydown", this.contextKeyEvent);
        this._contextEventBound = true;
    }

    unbindContextEvent() {
        if (!this._contextEventBound) {
            return;
        }

        document.removeEventListener("click", this.contextEvent);
        document.removeEventListener("keydown", this.contextKeyEvent);
        this._contextEventBound = false;
    }

    contextKeyEvent(event) {
        if (event.key !== 'Escape' || !this.state.contextMenu?.open) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        this.closeContextMenu(true);
    }

    // The table's windows -- filter, field popovers, settings, menus -- are
    // drawn in the page's top layer from inside the table, not moved to the end
    // of the body. Moved out, a window was not clipped by the table's scroll
    // box, but it lost everything the table gives what it holds: its size (the
    // filter opened a step larger than a table at 1/2), its language, its theme,
    // the variables its containers set (owner, 2026-10-13). In the top layer it
    // stays where it belongs and is still drawn above everything, unclipped.
    //
    // The container is a form of its own, before the table's rendered content:
    // inside a page's form a filter field would be submitted with it and Enter
    // in one would send the page's form; and before the content, so a re-render
    // of the table never takes it away. Its fields' own change and input stop
    // at it: they are the table's business, not the page's.
    getPortalContainer() {
        if (this._portalContainer?.isConnected) {
            return this._portalContainer;
        }

        const portal = document.createElement("form");
        portal.className = "sf-table-portal";
        portal.noValidate = true;
        portal.dataset.tableSettingsKey = this.getTableSettingsKey();
        portal.dataset.sfInstance = this.getInstanceId();
        if (typeof portal.showPopover === "function") portal.setAttribute("popover", "manual");
        portal.addEventListener("submit", (event) => event.preventDefault());
        for (const type of ["change", "input"]) {
            portal.addEventListener(type, (event) => event.stopPropagation());
        }
        this.insertBefore(portal, this.firstChild);
        this._portalContainer = portal;

        return portal;
    }

    // The top layer is entered while a window is open and left when none is.
    showPortal() {
        const portal = this._portalContainer;
        if (!portal?.isConnected || typeof portal.showPopover !== "function") return;
        if (!portal.matches(":popover-open")) portal.showPopover();
    }

    hidePortal() {
        const portal = this._portalContainer;
        if (!portal?.isConnected || typeof portal.hidePopover !== "function") return;
        if (portal.matches(":popover-open")) portal.hidePopover();
    }

    getVisualViewportRect() {
        const viewport = window.visualViewport;

        return {
            left: viewport?.offsetLeft || 0,
            top: viewport?.offsetTop || 0,
            right: (viewport?.offsetLeft || 0) + (viewport?.width || window.innerWidth),
            bottom: (viewport?.offsetTop || 0) + (viewport?.height || window.innerHeight),
        };
    }

    clampContextMenusToViewport() {
        const portal = this._portalContainer;

        if (!portal?.isConnected) {
            return;
        }

        const viewport = this.getVisualViewportRect();
        const rootStyle = getComputedStyle(document.documentElement);
        const inset = parseFloat(rootStyle.getPropertyValue('--sf-space-1/3')) || 0;

        const anchored = new Set();
        portal.querySelectorAll('sf-context-menu').forEach((menu) => {
            if (this.anchorContextMenu(menu, viewport, inset)) {
                anchored.add(menu);
                return;
            }
            const availableInlineSize = Math.max(
                0,
                viewport.right - viewport.left - inset * 2,
            );
            const availableBlockSize = Math.max(
                0,
                viewport.bottom - viewport.top - inset * 2,
            );
            // Both names: the stylesheet reads the second one, and only the
            // first was written here, so a menu placed by click coordinates
            // never received the cap this line means to give it.
            menu.style.setProperty(
                '--sf-table-context-menu--available-inline-size',
                `${availableInlineSize}px`,
            );
            menu.style.setProperty(
                '--sf-context-menu-available-inline-size',
                `${availableInlineSize}px`,
            );
            menu.style.setProperty(
                '--sf-context-menu-available-block-size',
                `${availableBlockSize}px`,
            );
            menu.style.translate = '';
            const surface = menu.querySelector(':scope > .sf-context-menu') || menu;
            const rect = surface.getBoundingClientRect();
            let offsetX = 0;
            let offsetY = 0;

            if (rect.left < viewport.left + inset) {
                offsetX = viewport.left + inset - rect.left;
            } else if (rect.right > viewport.right - inset) {
                offsetX = viewport.right - inset - rect.right;
            }

            if (rect.top < viewport.top + inset) {
                offsetY = viewport.top + inset - rect.top;
            } else if (rect.bottom > viewport.bottom - inset) {
                offsetY = viewport.bottom - inset - rect.bottom;
            }

            if (offsetX || offsetY) {
                menu.style.translate = `${offsetX}px ${offsetY}px`;
            }
        });
        for (const [menu, entry] of this._contextPositions || []) {
            if (!anchored.has(menu)) {
                entry.controller.stop();
                this._contextPositions.delete(menu);
            }
        }
    }

    // Placement of an open menu relative to the element it was opened from:
    // menus with a tail (row actions, template actions) open beside it, the
    // others below it. null keeps the static click-coordinate placement.
    // Cells re-render their controls, so an opener can be replaced while its
    // menu is open. Remember where it sits (row cell, header, toolbar id) to
    // find its replacement.
    describeContextAnchor(anchor) {
        if (!(anchor instanceof Element)) return null;
        const host = anchor.closest('[data-item], th[data-key], [id]');
        if (!host || !this.contains(host)) return null;
        const selector = host.dataset.item !== undefined
            ? `[data-item="${this.escapeColumnKey(host.dataset.item)}"]`
            : host.matches('th[data-key]')
                ? `th[data-key="${this.escapeColumnKey(host.dataset.key)}"]`
                : `[id="${this.escapeColumnKey(host.id)}"]`;
        const tag = anchor.tagName.toLowerCase();
        return {selector, tag, index: host === anchor ? -1 : Array.from(host.querySelectorAll(tag)).indexOf(anchor)};
    }

    resolveContextAnchor(data) {
        if (data?.anchor instanceof Element && data.anchor.isConnected) return data.anchor;
        const ref = data?.anchorRef;
        const host = ref ? this.querySelector(ref.selector) : null;
        const anchor = host && (ref.index < 0 ? host : host.querySelectorAll(ref.tag)[ref.index]);
        if (!anchor) return null;
        data.anchor = anchor;
        return anchor;
    }

    // Menus opened from a control at the end of a row align to that control's
    // end edge, so they open under the button instead of hanging past it over
    // the next column. The consumer measured a menu whose left edge matched the
    // button's left and whose right edge stood 34 to 124px beyond it.
    getContextMenuPlacement(menu) {
        const isSubmenu = menu.hasAttribute('data-context-submenu');
        const data = isSubmenu ? this.state.contextMenu?.submenu : this.state.contextMenu;
        const anchor = this.resolveContextAnchor(data);
        if (!anchor) return null;
        const position = String(data.position || (isSubmenu || data.type === 'left' ? 'left' : ''));
        const family = /^(left|right)(-top|-bottom)?$/.exec(position);
        if (!family) {
            // A menu belonging to a control at the end of its row aligns to that
            // control's end edge; otherwise it hangs past the button and over
            // whatever is beside it. The create arrow and the view's actions are
            // both such controls.
            const toTheEnd = ['create', 'toolbar-actions'].includes(data.menu);
            return {anchor, side: 'block-end', align: toTheEnd ? 'end' : 'start', tail: null};
        }
        const suffix = family[2] || '';
        return {
            anchor,
            side: family[1] === 'left' ? 'inline-end' : 'inline-start',
            align: suffix === '-top' ? 'start' : suffix === '-bottom' ? 'end' : 'center',
            tail: suffix,
        };
    }

    // Shared Framework geometry (SF.Position): the menu flips when its side
    // does not fit, shifts inside the viewport and follows its opener while
    // the page scrolls. Sizes are written to the rendered surface, not the
    // boxless host, so they never re-render the menu over this placement.
    anchorContextMenu(menu, viewport, inset) {
        const placement = this.getContextMenuPlacement(menu);
        if (!placement) return false;
        const surface = menu.querySelector(':scope > .sf-context-menu');
        if (!surface) return false;
        this._contextPositions = this._contextPositions || new Map();
        const current = this._contextPositions.get(menu);
        // SF.Position writes an inline max-width from the free space, which
        // would override the menu's stylesheet cap; keep that cap in the width.
        const cssCap = current?.cssCap ?? (parseFloat(getComputedStyle(surface).maxWidth) || Infinity);
        const inlineSize = `${Math.max(0, Math.min(viewport.right - viewport.left - inset * 2, cssCap))}px`;
        surface.style.setProperty('--sf-table-context-menu--available-inline-size', inlineSize);
        surface.style.setProperty('--sf-context-menu-available-inline-size', inlineSize);
        const key = `${placement.side}|${placement.align}`;
        if (current && current.anchor === placement.anchor && current.surface === surface && current.key === key) {
            current.controller.update();
            return true;
        }
        current?.controller.stop();
        surface.style.transform = 'none';
        surface.style.translate = 'none';
        surface.style.setProperty('--sf-context-menu-available-block-size', `${Math.max(0, viewport.bottom - viewport.top - inset * 2)}px`);
        const Position = positioning();
        const gap = placement.tail === null
            ? Position.resolveLength('calc(var(--sf-focus-outline-width) * 2)', surface)
            : Position.resolveLength('var(--sf-context-menu-tail-corner-offset)', surface);
        const anchorBox = placement.anchor.getBoundingClientRect();
        const controller = Position.anchor(placement.anchor, surface, {
            side: placement.side,
            align: placement.align,
            offset: gap,
            alignmentOffset: placement.align === 'center' || placement.tail === null
                ? 0
                : Math.max(0, anchorBox.height / 2 - gap),
            padding: inset,
            fitHeight: false,
            onPosition: ({side}) => {
                const box = placement.anchor.getBoundingClientRect();
                const view = this.getVisualViewportRect();
                const blockSize = side === 'block-end'
                    ? view.bottom - inset - box.bottom - gap
                    : side === 'block-start'
                        ? box.top - gap - inset - view.top
                        : view.bottom - view.top - inset * 2;
                const value = `${Math.max(0, Math.floor(blockSize))}px`;
                if (surface.style.getPropertyValue('--sf-context-menu-available-block-size') !== value) {
                    surface.style.setProperty('--sf-context-menu-available-block-size', value);
                }
                if (placement.tail !== null) {
                    const next = `${side === 'inline-end' ? 'left' : 'right'}${placement.tail}`;
                    if (menu.getAttribute('position') !== next) menu.setAttribute('position', next);
                }
            },
        });
        this._contextPositions.set(menu, {controller, anchor: placement.anchor, surface, key, cssCap});
        // The first placement is measured before the webfont has arrived, and
        // the cap written then is the cap of a narrower menu. The positioner
        // watches the box, but a font swap is cheap to wait for by name.
        document.fonts?.ready?.then(() => {
            if (this._contextPositions?.get(menu)?.controller === controller) controller.update();
        }).catch(() => {});
        return true;
    }

    stopContextMenuPositions() {
        for (const entry of this._contextPositions?.values() || []) entry.controller.stop();
        this._contextPositions?.clear();
    }

    contextViewportEvent() {
        requestAnimationFrame(() => this.clampContextMenusToViewport());
    }

    bindContextViewport() {
        if (this._contextViewportBound) {
            return;
        }

        window.addEventListener('resize', this.contextViewportEvent);
        window.visualViewport?.addEventListener('resize', this.contextViewportEvent);
        window.visualViewport?.addEventListener('scroll', this.contextViewportEvent);
        this._contextViewportBound = true;
    }

    unbindContextViewport() {
        if (!this._contextViewportBound) {
            return;
        }

        window.removeEventListener('resize', this.contextViewportEvent);
        window.visualViewport?.removeEventListener('resize', this.contextViewportEvent);
        window.visualViewport?.removeEventListener('scroll', this.contextViewportEvent);
        this._contextViewportBound = false;
    }


    openContextMenu(event, opts = {}) {
        this.closeContextMenu(false);
        this.contextTarget?.classList.remove("active");
        const target =
            event.currentTarget instanceof Element
                ? event.currentTarget
                : event.target instanceof Element
                  ? event.target
                  : null;

        if (!target) {
            return;
        }

        // Custom-element hosts use display:contents and have no positioning box.
        // Anchor to the native control on the event path within this opener.
        const nativeTarget = (event.composedPath?.() || [])
            .filter(node => node instanceof Element)
            .map(node => node.closest?.('button, input, select, textarea, a[href], [role="button"]'))
            .find(node => node && (node === target || target.contains(node)));
        // Without a native control (for example a clicked sf-tag), anchor to the
        // first rendered box inside the opener instead of its boxless host.
        const hasBox = (node) => {
            const rect = node.getBoundingClientRect();
            return rect.width > 0 || rect.height > 0;
        };
        const boxedTarget = nativeTarget || hasBox(target)
            ? target
            : Array.from((target.shadowRoot || target).querySelectorAll?.('*') || []).find(hasBox) || target;
        this.contextTarget = nativeTarget || boxedTarget;
        this.contextTarget.classList.add("active");
        // A chip opens its menu from the label button but anchors to the chip.
        let anchor = (opts.anchorSelector && this.contextTarget.closest?.(opts.anchorSelector)) || this.contextTarget;
        let pos = anchor.getBoundingClientRect();
        event.preventDefault();
        event.stopPropagation();
        let data = {
            open: true,
            id: this.contextTarget,
            ...opts
        }
        let tempData = {};
        switch (opts.type) {
            case 'left':
                tempData = {
                    x: pos.left + pos.width,
                    y: pos.y + pos.height / 2,
                }
                break;
            case 'bottom': {
                let parent = null;
                if (opts.parent) {
                    parent = getParent(event.target);
                    pos = parent.getBoundingClientRect();
                    anchor = parent;
                }
                tempData = {
                    x: pos.left,
                    y: pos.y + pos.height,
                }
                break;
            }
            default:
                tempData = {
                    x: pos.left,
                    y: pos.y + pos.height,
                }
        }
        data = {
            ...data,
            ...tempData,
            anchor,
            anchorRef: this.describeContextAnchor(anchor),
        }
        // What «Сбросить» returns to in the column settings window: the settings
        // as they stood when it was opened. Everything in that window applies on
        // the spot now, so without this there is nothing to go back to.
        if (data.menu === 'table-settings') {
            this._columnSettingsOnOpen = JSON.parse(JSON.stringify(this.getColumnSettings() || {}));
        }
        this.set({
            contextMenu: {...data}
        });
    }

    // The element to anchor to: the native control the event passed through,
    // else the first box inside the opener, else the opener itself. A Framework
    // control's host element is display:contents and has no box of its own.
    resolveBoxedAnchor(event, target) {
        if (!(target instanceof Element)) return target;
        const hasBox = (node) => {
            const rect = node?.getBoundingClientRect?.();
            return Boolean(rect) && (rect.width > 0 || rect.height > 0);
        };
        const native = (event?.composedPath?.() || [])
            .filter((node) => node instanceof Element)
            .map((node) => node.closest?.('button, input, select, textarea, a[href], [role="button"]'))
            .find((node) => node && (node === target || target.contains(node)));
        if (native && hasBox(native)) return native;
        if (hasBox(target)) return target;
        const inside = Array.from((target.shadowRoot || target).querySelectorAll?.('*') || []).find(hasBox);
        return inside || target;
    }

    // A context menu renders Framework elements, and a page may open one before
    // their modules have arrived -- the consumer saw a template row with its
    // handle and its menu but no radio and no name, and the window's tabs
    // rendering as one list, because sf-radio and sf-tabs were not upgraded yet.
    // Nothing redrew when they were. This asks once per element and redraws when
    // it is defined; an element already defined costs nothing.
    redrawWhenComponentsUpgrade(root = this) {
        if (!root?.querySelectorAll) return this;
        this._awaitedComponents = this._awaitedComponents || new Set();
        const names = new Set(
            Array.from(root.querySelectorAll('*'))
                .map((node) => node.tagName.toLowerCase())
                .filter((name) => name.includes('-') && !customElements.get(name)),
        );
        for (const name of names) {
            if (this._awaitedComponents.has(name)) continue;
            this._awaitedComponents.add(name);
            customElements.whenDefined(name).then(() => {
                this.requestComponentUpdate('component-upgrade');
            }).catch(() => {});
        }

        return this;
    }

    openContextSubmenu(event, opts = {}) {
        event.preventDefault();
        event.stopPropagation();

        const target = event.target instanceof Element ? event.target : null;
        const anchorTarget =
            event.currentTarget instanceof Element ? event.currentTarget : target;

        if (!anchorTarget) {
            return;
        }

        // The opener is usually an sf-icon-button, whose host is display:contents
        // and has no box at all: anchoring to it put the menu at the top left of
        // the viewport, which the clamp then pushed to the window's edge -- the
        // consumer saw it open far from its button, over the admin menu. The
        // native control on the event path is the thing with a box.
        const boxed = this.resolveBoxedAnchor(event, anchorTarget);
        const rect = boxed.getBoundingClientRect();
        const anchorKey =
            opts.anchorKey ||
            opts.template?.key ||
            anchorTarget.dataset?.contextSubmenuAnchor ||
            "";

        if (
            anchorKey &&
            this.state.contextMenu?.submenu?.open &&
            this.state.contextMenu.submenu.anchorKey === anchorKey
        ) {
            this.closeContextSubmenu();
            return;
        }

        const data = {
            open: true,
            type: opts.type,
            anchorKey,
            x: rect.left + rect.width,
            y: rect.y + rect.height / 2,
            position: opts.position || 'left',
            transform: -50,
            ...opts,
            anchor: boxed,
            anchorRef: this.describeContextAnchor(boxed),
        };

        this.set((state) => ({
            contextMenu: {
                ...(state.contextMenu || {}),
                submenu: data,
            },
        }));
    }

    handleContextLayerClick(event) {
        const target = event.target;

        if (!(target instanceof Element)) {
            return;
        }

        if (target.closest("[data-context-submenu]")) {
            return;
        }

        if (target.closest("[data-context-submenu-anchor]")) {
            return;
        }

        if (
            this.state.contextMenu?.submenu?.open &&
            !target.closest("[data-context-main-menu]")
        ) {
            this.closeContextSubmenu();
        }
    }

    closeContextSubmenu() {
        this.set((state) => ({
            contextMenu: {
                ...(state.contextMenu || {}),
                submenu: null,
            },
        }));
    }

    // Every element the table draws goes through here -- its own controls,
    // the row menus, the select boxes, a host's row actions -- and each was
    // written for the ladder's step 1. A table at another step draws them at
    // its own: a size of 1 becomes the table's. An element that names no size
    // keeps its own default -- a badge's is 1/3, a tag's 1/2 -- which a table at
    // a smaller step would otherwise have grown.
    renderSmartElement(type, props = {}) {
        const size = this.size;
        if (size !== "1" && props && String(props.size) === "1") {
            return super.renderSmartElement(type, {...props, size});
        }
        return super.renderSmartElement(type, props);
    }

    get size() {
        return this.getEnumAttr("size", ["1/2", "1"], "1");
    }

    getSelectColumn() {
        return {
            ...this.TABLE_SELECT_COLUMN,
            component: {
                ...this.TABLE_SELECT_COLUMN.component,
                props: {
                    ...(this.TABLE_SELECT_COLUMN.component?.props || {}),
                    checked: Boolean(this.state?.settingsChecked),
                    indeterminate: Boolean(this.state?.selectIndeterminate),
                },
            },
        };
    }

    getColumns() {
        const dataColumns = this.getTableData().columns || [];
        const systemColumns = this.getSystemColumns();
        const systemKeys = new Set(systemColumns.map((column) => column.key));
        const rightSystemKeys = new Set(['actions']);
        const originalIndexByKey = new Map(
            dataColumns.map((column, index) => [column?.key, index]),
        );
        const userColumns = dataColumns
            .filter((column) => column?.key && !systemKeys.has(column.key))
            .map((column) => {
                const settings = this.getColumnSetting(column.key);
                const nextColumn = {
                    ...column,
                    ...settings,
                };

                if (settings.width) {
                    nextColumn.colWidth = settings.width;
                }

                return nextColumn;
            })
            .sort((left, right) => {
                const getOrder = (column) => {
                    const order = column?.order;

                    return order !== null &&
                    typeof order !== "undefined" &&
                    order !== "" &&
                    Number.isFinite(Number(order))
                        ? Number(order)
                        : originalIndexByKey.get(column?.key) ?? 0;
                };
                const leftOrder =
                    getOrder(left);
                const rightOrder =
                    getOrder(right);

                if (leftOrder === rightOrder) {
                    return (
                        (originalIndexByKey.get(left.key) ?? 0) -
                        (originalIndexByKey.get(right.key) ?? 0)
                    );
                }

                return leftOrder - rightOrder;
            });
        const leftSystemColumns = systemColumns.filter(
            (column) => !rightSystemKeys.has(column?.key),
        );
        const rightSystemColumns = systemColumns.filter((column) =>
            rightSystemKeys.has(column?.key),
        );

        // Pinned columns stand right after the box and the row menu, each group
        // in its own order. The partition is stable, so pinning changes which
        // group a column is in and nothing else about where it sits.
        const pinnedUserColumns = userColumns.filter((column) => this.isColumnPinned(column));
        const looseUserColumns = userColumns.filter((column) => !this.isColumnPinned(column));
        const columns = [
            ...leftSystemColumns,
            ...pinnedUserColumns,
            ...looseUserColumns,
            ...rightSystemColumns,
        ].filter((column) => column?.visible !== false);
        const width = this._responsiveWidth || this.clientWidth || 0;
        // The list chooses its own fields by priority and wants every column to
        // choose from; dropping columns that do not fit is the table's answer.
        if (this.isList || !width || !userColumns.some((column) => column.priority !== undefined)) return columns;
        const columnWidth = (column) => {
            const value = column.min_width ?? column.minWidth ?? column.width;
            const parsed = typeof value === "number" ? value : Number.parseFloat(String(value ?? ""));
            return Number.isFinite(parsed) && parsed > 0 ? parsed : (column.system ? 48 : 160);
        };
        const retained = new Set(columns);
        let needed = columns.reduce((sum, column) => sum + columnWidth(column), 0);
        const removable = userColumns.filter((column) => column.visible !== false)
            .sort((a, b) => (b.priority ?? 0) - (a.priority ?? 0));
        for (const column of removable) {
            if (needed <= width || retained.size <= systemColumns.length + 1) break;
            retained.delete(column);
            needed -= columnWidth(column);
        }
        return columns.filter((column) => retained.has(column));
    }

    // The columns that stay in place while the rows scroll sideways: the box,
    // the row menu, and the column the host marked as the record's title. The
    // host declares the title with `role: "title"` on the column rather than
    // the table guessing the first text column — a guess breaks on a page whose
    // first column is an icon or a flag, and the host already knows which
    // column leads the record.
    // Whether a column is pinned. The person decides, through the toggle in the
    // column settings, and the answer is stored beside the order and the width;
    // the column the host marked as the record's title starts pinned, because
    // that is the column a reader names a row by. The box and the row menu are
    // always pinned and are not offered a toggle.
    isColumnPinned(column) {
        if (!column?.key) return false;
        if (column.key === "select" || column.key === "settings") return true;
        if (column.system) return false;
        const pending = this.tempSettings?.cols?.[column.key];
        const setting = this.getColumnSetting(column.key) || {};
        const chosen = pending?.pinned ?? setting.pinned ?? column.pinned;
        if (typeof chosen === "boolean") return chosen;

        return column.role === "title";
    }

    getPinnedColumnKeys(columns = this.getColumns()) {
        return columns
            .filter((column) => column?.key && this.isColumnPinned(column))
            .map((column) => column.key);
    }

    isPinnedColumn(column) {
        if (!column?.key) return false;
        if (["start", "end"].includes(column.sticky)) return true;
        return this.isColumnPinned(column);
    }

    // A pinned cell's offset is read from the page, not from the declared
    // widths. The box and the row menu declare their widths as `calc()` over
    // tokens, which no arithmetic here can resolve, and a column the person has
    // resized no longer has the width its description names. The measured
    // offsets are published as custom properties, so the cells themselves only
    // ever name a property.
    getStickyCellStyle(columns, index) {
        const column = columns[index];
        const pinned = this.isPinnedColumn(column);
        if (!pinned) return "";
        const side = ["start", "end"].includes(column.sticky) ? column.sticky : "start";
        // Only the placing is inline. The colour is a rule, because a pinned
        // cell has to carry the colour of the row it is in: painting it here
        // beat every row state and left the pinned columns white under a
        // selected or hovered row.
        return `position: sticky; inset-inline-${side}: var(--sf-table-pin-${column.key}, 0px); z-index: 2;`;
    }

    measurePinnedColumns() {
        const table = this.refs.table?.value;
        const scroll = this.refs.scroll?.value;
        if (!table) return this;
        const keys = this.getPinnedColumnKeys();
        const head = table.querySelector("thead tr");
        let offset = 0;
        for (const key of keys) {
            table.style.setProperty(`--sf-table-pin-${key}`, `${Math.round(offset)}px`);
            const cell = head?.querySelector(`th[data-key="${key}"]`);
            offset += cell ? cell.getBoundingClientRect().width : 0;
        }
        const last = keys[keys.length - 1];
        for (const cell of table.querySelectorAll("[data-sf-pin-edge]")) {
            cell.removeAttribute("data-sf-pin-edge");
        }
        if (last) {
            for (const cell of table.querySelectorAll(`[data-key="${last}"]`)) {
                cell.setAttribute("data-sf-pin-edge", "");
            }
        }
        // The shadow only means something once something is hidden behind the
        // pinned block, so it follows the scroll position. In a right-to-left
        // table the browser reports that position as a negative number.
        if (scroll) {
            const scrolled = Math.abs(scroll.scrollLeft) > 0;
            if (scrolled) scroll.setAttribute("data-sf-pin-scrolled", "");
            else scroll.removeAttribute("data-sf-pin-scrolled");
        }
        return this;
    }

    bindPinnedColumnScroll() {
        const scroll = this.refs.scroll?.value;
        if (!scroll || this._pinnedScrollBound === scroll) return this;
        this._pinnedScrollBound = scroll;
        scroll.addEventListener("scroll", () => this.measurePinnedColumns(), {passive: true});
        return this;
    }

    // A row's select box has no visible label: the row is the label. A reader
    // moving through the column hears each box by the row's first data cell,
    // instead of a run of unnamed checkboxes it cannot tell apart.
    getRowSelectLabel(row) {
        const column = this.getColumnItems().find((item) => item?.key && !item.system && item.key !== "select" && item.key !== "settings");
        const raw = column ? row?.[column.key] : undefined;
        const value = raw !== null && typeof raw === "object" ? (raw.text ?? raw.label ?? raw.value ?? "") : raw;
        const text = String(value ?? "").trim() || String(row?.id ?? "").trim();
        return text ? `${this.text("selectRowLabel")}: ${text}` : this.text("selectRowLabel");
    }

    createRowComponentCell(component, props = {}) {
        if (!component?.type) {
            return null;
        }

        return {
            component: {
                type: component.type,
                props: {
                    ...(component.props || {}),
                    ...props,
                },
            },
        };
    }

    // The row's menu is the Framework's standard menu (owner, 2026-10-12): it
    // opens below its button and start-aligned, in the top layer, and a group
    // of the host's row_actions is a real submenu rather than a second menu
    // swapped in over the first. What a choice does is unchanged -- an intent
    // for the row, or the action's own handler where it has one.
    async openRowMenu(event, row = {}) {
        const anchor = event?.currentTarget?.querySelector?.("button")
            ?? event?.target?.closest?.("button")
            ?? event?.currentTarget;
        if (!(anchor instanceof Element)) return null;
        const selection = Array.isArray(row.actions) && row.actions.every((item) => typeof item === "string")
            ? row.actions
            : null;
        const items = this.getActionRowItems(row);
        const iconOf = (action) => action?.icon ?? action?.component?.props?.icon ?? undefined;
        const entries = items.map((action) => (Array.isArray(action.members)
            ? {
                id: action.id, text: action.name, icon: iconOf(action),
                submenu: action.members
                    .filter((member) => !selection || selection.includes(member.id))
                    .map((member) => ({id: member.id, text: member.name, icon: iconOf(member), danger: member.danger === true})),
            }
            : {id: action.id, text: action.name, icon: iconOf(action), danger: action.danger === true}));
        // The standard menu is a lazy relation of the table: fetched after the
        // page is ready, and asked for here if a press comes first.
        if (!customElements.get("sf-context-menu")) {
            globalThis.SF?.Loader?.ensurePlugin?.("cl-context-menu");
            await Promise.race([customElements.whenDefined("sf-context-menu"), new Promise((settle) => setTimeout(settle, 3000))]);
        }
        const menu = this.rowMenu();
        menu.setAttribute("aria-label", this.getContextMenuLabel({row, menu: "row-settings"}));
        menu.entries = entries;
        this._rowMenuContext = {row, items, anchor};
        return menu.toggleFor(anchor);
    }

    rowMenu() {
        if (this._rowMenu?.isConnected) return this._rowMenu;
        const menu = document.createElement("sf-context-menu");
        menu.setAttribute("popup", "");
        menu.dataset.sfTableMenu = "row";
        menu.addEventListener("sf-context-menu-select", (event) => {
            const context = this._rowMenuContext;
            if (!context) return;
            const id = event.detail?.id;
            const {row, items, anchor} = context;
            const action = items.find((item) => item.id === id);
            const member = action ? null : items.flatMap((item) => item.members ?? []).find((item) => item.id === id);
            if (member) {
                this.requestActionIntent(member.id, [row.id]);
                return;
            }
            const handler = action?.component?.props?.["@click"] ?? action?.component?.props?.onClick;
            if (typeof handler === "function") handler({currentTarget: anchor, target: anchor, preventDefault() {}, stopPropagation() {}});
            else if (action) this.requestActionIntent(action.id, [row.id]);
        });
        (this.closest("dialog[open]") || document.body).append(menu);
        this._rowMenu = menu;
        return menu;
    }

    // Which of the table's actions this row offers, and in what shape.
    //
    // `row.actions` is read twice over a row's life and means two things. From
    // a host it is ids -- `["view"]`, the data view port's word for which
    // actions apply to this record -- and that is what selects from the table's
    // own list. Once the cell is built it holds the built items, and the
    // template calls this again with them, so the filter was asking whether an
    // array of objects includes a string. It never does: every action was
    // filtered out and the «Действия» column drew an empty container in every
    // row of every list, page lists included. A consumer reported it on an
    // embedded one, which is only where they happened to be looking (owner,
    // 2026-10-09).
    //
    // Ids select. Anything else is already the selection and is not filtered
    // again.
    // The same items for the same row, so a render finds the elements it made
    // last time rather than new ones.
    //
    // A re-query brings new row objects for the same records, so a cache held
    // on the row produced new items every time and lit rebuilt every actions
    // cell -- which is exactly what tests/browser/table-round-3 exists to
    // refuse: it used to tear every cell out and build it again, and the
    // control a person was using went with them. The key is what the cell
    // actually depends on: the row, the ids it offers, and the list those ids
    // select from (owner, 2026-10-09).
    rowActionItems(row = {}) {
        const ids = Array.isArray(row?.actions)
            && row.actions.every((item) => typeof item === "string")
            ? row.actions.join(",")
            : "*";
        const list = this.ACTIONS_SETTINGS_ROW ?? [];
        const listKey = list.map((action) => action?.id ?? "").join(",");
        if (this._rowActionListKey !== listKey) {
            this._rowActionListKey = listKey;
            this._rowActionItems = new Map();
        }
        const key = `${row?.id ?? ""}|${ids}`;
        if (!this._rowActionItems) this._rowActionItems = new Map();
        if (!this._rowActionItems.has(key)) {
            this._rowActionItems.set(key, this.getActionRowItems(row));
        }
        return this._rowActionItems.get(key);
    }

    getActionRowItems(row = {}, actions = this.ACTIONS_SETTINGS_ROW) {
        const selection = Array.isArray(row.actions)
            && row.actions.every((item) => typeof item === "string")
            ? row.actions
            : null;
        // A group (the host's row_actions, port 1.8.0) is offered when the
        // record allows any of its members, and opens a submenu of exactly
        // those: «Опубликовать › ru» on a row drafted in ru, not in en.
        const allowedMembers = (action) => (action.members || [])
            .filter((member) => !selection || selection.includes(member.id));
        return actions.filter((action) => (Array.isArray(action.members)
            ? allowedMembers(action).length > 0
            : !selection || selection.includes(action.id))).map((action) => {
            if (Array.isArray(action.members)) {
                const members = allowedMembers(action);
                const props = {...(action.component?.props || {})};
                props["@click"] = (event) => this.openContextMenu(event, {
                    type: "left", row, action, menu: "row-settings",
                    items: members.map((member) => ({
                        component: "sf-button",
                        props: {
                            appearance: "link",
                            scheme: member.danger ? "error" : "on-surface",
                            text: member.name,
                            "@click": () => {
                                this.requestActionIntent(member.id, [row.id]);
                                this.closeContextMenu();
                            },
                        },
                    })),
                });
                return {...action, component: {...(action.component || {}), props}};
            }
            const component = action.component || {};
            const props = {
                ...(action.props || {}),
                ...(component.props || {}),
            };
            const menu = action.menu || props.menu;
            const hasClickHandler =
                typeof props["@click"] === "function" ||
                typeof props.onClick === "function";

            if (menu && !hasClickHandler) {
                props["@click"] = (event) => {
                    const menuOptions =
                        menu && typeof menu === "object"
                            ? menu
                            : {menu};

                    this.openContextMenu(event, {
                        type: "left",
                        row,
                        action,
                        ...menuOptions,
                    });
                };
            } else if (!hasClickHandler) {
                props["@click"] = () => this.requestActionIntent(action.id, [row.id]);
            }

            return {
                ...action,
                component: {
                    ...component,
                    props,
                },
            };
        });
    }

    getDefaultRowCell(column, row) {
        if (!column?.key) {
            return null;
        }
        if (column.key === "select") {
            return this.createRowComponentCell(column.rowComponent, {
                checked: this.state.settingsChecked ? true : Boolean(row?.selected || row?.checked),
                value: row?.id ?? row?.value ?? "",
                ariaLabel: this.getRowSelectLabel(row),
                "@change": (event) => {
                    this.applyRowSelection(row.id, Boolean(event.target.checked), {
                        range: this._selectRangeModifier === true,
                    });
                },
            });
        }

        if (column.key === "settings") {
            return this.createRowComponentCell(column.rowComponent || {
                type: "icon-button",
                props: {
                    size: "1",
                    appearance: "link",
                    scheme: "on-surface",
                    icon: "menu",
                    ariaLabel: this.text("menuLabel"),
                    value: "menu",
                    "@click": (e) => this.openRowMenu(e, row),
                },
            });
        }
        if (column.key === 'actions') {
            return this.getActionRowItems(row);
        }

        return null;
    }

    normalizeRow(row = {}) {
        if (!row || typeof row !== "object" || Array.isArray(row)) {
            return row;
        }

        const nextRow = {...row};

        this.getSystemColumns().forEach((column) => {

            if (!column?.key || typeof nextRow[column.key] !== "undefined" || column.visible === false) {
                return;
            }

            const cell = this.getDefaultRowCell(column, nextRow);
            if (cell) {
                nextRow[column.key] = cell;
            }
        });

        return nextRow;
    }

    normalizeRows(rows = []) {
        return Array.isArray(rows) ? rows.map((row) => this.normalizeRow(row)) : [];
    }

    setTableData(patch = {}, reason = "data") {
        const currentData = this.getTableData();
        const nextPatch =
            typeof patch === "function" ? patch(currentData) : patch;

        if (!nextPatch || typeof nextPatch !== "object") {
            return this;
        }

        const result = this.set(
            {
                data: {
                    ...currentData,
                    ...nextPatch,
                },
            },
            reason,
        );
        this.syncSelectionPort();
        // New rows are new selection: a tree that redrew its rows after a bulk
        // write left the page box half-ticked over rows none of which were.
        if (Object.prototype.hasOwnProperty.call(nextPatch, "rows")) this.syncSelectAllState();
        return result;
    }

    setColumns(columns = [], reason = "columns") {
        const currentColumns = this.getTableData().columns || [];
        const nextColumns =
            typeof columns === "function" ? columns(currentColumns) : columns;

        return this.setTableData(
            {
                columns: Array.isArray(nextColumns) ? nextColumns : [],
            },
            reason,
        );
    }

    addColumns(columns = []) {
        const nextColumns = Array.isArray(columns) ? columns : [columns];

        return this.setColumns((currentColumns) => [
            ...currentColumns,
            ...nextColumns.filter(Boolean),
        ]);
    }

    updateColumn(columnKey, patch = {}, reason = "columns") {
        if (!columnKey) {
            return this;
        }

        return this.setColumns(
            (columns) =>
                columns.map((column, index) => {
                    if (!column || column.key !== columnKey) {
                        return column;
                    }

                    const nextPatch =
                        typeof patch === "function" ? patch(column, index) : patch;

                    return {
                        ...column,
                        ...(nextPatch || {}),
                    };
                }),
            reason,
        );
    }

    setRows(rows = [], reason = "rows") {
        const currentRows = this.getTableData().rows || [];
        const nextRows = typeof rows === "function" ? rows(currentRows) : rows;
        return this.setTableData(
            {
                rows: this.normalizeRows(nextRows),
            },
            reason,
        );
    }

    addRows(rows = [], reason = "rows") {
        const nextRows = Array.isArray(rows) ? rows : [rows];

        return this.setRows((currentRows) => [
            ...currentRows,
            ...nextRows.filter(Boolean),
        ], reason);
    }

    updateRowsByKey(key, value, patch = {}, reason = "rows") {
        if (!key) {
            return this;
        }

        const patchRow = typeof patch === "function"
            ? patch
            : (row) => ({
                ...row,
                ...(patch || {}),
            });

        return this.setRows((rows) =>
                rows.map((row, index) => {
                    if (!row || row[key] !== value) {
                        return row;
                    }

                    const nextRow = patchRow(row, index);

                    return nextRow && typeof nextRow === "object" ? nextRow : row;
                }),
            reason,
        );
    }

    updateRowByKey(key, value, patch = {}, reason = "row") {
        return this.updateRowsByKey(key, value, patch, reason);
    }

    updateRows(patch = {}, reason = "rows") {
        const patchRow = typeof patch === "function"
            ? patch
            : (row) => ({
                ...row,
                ...(patch || {}),
            });

        return this.setRows((rows) =>
                rows.map((row, index) => {
                    const nextRow = patchRow(row, index);

                    return nextRow && typeof nextRow === "object" ? nextRow : row;
                }),
            reason,
        );
    }

    updateRowCell(rowKey, rowValue, cellKey, patch = {}, reason = "cell") {
        if (!cellKey) {
            return this;
        }

        return this.updateRowsByKey(
            rowKey,
            rowValue,
            (row) => {
                const currentCell =
                    row && row[cellKey] && typeof row[cellKey] === "object"
                        ? row[cellKey]
                        : {};
                const nextCellPatch =
                    typeof patch === "function" ? patch(currentCell, row) : patch;

                return {
                    ...row,
                    [cellKey]: {
                        ...currentCell,
                        ...(nextCellPatch || {}),
                    },
                };
            },
            reason,
        );
    }

    updateRowCellProps(rowKey, rowValue, cellKey, props = {}, reason = "cell-props") {
        return this.updateRowCell(
            rowKey,
            rowValue,
            cellKey,
            (cell) => ({
                component: {
                    ...(cell.component || {}),
                    props: {
                        ...(cell.component?.props || {}),
                        ...(typeof props === "function"
                            ? props(cell.component?.props || {}, cell)
                            : props || {}),
                    },
                },
            }),
            reason,
        );
    }

    closeContextMenu(restoreFocus = true) {
        const trigger = this.contextTarget;
        this.set({
            contextMenu: {
                open: false,
            }
        });
        this.stopContextMenuPositions();
        render(null, this.getPortalContainer());
        this.hidePortal();
        if (this.refs.contextMenu) {
            this.refs.contextMenu.value = null;
        }
        this.unbindContextEvent();
        this.unbindContextViewport();
        this.contextTarget?.classList.remove("active");
        this.contextTarget = null;

        if (restoreFocus && trigger?.isConnected && typeof trigger.focus === 'function') {
            requestAnimationFrame(() => trigger.focus());
        }
    }

    get templateName() {
        return this.getAttribute("template") || "default";
    }

    get dataState() {
        return this.getEnumAttr(
            'data-state',
            ['loading', 'populated', 'empty', 'error'],
            'auto',
        );
    }

    setDataState(state = 'auto') {
        const normalized = ['loading', 'populated', 'empty', 'error'].includes(state)
            ? state
            : 'auto';

        if (normalized === 'auto') {
            this.removeAttribute('data-state');
        } else {
            this.setAttribute('data-state', normalized);
        }

        return this;
    }

    requestRetry() {
        return this.dispatchTableEvent('sf-table-retry');
    }

    disconnectedCallback() {
        // The row menu lives beside the page, not inside the table: it goes
        // with the table that made it.
        this._rowMenu?.close?.({restoreFocus: false});
        this._rowMenu?.remove();
        this._rowMenu = null;
        for (const page of this._filterOptionPages.values()) {
            page.controller?.abort();
            clearTimeout(page.searchTimer);
        }
        this._filterOptionPages.clear();
        this._responsiveObserver?.disconnect();
        this._responsiveObserver = null;
        this._responsiveWidth = 0;
        this.clearSearchDebounce();
        this._searchPendingProps = null;
        this._searchHoldKey = '';
        this.clearFilterTemplateDragBindings();
        this.stopContextMenuPositions();
        this.unbindContextEvent();
        this.unbindContextViewport();
        this.unBindHoverEvent();
        this.unbindColumnResizeEvent();
        this.unbindColumnDragEvent();
        this.clearColumnDragState(false);
        this.onColumnResizePointerUp();
        this.tempSettings = null;
        this._selectionPortKey = "[]";
        // A removed table abandons its pending answer; a remount asks again.
        this._abandonedQuery = this._pendingQuerySequence !== null;
        this._pendingQuerySequence = null;
        this.state.contextMenu = {open: false};
        this.state.settingsChecked = false;
        this.state.selectIndeterminate = false;
        const data = this.getTableData();
        this.state.data = {...data, rows: data.rows.map((row) => ({
            ...row,
            selected: false,
            checked: false,
            ...(row.select?.component ? {select: {
                ...row.select,
                component: {...row.select.component, props: {
                    ...row.select.component.props, checked: false,
                }},
            }} : {}),
        }))};
        if (this.refs.contextMenu) {
            this.refs.contextMenu.value = null;
        }
        this.contextTarget?.classList?.remove('active');
        this.contextTarget = null;
        if (this._portalContainer) {
            render(null, this._portalContainer);
            this._portalContainer.remove();
            this._portalContainer = null;
        }
        super.disconnectedCallback();
    }

    /**
     * One of this table's words, in the page's language. An attribute wins over
     * both languages, because a consumer who names a word has named it.
     *
     * The declared default is the English one, so a component read through its
     * props alone still says something sensible; the context below overrides
     * every text key with this, which is what makes the Russian reachable. A
     * key left out of that override would quietly fall back to English on a
     * Russian page -- which is exactly how three labels in the record panel's
     * head came to read «Close» beside «В избранное».
     */
    text(key, attribute) {
        return (attribute && this.getAttribute(attribute)) || pageText(TABLE_TEXT, key, this);
    }

    templateContext() {
        this._renderedCramped = this.columnsAreCramped;
        const props = this.getPropsContext();

        return this.createTemplateContext({
            ...props,
            component: this,
            list: this.isList,
            rootClass: this.getRootClass(),
            rootStyle: this.getRootStyle(),
            filterText: this.text("filterText", "filter-text"),
            clearText: this.text("clearText", "clear-text"),
            searchPlaceholder: this.text("searchPlaceholder", "search-placeholder"),
            createText: this.text("createText", "create-text"),
            pinColumnLabel: this.text("pinColumnLabel", "pin-column-label"),
            unpinColumnLabel: this.text("unpinColumnLabel", "unpin-column-label"),
            loadingText: this.text("loadingText", "loading-text"),
            emptyText: this.text("emptyText", "empty-text"),
            errorText: this.text("errorText", "error-text"),
            retryText: this.text("retryText", "retry-text"),
            columnMoveLabel: this.text("columnMoveLabel", "column-move-label"),
            filterApplyText: this.text("filterApplyText", "filter-apply-text"),
            columnSearchPlaceholder: this.text("columnSearchPlaceholder", "column-search-placeholder"),
            columnSearchEmptyText: this.text("columnSearchEmptyText", "column-search-empty-text"),
        });
    }

    setCheckboxHeight() {
        if (!this.refs.items.size) return;
        const measureToken = ++this._checkboxMeasureToken;
        requestAnimationFrame(() => {
            if (!this.isConnected || measureToken !== this._checkboxMeasureToken) {
                return;
            }

            const parentSpace = setParentSpace(this.refs.items);
            if (!parentSpace) {
                return;
            }

            const {item, gap, parent} = parentSpace;
            this.parentGap = gap;
            const itemHeight = item.tagName.toLowerCase().startsWith('sf-')
                ? item.children[0]?.clientHeight
                : item.clientHeight;
            if (!itemHeight) {
                return;
            }
            const itemSize = parent.dataset.size || 8;
            const parentHeight = (itemHeight + gap) * itemSize;

            if (parent.clientHeight <= parentHeight) {
                return false;
            }
            parent.setAttribute('style', `height: ${parentHeight}px; overflow: auto;`)
        })
    }


    beforeRender() {
        super.beforeRender();
        this._checkboxMeasureToken++;
        this.refs.items = new Map();
    }

    afterRender() {
        // Content widths first: they change the widths everything below reads.
        // Twice at most in a row, as the cramped check below, so content that
        // sits on an edge cannot keep the table re-rendering.
        if (this.measureContentWidths() && (this._contentRetries ?? 0) < 2) {
            this._contentRetries = (this._contentRetries ?? 0) + 1;
            this.requestComponentUpdate("content-widths");
            return;
        }
        this._contentRetries = 0;
        this.measurePinnedColumns();
        // Whether the columns are cramped is read from the laid-out table, so
        // the first render guesses: before layout the actions column and the
        // floors are not yet where they will be. When the answer after the
        // render is not the one the render used, render once more.
        // Twice at most in a row, so a width that sits on the edge cannot keep
        // the table re-rendering.
        if (this.columnsAreCramped !== this._renderedCramped && (this._crampedRetries ?? 0) < 2) {
            this._crampedRetries = (this._crampedRetries ?? 0) + 1;
            this.requestComponentUpdate("cramped-columns");
        } else {
            this._crampedRetries = 0;
        }
        this.bindPinnedColumnScroll();
        this.setCheckboxHeight();
        this.bindHoverEvent();
        this.bindColumnResizeEvent();
        this.bindColumnDragEvent();
    }

    template() {
        return renderTableTemplate(this.templateContext());
    }
}

SfTable.define("sf-table");
