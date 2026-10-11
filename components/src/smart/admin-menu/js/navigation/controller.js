// The admin menu that replaced the admin's top bar, driven by
// simai.admin-navigation-port (larena-specs frontend-interface-program,
// sf-admin-menu assignment, mockup v5).
//
// sf-admin-menu draws this when a host answers it with a navigation port, and
// its older `items` menu otherwise. Both live in one element on purpose: a
// fifth element would have had to be added across the build, the package
// reference and the locks, and retired the same way, while the `items` menu
// has to keep working for Larena until it connects the port. The `items` path
// goes when Larena has switched (owner, 2026-10-11).
//
// The host owns everything a person sees here -- sites, rows, profiles,
// favourites, the person -- and this controller owns only where the person is:
// which root is open, which section is unfolded, what they typed into «Найти в
// разделе», whether the menu is on its rail. Every change leaves through the
// port, and nothing the host said is kept past the next answer.
import {pageText} from "../../../../core/js/page-language";

export const NAVIGATION_PORT = "simai.admin-navigation-port";

// Every port version this shell accepts, in one place, the way sf-data-view and
// sf-property-view keep theirs.
export const SUPPORTED_NAVIGATION_VERSIONS = ["1.0.0"];

// The width at which two columns become two screens. The same query
// sf-property-view and sf-drawer switch on, written the same way, so a reader
// grepping 48rem finds all three.
const WIDE_MEDIA_QUERY = "(min-width: 48rem)";

// «Найти в разделе» is offered once a section has more rows than fit at a
// glance (assignment, section 3).
const FIND_IN_SECTION_FROM = 7;

// How many favourites show before the block folds (assignment, section 2).
const FAVOURITES_SHOWN = 5;

// How long typing may pause before the palette asks the host. Short enough to
// feel immediate; long enough that a word typed at speed is one question.
const PALETTE_PAUSE_MS = 120;

// ⌘K on a Mac, Ctrl K elsewhere: the hint says the keys this keyboard has.
const IS_MAC = /Mac|iPhone|iPad/u.test(globalThis.navigator?.platform ?? globalThis.navigator?.userAgent ?? "");
export const PALETTE_SHORTCUT = IS_MAC ? "⌘K" : "Ctrl K";

// The id prefix of the lists sf-sortable reorders in the edit mode.
export const LIST_ID = "sf-admin-nav-list-";

export const NAVIGATION_TEXT = {
    en: {
        loading: "Loading the menu…",
        unavailable: "The menu is not available right now.",
        retry: "Try again",
        search: "Search",
        favourites: "Favourites",
        fold: "fold",
        unfold: "show all",
        notifications: "Notifications",
        collapse: "Collapse",
        expand: "Expand",
        hideColumn: "Hide column",
        back: "Back",
        allSites: "Shared by all sites",
        findInSection: "Find in section",
        nothingFound: "Nothing found",
        star: "Add to favourites",
        unstar: "Remove from favourites",
        chooseSite: "Choose a site",
        findSite: "Find a site",
        manageSites: "Manage sites",
        moreSites: "Show more",
        external: "opens in a new tab",
        paletteHint: "Sections, records, actions…",
        paletteNavigate: "choose",
        paletteOpen: "open",
        paletteClose: "close",
        account: "Account",
        theme: "Theme",
        themeLight: "Light",
        themeDark: "Dark",
        themeSystem: "As the system",
        language: "Language",
        findLanguage: "Find a language",
        languageNote: "The admin's own language. The site's languages are in the site settings.",
        menuProfile: "Menu profile",
        signOut: "Sign out",
        editMenu: "Edit menu",
        configureMenu: "Configure the menu",
        done: "Done",
        profileLabel: "Profile:",
        notePersonal: "Your profile: it changes for you alone. Applied at once.",
        noteShared: "Shared profile: it changes for everyone on the site. Applied at once.",
        resetToPackages: "Reset to the packages",
        newProfile: "New profile…",
        renameProfile: "Rename",
        deleteProfile: "Delete",
        sharedProfile: "For everyone on the site",
        create: "Create",
        cancel: "Cancel",
        save: "Save",
        show: "Show",
        hide: "Hide",
        more: "More",
        rename: "Rename",
        packageName: "Return the package's name",
        deleteRow: "Delete",
        drag: "Move",
        add: "Add",
        addDivider: "Divider",
        addHeading: "Group label",
        addLink: "Link",
        linkLabel: "Name",
        linkHref: "Address",
        newHeading: "New group",
        conflict: "The menu was changed elsewhere. This is what is saved now.",
        personalName: "Personal",
    },
    ru: {
        loading: "Загружаем меню…",
        unavailable: "Меню сейчас недоступно.",
        retry: "Повторить",
        search: "Поиск",
        favourites: "Избранное",
        fold: "свернуть",
        unfold: "показать все",
        notifications: "Уведомления",
        collapse: "Свернуть",
        expand: "Развернуть",
        hideColumn: "Скрыть колонку",
        back: "Назад",
        allSites: "Общее для всех сайтов",
        findInSection: "Найти в разделе",
        nothingFound: "Ничего не найдено",
        star: "В избранное",
        unstar: "Убрать из избранного",
        chooseSite: "Выбрать сайт",
        findSite: "Найти сайт",
        manageSites: "Управлять сайтами",
        moreSites: "Показать ещё",
        external: "откроется в новой вкладке",
        paletteHint: "Разделы, записи, действия…",
        paletteNavigate: "выбрать",
        paletteOpen: "открыть",
        paletteClose: "закрыть",
        account: "Профиль пользователя",
        theme: "Тема",
        themeLight: "Светлая",
        themeDark: "Тёмная",
        themeSystem: "Как в системе",
        language: "Язык",
        findLanguage: "Найти язык",
        languageNote: "Язык интерфейса админки. Языки сайта — в настройках сайта.",
        menuProfile: "Профиль меню",
        signOut: "Выйти",
        editMenu: "Правка меню",
        configureMenu: "Настроить меню",
        done: "Готово",
        profileLabel: "Профиль:",
        notePersonal: "Ваш профиль: меняется только у вас. Применяется сразу.",
        noteShared: "Общий профиль: меняется у всех на сайте. Применяется сразу.",
        resetToPackages: "Сбросить к пакетам",
        newProfile: "Новый профиль…",
        renameProfile: "Переименовать",
        deleteProfile: "Удалить",
        sharedProfile: "Для всех на сайте",
        create: "Создать",
        cancel: "Отменить",
        save: "Сохранить",
        show: "Показать",
        hide: "Скрыть",
        more: "Ещё",
        rename: "Переименовать",
        packageName: "Вернуть название пакета",
        deleteRow: "Удалить",
        drag: "Переместить",
        add: "Добавить",
        addDivider: "Разделитель",
        addHeading: "Подпись группы",
        addLink: "Ссылка",
        linkLabel: "Название",
        linkHref: "Адрес",
        newHeading: "Новая группа",
        conflict: "Меню изменили в другом месте. Показано то, что сохранено сейчас.",
        personalName: "Персональный",
    },
};

const copy = (value) => (value === undefined ? undefined : JSON.parse(JSON.stringify(value)));

export class NavigationController {
    constructor(element, port) {
        this.element = element;
        this.port = port;
        this.navigation = null;
        this.status = "loading";
        this.message = "";
        this.openRoot = null;
        this.unfolded = new Set();
        this.filter = "";
        this.favouritesFolded = false;
        this.compact = false;
        this.screen = "first";
        this.sequence = 0;
        this.sites = {open: false, query: "", items: [], next: null, loading: false, sequence: 0};
        // The ⌘K palette: what was typed, what the host answered for it, which
        // result is under the keyboard, and where focus goes back to.
        this.palette = {open: false, query: "", groups: [], scope: "", active: 0, loading: false, sequence: 0};
        this._paletteOpener = null;
        this._paletteTimer = null;
        // The profile menu at the bottom: open or not, which of its three views
        // is showing, and what was typed into «Найти язык».
        this.account = {open: false, view: "menu", query: ""};
        // The edit mode: on or off, what is being renamed or added, the row whose
        // ⋯ is open, the profile list in the banner, and what the last save said.
        this.edit = {on: false, renaming: null, adding: null, rowMenu: null, profiles: false, naming: null, message: ""};
        this._onSortableIntent = (event) => this.onSortableIntent(event);
        element.addEventListener("sf-sortable-intent", this._onSortableIntent);

        this.wideMedia = typeof globalThis.matchMedia === "function"
            ? globalThis.matchMedia(WIDE_MEDIA_QUERY)
            : null;
        this.wide = this.wideMedia ? this.wideMedia.matches : true;
        this._onWideChange = (event) => {
            this.wide = event.matches;
            // Screens are a phone idea; a desktop shows both columns at once.
            if (this.wide) this.screen = "first";
            this.update("viewport");
        };
        this.wideMedia?.addEventListener?.("change", this._onWideChange);

        this._onDocumentPointer = (event) => {
            const path = event.composedPath();
            const within = (name) => path.some((node) => node?.classList?.contains?.(name));
            if (this.sites.open && !within("sf-admin-nav-site")) this.closeSites();
            if (this.account.open && !within("sf-admin-nav-account")) this.closeAccount();
        };
        this._onKeyDown = (event) => {
            // ⌘K / Ctrl+K opens the palette from anywhere on the page, in every
            // arrangement of the menu -- the rail and the phone included, which
            // is the reason it is a palette over the page rather than a column.
            if ((event.metaKey || event.ctrlKey) && !event.altKey && !event.shiftKey
                && String(event.key).toLowerCase() === "k" && this.offers("search")) {
                event.preventDefault();
                if (this.palette.open) this.closePalette();
                else this.openPalette();
                return;
            }
            if (event.key === "Escape" && this.palette.open) {
                event.preventDefault();
                event.stopPropagation();
                this.closePalette();
                return;
            }
            if (event.key === "Escape" && this.account.open) {
                event.stopPropagation();
                // Esc steps back out of the language or profile list first, then
                // closes the menu: the same way out as the arrow on the screen.
                if (this.account.view !== "menu") this.accountView("menu");
                else this.closeAccount({focusTrigger: true});
                return;
            }
            if (event.key === "Escape" && this.sites.open) {
                event.stopPropagation();
                this.closeSites({focusTrigger: true});
            }
        };
        document.addEventListener("pointerdown", this._onDocumentPointer, true);
        document.addEventListener("keydown", this._onKeyDown, true);
    }

    dispose() {
        clearTimeout(this._paletteTimer);
        this.element.removeEventListener("sf-sortable-intent", this._onSortableIntent);
        this.wideMedia?.removeEventListener?.("change", this._onWideChange);
        document.removeEventListener("pointerdown", this._onDocumentPointer, true);
        document.removeEventListener("keydown", this._onKeyDown, true);
        this.wideMedia = null;
    }

    text(key) {
        return pageText(NAVIGATION_TEXT, key, this.element, "sf-admin-menu");
    }

    update(reason) {
        this.element.requestComponentUpdate(`navigation-${reason}`);
        if (this.palette?.open) requestAnimationFrame(() => this.syncPaletteField());
    }

    // The field is sf-input's own input, so the listbox it drives is named on
    // that element rather than on the wrapper a screen reader never focuses.
    syncPaletteField() {
        const input = this.element.querySelector(".sf-admin-nav-palette-field input");
        if (!input) return;
        input.setAttribute("role", "combobox");
        input.setAttribute("aria-expanded", "true");
        input.setAttribute("aria-controls", "sf-admin-nav-palette-results");
        input.setAttribute("aria-autocomplete", "list");
        input.setAttribute("aria-label", this.text("search"));
        const count = this.paletteResults().length;
        if (count) input.setAttribute("aria-activedescendant", `sf-admin-nav-palette-option-${this.palette.active}`);
        else input.removeAttribute("aria-activedescendant");
    }

    offers(capability) {
        return Boolean(this.port?.capabilities?.includes?.(capability));
    }

    // A host that throws is a host that did not answer; a person sees the same
    // «unavailable» as for a host that said so.
    // Every intent is also announced as sf-admin-menu-intent on the element,
    // which is the event the port names for all of them: a page can watch what a
    // person asked of the host without standing between the two.
    async raise(intent, payload = {}) {
        this.element.dispatchEvent(new CustomEvent("sf-admin-menu-intent", {
            bubbles: true,
            composed: true,
            detail: {intent, payload: copy(payload)},
        }));
        try {
            const answer = await this.port.raise(intent, copy(payload), {});
            return answer && typeof answer === "object" ? answer : {answer: "unavailable"};
        } catch (error) {
            return {answer: "unavailable", reason: String(error?.message ?? error)};
        }
    }

    // The menu as the host sees it. An answer to an older request than the last
    // one sent is dropped by its echoed sequence: switching sites twice quickly
    // must not end on the first site's rows.
    async load(reason = "connected", site = null) {
        const sequence = ++this.sequence;
        if (!this.navigation) this.status = "loading";
        this.update("loading");
        const answer = await this.raise("navigation.load", {reason, site, sequence});
        this.accept(answer, sequence);
    }

    accept(answer, sequence) {
        if (answer.answer === "applied") {
            const echoed = answer.echo?.sequence ?? sequence;
            if (echoed !== null && echoed < this.sequence) return;
            const first = !this.navigation;
            this.navigation = answer;
            if (first) this.applyPersonPreferences(answer.person);
            // On a wide screen the menu opens where the page is: the root that
            // holds the current row shows its column, as a person who came
            // through it last left it. Only on the first answer -- a root the
            // person closed stays closed when the menu is read again.
            if (first && this.wide && !this.openRoot && !answer.person?.compact) {
                const holder = this.roots().find((root) => this.holdsCurrent(root)
                    && root.key !== answer.current && (root.children ?? []).length > 0);
                if (holder) this.openRoot = holder.key;
            }
            this.status = "ready";
            this.message = "";
            this.compact = Boolean(answer.person?.compact);
            // An open root the new menu does not hold closes rather than showing
            // an empty column under a heading that no longer exists.
            if (this.openRoot && !this.roots().some((root) => root.key === this.openRoot)) {
                this.openRoot = null;
                this.screen = "first";
            }
        } else if (!this.navigation) {
            this.status = answer.answer === "refused" ? "refused" : "unavailable";
            this.message = answer.reason || "";
        }
        this.update("state");
    }

    // The person's theme and language are the host's to keep, so the page follows
    // them when the menu first reads them. Without this a page could open dark
    // from the Framework's own cookie while the host -- and the profile menu --
    // said «as the system», two answers to one question.
    applyPersonPreferences(person) {
        if (!person || !this.offers("preferences")) return;
        const loader = globalThis.SF?.Loader;
        if (person.theme && loader?.themePreference !== person.theme) loader?.setTheme?.(person.theme);
        const language = globalThis.SF?.language;
        if (person.language && language?.current?.() !== person.language) language?.use?.(person.language);
    }

    // ─── what the template reads ─────────────────────────────────────────────

    // Hidden rows are drawn in the edit mode, struck through, so they can be
    // brought back; outside it they are not drawn at all.
    roots() {
        return (this.navigation?.items ?? []).filter((row) => this.edit?.on || !row.hidden);
    }

    siteRoots() {
        return this.roots().filter((row) => row.level !== "install");
    }

    installRoots() {
        return this.roots().filter((row) => row.level === "install");
    }

    rootByKey(key) {
        return (this.navigation?.items ?? []).find((row) => row.key === key) ?? null;
    }

    // The rows of the open root as they are drawn: hidden ones gone, headings
    // without a visible row after them gone too (assignment v5, item 9), and the
    // filter applied to pages when one is typed.
    rowsOf(root) {
        if (this.edit?.on) return [...(root?.children ?? [])];
        const rows = (root?.children ?? []).filter((row) => !row.hidden);
        const needle = this.filter.trim().toLowerCase();
        const matched = needle
            ? rows.filter((row) => row.kind === "heading" || (row.label ?? "").toLowerCase().includes(needle)
                || (row.children ?? []).some((child) => (child.label ?? "").toLowerCase().includes(needle)))
            : rows;
        const drawn = [];
        matched.forEach((row, index) => {
            if (row.kind === "heading") {
                const next = matched.slice(index + 1).find((candidate) => candidate.kind !== "divider");
                if (!next || next.kind === "heading") return;
            }
            if (row.kind === "divider" && (needle || drawn.length === 0 || drawn[drawn.length - 1].kind === "divider")) return;
            drawn.push(row);
        });
        while (drawn.length && drawn[drawn.length - 1].kind === "divider") drawn.pop();
        return drawn;
    }

    pageCount(root) {
        return (root?.children ?? []).filter((row) => !row.hidden && !["heading", "divider"].includes(row.kind)).length;
    }

    offersFind(root) {
        return this.pageCount(root) >= FIND_IN_SECTION_FROM;
    }

    favourites() {
        return this.offers("favourites") ? (this.navigation?.favourites ?? []) : [];
    }

    shownFavourites() {
        const all = this.favourites();
        return this.favouritesFolded ? [] : all.slice(0, FAVOURITES_SHOWN);
    }

    isFavourite(key) {
        return this.favourites().some((entry) => entry.key === key);
    }

    isCurrent(key) {
        return this.navigation?.current === key;
    }

    // A root is shown as current when the page belongs to one of its rows.
    holdsCurrent(root) {
        const current = this.navigation?.current;
        if (!current) return false;
        if (root.key === current) return true;
        return (root.children ?? []).some((row) => row.key === current
            || (row.children ?? []).some((child) => child.key === current));
    }

    // ─── what a person does ──────────────────────────────────────────────────

    openRootColumn(key) {
        if (this.openRoot === key && this.wide) {
            this.openRoot = null;
        } else {
            this.openRoot = key;
            this.filter = "";
            if (!this.wide) this.screen = "second";
        }
        this.update("root");
    }

    hideColumn() {
        this.openRoot = null;
        this.screen = "first";
        this.update("root");
    }

    back() {
        this.screen = "first";
        this.update("screen");
    }

    toggleSection(key) {
        if (this.unfolded.has(key)) this.unfolded.delete(key);
        else this.unfolded.add(key);
        this.update("section");
    }

    setFilter(value) {
        this.filter = String(value ?? "");
        this.update("filter");
    }

    toggleFavouritesFolded() {
        this.favouritesFolded = !this.favouritesFolded;
        this.update("favourites-folded");
    }

    // A row is a link the browser understands -- a middle click or a modified
    // click opens it in a tab as any link would -- and a plain click is the
    // host's to answer, because what opening a row means is the host's.
    async openRow(row, event) {
        if (event && (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey)) return;
        if (row.kind === "link") return;
        event?.preventDefault?.();
        if (row.kind === "root") {
            this.openRootColumn(row.key);
            return;
        }
        if (row.kind === "section") {
            this.toggleSection(row.key);
            return;
        }
        const answer = await this.raise("item.open", {key: row.key, href: row.href ?? null});
        if (answer.answer === "applied") {
            this.navigation = {...this.navigation, current: row.key};
            if (!this.wide) this.screen = "first";
            this.update("open");
            this.element.dispatchEvent(new CustomEvent("sf-admin-menu-navigate", {
                bubbles: true,
                composed: true,
                detail: {key: row.key, href: answer.href ?? row.href ?? null},
            }));
        }
    }

    async toggleFavourite(row) {
        const on = !this.isFavourite(row.key);
        const answer = await this.raise("favourite.toggle", {key: row.key, on});
        if (answer.answer === "applied" && Array.isArray(answer.favourites)) {
            this.navigation = {...this.navigation, favourites: answer.favourites};
            this.update("favourite");
        }
    }

    async toggleCompact() {
        this.compact = !this.compact;
        if (this.compact) this.openRoot = null;
        this.update("compact");
        if (this.offers("preferences")) await this.raise("preference.compact", {compact: this.compact});
        this.element.dispatchEvent(new CustomEvent("sf-admin-menu-compact-change", {
            bubbles: true,
            composed: true,
            // The payload the manifest has always declared for this event.
            detail: {compact: this.compact, source: "toggle"},
        }));
    }

    // ─── the site switcher ───────────────────────────────────────────────────

    canSwitchSite() {
        return this.offers("site-switch") && (this.navigation?.site?.count ?? 1) > 1;
    }

    async openSites() {
        if (!this.canSwitchSite()) return;
        this.sites = {...this.sites, open: true, query: "", items: [], next: null};
        this.update("sites");
        await this.searchSites("");
        this.element.querySelector(".sf-admin-nav-site-find input, .sf-admin-nav-site-find")?.focus?.();
    }

    closeSites({focusTrigger = false} = {}) {
        this.sites = {...this.sites, open: false};
        this.update("sites");
        if (focusTrigger) this.element.querySelector(".sf-admin-nav-site-trigger")?.focus?.();
    }

    // Asked of the host, a page at a time; never filtered here, because an
    // agency may have hundreds of sites.
    async searchSites(query, {more = false} = {}) {
        const sequence = ++this.sites.sequence;
        this.sites = {...this.sites, query, loading: true};
        this.update("sites");
        const answer = await this.raise("sites.search", {query, cursor: more ? this.sites.next : null});
        if (sequence !== this.sites.sequence) return;
        if (answer.answer === "applied") {
            this.sites = {
                ...this.sites,
                loading: false,
                items: more ? [...this.sites.items, ...(answer.items ?? [])] : (answer.items ?? []),
                next: answer.next_cursor ?? null,
            };
        } else {
            this.sites = {...this.sites, loading: false};
        }
        this.update("sites");
    }

    async switchSite(key) {
        this.closeSites();
        if (key === this.navigation?.site?.current?.key) return;
        const sequence = ++this.sequence;
        const answer = await this.raise("site.switch", {site: key, sequence});
        this.accept(answer, sequence);
    }

    // ─── the profile menu ────────────────────────────────────────────────────

    toggleAccount() {
        if (this.account.open) this.closeAccount({focusTrigger: true});
        else this.openAccount();
    }

    openAccount() {
        this.closeSites();
        this.account = {open: true, view: "menu", query: ""};
        this.update("account");
        this.focusInAccount();
    }

    closeAccount({focusTrigger = false} = {}) {
        if (!this.account.open) return;
        this.account = {...this.account, open: false};
        this.update("account");
        if (focusTrigger) this.element.querySelector(".sf-admin-nav-person")?.focus?.();
    }

    accountView(view) {
        this.account = {...this.account, view, query: ""};
        this.update("account");
        this.focusInAccount();
    }

    focusInAccount(attempt = 0) {
        if (!this.account.open) return;
        // The search where there is one -- a person opened the languages to find
        // one -- and otherwise the first thing in the menu.
        const target = this.element.querySelector(".sf-admin-nav-account input")
            ?? (this.account.view === "language"
                ? null
                : this.element.querySelector(".sf-admin-nav-account button, .sf-admin-nav-account a"));
        if (!target) {
            if (attempt < 60) setTimeout(() => this.focusInAccount(attempt + 1), 50);
            return;
        }
        target.focus();
    }

    setLanguageQuery(value) {
        this.account = {...this.account, query: String(value ?? "")};
        this.update("account");
    }

    // The languages the host offers, matched against both names: a person who
    // reads neither script still finds «Deutsch» by typing «German».
    languages() {
        const all = this.navigation?.person?.languages ?? [];
        const needle = this.account.query.trim().toLowerCase();
        return needle
            ? all.filter((entry) => entry.native.toLowerCase().includes(needle)
                || entry.english.toLowerCase().includes(needle)
                || entry.code.toLowerCase().startsWith(needle))
            : all;
    }

    currentLanguage() {
        const code = this.navigation?.person?.language;
        return (this.navigation?.person?.languages ?? []).find((entry) => entry.code === code) ?? null;
    }

    // Applied at once through the Framework's own theme switch, which every page
    // on the Framework already answers; the host keeps it for the next page.
    async chooseTheme(theme) {
        globalThis.SF?.Loader?.setTheme?.(theme);
        this.navigation = {...this.navigation, person: {...this.navigation.person, theme}};
        this.update("theme");
        if (this.offers("preferences")) await this.raise("preference.theme", {theme});
    }

    // Framework components change language without a reload; whether the host's
    // own page needs one is the host's answer, not this component's guess.
    async chooseLanguage(code) {
        globalThis.SF?.language?.use?.(code);
        this.navigation = {...this.navigation, person: {...this.navigation.person, language: code}};
        this.accountView("menu");
        if (!this.offers("preferences")) return;
        const answer = await this.raise("preference.language", {language: code});
        if (answer.answer === "applied" && answer.reload === true) globalThis.location?.reload?.();
    }

    async chooseProfile(key) {
        if (key === this.navigation?.profiles?.current) {
            this.accountView("menu");
            return;
        }
        const answer = await this.raise("view.profile", {profile: key});
        if (answer.answer === "applied" && answer.navigation) {
            this.accept({...answer.navigation, answer: "applied"}, this.sequence);
        }
        this.accountView("menu");
    }

    async signOut() {
        const answer = await this.raise("session.sign-out", {});
        if (answer.answer !== "applied") return;
        this.closeAccount();
        this.element.dispatchEvent(new CustomEvent("sf-admin-menu-navigate", {
            bubbles: true,
            composed: true,
            detail: {key: "session.sign-out", href: answer.href ?? null},
        }));
    }

    // ─── the edit mode ───────────────────────────────────────────────────────

    currentProfile() {
        const profiles = this.navigation?.profiles;
        return profiles?.list?.find((entry) => entry.key === profiles.current) ?? null;
    }

    mayEdit(profile = this.currentProfile()) {
        const rights = this.navigation?.rights ?? {};
        if (!profile) return false;
        return profile.kind === "shared" ? Boolean(rights.edit_shared) : Boolean(rights.edit_personal);
    }

    // The pencil is there when something can be edited at all: the current
    // profile, or a personal one the edit mode can move to.
    canEdit() {
        if (!this.offers("menu-profiles") || !this.navigation) return false;
        return this.mayEdit() || Boolean(this.navigation.rights?.edit_personal);
    }

    // A shared profile this person may not change is not edited by stealth:
    // the edit mode moves to their personal profile, made from the one they
    // were looking at when they have none.
    async startEdit() {
        if (!this.canEdit()) return;
        this.closeAccount();
        if (!this.mayEdit()) {
            let personal = this.navigation.profiles.list.find((entry) => entry.kind === "personal");
            if (!personal) {
                const made = await this.raise("view.create", {
                    label: this.text("personalName"), shared: false, from: this.navigation.profiles.current,
                });
                if (made.answer !== "applied") return this.editMessage(made.reason);
                personal = made.profiles?.list?.find((entry) => entry.kind === "personal");
            }
            if (!personal || !(await this.switchProfile(personal.key))) return;
        }
        this.edit = {...this.edit, on: true, message: ""};
        if (!this.openRoot) {
            const first = this.siteRoots().find((root) => root.kind === "root");
            if (first && this.wide) this.openRoot = first.key;
        }
        this.update("edit");
    }

    stopEdit() {
        this.edit = {on: false, renaming: null, adding: null, rowMenu: null, profiles: false, naming: null, message: ""};
        this.update("edit");
    }

    editMessage(text) {
        this.edit = {...this.edit, message: text || ""};
        this.update("edit");
        return false;
    }

    async switchProfile(key) {
        const answer = await this.raise("view.profile", {profile: key});
        if (answer.answer !== "applied" || !answer.navigation) return this.editMessage(answer.reason);
        this.accept({...answer.navigation, answer: "applied"}, this.sequence);
        return true;
    }

    // The whole layout, read off the menu as it is drawn: where every row sits,
    // its order, whether it is hidden, a name a person gave it, and their own
    // rows. A save is always the whole of it, against the revision it came from.
    layoutOf(items) {
        const layout = {schema_version: 1, items: {}, own: []};
        const place = (row, parent, order) => {
            const named = row.label !== (row.package_label ?? row.label) ? row.label : null;
            if (row.own) {
                layout.own.push({
                    key: row.key, kind: row.kind, parent, order,
                    label: row.label ?? null, icon: row.icon ?? null, href: row.href ?? null,
                });
            } else {
                layout.items[row.key] = {parent, order, hidden: Boolean(row.hidden), label: named, icon: null};
            }
        };
        items.forEach((root, rootIndex) => {
            place(root, null, rootIndex);
            (root.children ?? []).forEach((row, index) => place(row, root.key, index));
        });
        return layout;
    }

    // Every change is drawn at once and saved at once (assignment, section 7).
    // A conflict reads the menu again, with the reason said; nothing is merged
    // behind the person's back.
    async change(mutate) {
        const items = JSON.parse(JSON.stringify(this.navigation.items ?? []));
        mutate(items);
        this.navigation = {...this.navigation, items};
        this.edit = {...this.edit, message: ""};
        this.update("edit-change");
        const answer = await this.raise("view.save", {
            profile: this.navigation.profiles.current,
            revision: this.navigation.revision,
            layout: this.layoutOf(items),
        });
        if (answer.answer === "applied") {
            this.navigation = {...this.navigation, revision: answer.revision ?? this.navigation.revision};
            return true;
        }
        this.editMessage(answer.answer === "conflict" ? this.text("conflict") : answer.reason);
        await this.load("refresh-after-conflict");
        return false;
    }

    // The list a key lives in: the roots, or one root's rows.
    listOf(items, parent) {
        if (parent === null || parent === "roots") return items;
        return items.find((root) => root.key === parent)?.children ?? null;
    }

    findIn(items, key) {
        for (const root of items) {
            if (root.key === key) return {row: root, list: items};
            const at = (root.children ?? []).find((row) => row.key === key);
            if (at) return {row: at, list: root.children};
        }
        return null;
    }

    toggleHidden(key) {
        return this.change((items) => {
            const found = this.findIn(items, key);
            if (found) found.row.hidden = !found.row.hidden;
        });
    }

    renameRow(key, label) {
        this.edit = {...this.edit, renaming: null, rowMenu: null};
        const text = String(label ?? "").trim();
        if (!text) return this.update("edit");
        return this.change((items) => {
            const found = this.findIn(items, key);
            if (found) found.row.label = text;
        });
    }

    returnPackageName(key) {
        this.edit = {...this.edit, rowMenu: null};
        return this.change((items) => {
            const found = this.findIn(items, key);
            if (found && found.row.package_label) found.row.label = found.row.package_label;
        });
    }

    removeOwnRow(key) {
        this.edit = {...this.edit, rowMenu: null};
        return this.change((items) => {
            const found = this.findIn(items, key);
            if (found?.row.own) found.list.splice(found.list.indexOf(found.row), 1);
        });
    }

    // A person's own rows get keys the layout recognises as theirs; the host
    // keeps them as they are, inside the profile they were made in.
    addOwnRow(parent, kind, {label = null, href = null} = {}) {
        this.edit = {...this.edit, adding: null};
        const key = `own.${Date.now().toString(36)}`;
        const row = {
            key, kind, level: "site", own: true, hidden: false, package_label: null,
            label: kind === "divider" ? null : (label || this.text("newHeading")),
            icon: kind === "link" ? "open_in_new" : null,
            href: kind === "link" ? href : null,
            attention: null, badge: null, shortcut: null, action: null,
        };
        return this.change((items) => this.listOf(items, parent)?.push(row)).then((saved) => {
            if (saved && kind === "heading") {
                this.edit = {...this.edit, renaming: key};
                this.update("edit");
            }
            return saved;
        });
    }

    // sf-sortable says what was moved and where to; this controller moves it in
    // the menu it holds and saves. Rows stay within their own list -- the roots,
    // or one root's rows -- which is all the two columns can show at once.
    onSortableIntent(event) {
        if (!this.edit.on) return;
        // sf-sortable names a list by its id; the lists here are
        // `sf-admin-nav-list-roots` and `sf-admin-nav-list-<root key>`.
        const {item, from, to, index} = event.detail ?? {};
        const listKey = (id) => (typeof id === "string" && id.startsWith(LIST_ID) ? id.slice(LIST_ID.length) : null);
        const source = listKey(from);
        if (!source || source !== listKey(to)) return;
        this.moveRow(source, item, index);
    }

    moveRow(listKey, key, index) {
        return this.change((items) => {
            const list = this.listOf(items, listKey === "roots" ? null : listKey);
            if (!list) return;
            const at = list.findIndex((row) => row.key === key);
            if (at < 0) return;
            const [row] = list.splice(at, 1);
            list.splice(Math.max(0, Math.min(index, list.length)), 0, row);
        });
    }

    async resetProfile() {
        const answer = await this.raise("view.reset", {
            profile: this.navigation.profiles.current, revision: this.navigation.revision,
        });
        if (answer.answer === "applied" && answer.navigation) {
            this.accept({...answer.navigation, answer: "applied"}, this.sequence);
            this.editMessage("");
            return;
        }
        this.editMessage(answer.answer === "conflict" ? this.text("conflict") : answer.reason);
        await this.load("refresh-after-conflict");
    }

    toggleEditProfiles() {
        this.edit = {...this.edit, profiles: !this.edit.profiles, naming: null};
        this.update("edit");
    }

    async editProfile(key) {
        this.edit = {...this.edit, profiles: false};
        if (key !== this.navigation.profiles.current) await this.switchProfile(key);
        this.update("edit");
    }

    startNaming(mode) {
        this.edit = {...this.edit, naming: mode};
        this.update("edit");
    }

    async createProfile(label, shared) {
        const text = String(label ?? "").trim();
        if (!text) return;
        const answer = await this.raise("view.create", {label: text, shared: Boolean(shared), from: this.navigation.profiles.current});
        if (answer.answer !== "applied") return this.editMessage(answer.reason);
        const made = answer.profiles?.list?.find((entry) => entry.label === text
            && !this.navigation.profiles.list.some((known) => known.key === entry.key));
        this.edit = {...this.edit, profiles: false, naming: null};
        if (made) await this.switchProfile(made.key);
        else await this.load("requested");
    }

    async renameProfile(label) {
        const text = String(label ?? "").trim();
        if (!text) return;
        const answer = await this.raise("view.rename", {profile: this.navigation.profiles.current, label: text});
        if (answer.answer !== "applied") return this.editMessage(answer.reason);
        this.navigation = {...this.navigation, profiles: answer.profiles ?? this.navigation.profiles};
        this.edit = {...this.edit, profiles: false, naming: null};
        this.update("edit");
    }

    async deleteProfile() {
        const answer = await this.raise("view.delete", {profile: this.navigation.profiles.current});
        if (answer.answer !== "applied") return this.editMessage(answer.reason);
        this.edit = {...this.edit, profiles: false, naming: null};
        if (answer.navigation) this.accept({...answer.navigation, answer: "applied"}, this.sequence);
        else await this.load("requested");
        if (!this.mayEdit()) this.stopEdit();
    }

    // ─── the palette ─────────────────────────────────────────────────────────

    openPalette() {
        if (!this.offers("search") || this.palette.open) return;
        this._paletteOpener = document.activeElement;
        this.closeSites();
        this.palette = {...this.palette, open: true, query: "", groups: [], scope: "", active: 0};
        this.update("palette");
        // Empty text asks for what the host shows before anything is typed:
        // «Недавние» and the actions.
        this.queryPalette("", {now: true});
        this.focusPalette();
    }

    // The field is sf-input's own <input>. The first time the palette opens on a
    // page, sf-input may not even be loaded yet -- the Loader fetches a tag's
    // script when the tag first appears -- so the field arrives some hundreds of
    // milliseconds after the dialog. Asking once, or for a few frames, found
    // nothing and left focus on the page behind a dialog that says it is modal.
    focusPalette(attempt = 0) {
        if (!this.palette.open) return;
        const input = this.element.querySelector(".sf-admin-nav-palette-field input");
        if (!input) {
            if (attempt < 60) setTimeout(() => this.focusPalette(attempt + 1), 50);
            return;
        }
        this.syncPaletteField();
        if (document.activeElement !== input) input.focus();
    }

    closePalette({restoreFocus = true} = {}) {
        if (!this.palette.open) return;
        clearTimeout(this._paletteTimer);
        this.palette = {...this.palette, open: false};
        this.update("palette");
        const opener = this._paletteOpener;
        this._paletteOpener = null;
        if (restoreFocus && opener?.isConnected) opener.focus?.();
    }

    queryPalette(text, {now = false} = {}) {
        const query = String(text ?? "");
        this.palette = {...this.palette, query};
        clearTimeout(this._paletteTimer);
        const ask = async () => {
            const sequence = ++this.palette.sequence;
            this.palette = {...this.palette, loading: true};
            this.update("palette");
            const answer = await this.raise("search.query", {query, sequence});
            // A late answer is dropped: what the field shows is what the
            // results answer.
            if (!this.palette.open || sequence !== this.palette.sequence) return;
            if (answer.answer === "applied") {
                this.palette = {
                    ...this.palette,
                    loading: false,
                    groups: (answer.groups ?? []).filter((group) => (group.items ?? []).length || group.more),
                    scope: answer.echo?.scope ?? "",
                    active: 0,
                };
            } else {
                this.palette = {...this.palette, loading: false, groups: []};
            }
            this.update("palette");
        };
        if (now) ask();
        else this._paletteTimer = setTimeout(ask, PALETTE_PAUSE_MS);
    }

    paletteResults() {
        return this.palette.groups.flatMap((group) => (group.items ?? []).map((item) => ({group: group.key, item})));
    }

    movePalette(delta) {
        const count = this.paletteResults().length;
        if (!count) return;
        this.palette = {...this.palette, active: (this.palette.active + delta + count) % count};
        this.update("palette-active");
        requestAnimationFrame(() => this.element
            .querySelector(".sf-admin-nav-palette-item[data-active=\"true\"]")
            ?.scrollIntoView?.({block: "nearest"}));
    }

    async choosePalette(index = this.palette.active) {
        const chosen = this.paletteResults()[index];
        if (!chosen) return;
        const answer = await this.raise("search.choose", {group: chosen.group, key: chosen.item.key});
        if (answer.answer !== "applied") return;
        this.closePalette({restoreFocus: false});
        const href = answer.href ?? chosen.item.href ?? null;
        if (chosen.item.kind !== "action" && this.navigation) {
            this.navigation = {...this.navigation, current: chosen.item.key};
            this.update("open");
        }
        this.element.dispatchEvent(new CustomEvent("sf-admin-menu-navigate", {
            bubbles: true,
            composed: true,
            detail: {key: chosen.item.key, href},
        }));
    }

    onPaletteKey(event) {
        if (event.key === "ArrowDown") { event.preventDefault(); this.movePalette(1); }
        else if (event.key === "ArrowUp") { event.preventDefault(); this.movePalette(-1); }
        else if (event.key === "Enter") { event.preventDefault(); this.choosePalette(); }
    }

    context() {
        return {
            controller: this,
            text: (key) => this.text(key),
            status: this.status,
            message: this.message,
            navigation: this.navigation,
            wide: this.wide,
            compact: this.compact && this.wide,
            screen: this.screen,
            openRoot: this.openRoot ? this.rootByKey(this.openRoot) : null,
            filter: this.filter,
            sites: this.sites,
            palette: this.palette,
            shortcut: PALETTE_SHORTCUT,
            account: this.account,
            edit: this.edit,
        };
    }
}
