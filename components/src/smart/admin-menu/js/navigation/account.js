import {html, nothing} from "lit";
import {getInitials} from "../../../helpers/text";

// The profile menu at the bottom of the first column: who is signed in, the
// host's links, the theme and the language, the menu profile, and signing out
// apart (assignment, section 5). The language and the menu profile open as a
// list in the same place, with a way back, so the same menu works on a phone
// where there is no room beside it.

const icon = (name, rootClass = "sf-admin-nav-row-icon") => (name
    ? html`<sf-icon root-class=${rootClass} aria-hidden="true" icon=${name}></sf-icon>`
    : nothing);

const avatar = (person) => (person.avatar
    ? html`<img class="sf-admin-nav-avatar" src=${person.avatar} alt="">`
    : html`<span class="sf-admin-nav-avatar" aria-hidden="true">${getInitials(person.name ?? "")}</span>`);

const THEMES = [
    {value: "light", icon: "light_mode", label: "themeLight"},
    {value: "dark", icon: "dark_mode", label: "themeDark"},
    {value: "system", icon: "contrast", label: "themeSystem"},
];

function renderMenu(context) {
    const {controller, navigation, text} = context;
    const person = navigation.person;
    const site = navigation.site?.current;
    const profiles = navigation.profiles;
    const currentProfile = profiles?.list?.find((entry) => entry.key === profiles.current);
    const language = controller.currentLanguage();
    return html`
        <div class="sf-admin-nav-account-who">
            ${avatar(person)}
            <span class="sf-admin-nav-person-text">
                <span class="sf-admin-nav-person-name">${person.name}</span>
                ${person.email ? html`<span class="sf-admin-nav-person-email">${person.email}</span>` : nothing}
                ${person.role ? html`<span class="sf-admin-nav-person-email">${person.role}${site ? ` · ${site.label}` : ""}</span>` : nothing}
            </span>
        </div>
        ${(person.links ?? []).length ? html`<hr class="sf-admin-nav-divider">
            ${person.links.map((link) => html`<a class="sf-admin-nav-row" href=${link.href}
                @click=${() => controller.closeAccount()}>
                ${icon(link.icon)}<span class="sf-admin-nav-row-label">${link.label}</span>
            </a>`)}` : nothing}
        ${controller.offers("preferences") ? html`<hr class="sf-admin-nav-divider">
            <div class="sf-admin-nav-heading" id="sf-admin-nav-theme-label">${text("theme")}</div>
            <div class="sf-admin-nav-themes" role="radiogroup" aria-labelledby="sf-admin-nav-theme-label">
                ${THEMES.map((theme) => {
                    const on = person.theme === theme.value;
                    return html`<button type="button" class="sf-admin-nav-theme" role="radio"
                        aria-checked=${String(on)} data-on=${on ? "true" : nothing}
                        aria-label=${text(theme.label)} title=${text(theme.label)}
                        @click=${() => controller.chooseTheme(theme.value)}>${icon(theme.icon, "")}</button>`;
                })}
            </div>
            <button type="button" class="sf-admin-nav-row" @click=${() => controller.accountView("language")}>
                ${icon("translate")}
                <span class="sf-admin-nav-row-label">${text("language")}</span>
                <span class="sf-admin-nav-row-value">${language?.native ?? person.language}</span>
                ${icon("chevron_right", "sf-admin-nav-row-chevron")}
            </button>` : nothing}
        ${controller.offers("menu-profiles") && profiles?.list?.length ? html`
            <button type="button" class="sf-admin-nav-row" @click=${() => controller.accountView("profile")}>
                ${icon("view_sidebar")}
                <span class="sf-admin-nav-row-label">${text("menuProfile")}</span>
                <span class="sf-admin-nav-row-value">${currentProfile?.label ?? ""}</span>
                ${icon("chevron_right", "sf-admin-nav-row-chevron")}
            </button>` : nothing}
        ${controller.offers("sign-out") ? html`<hr class="sf-admin-nav-divider">
            <button type="button" class="sf-admin-nav-row sf-admin-nav-row--danger" @click=${() => controller.signOut()}>
                ${icon("logout")}<span class="sf-admin-nav-row-label">${text("signOut")}</span>
            </button>` : nothing}`;
}

function renderBack(controller, label, back) {
    return html`<button type="button" class="sf-admin-nav-row sf-admin-nav-account-back" @click=${() => controller.accountView("menu")}>
        ${icon("arrow_back")}<span class="sf-admin-nav-row-label">${label}</span>
        <span class="sr-only">${back}</span>
    </button>`;
}

function renderLanguages(context) {
    const {controller, navigation, text, account} = context;
    const current = navigation.person.language;
    const list = controller.languages();
    return html`
        ${renderBack(controller, text("language"), text("back"))}
        <div class="sf-admin-nav-account-find">
            <sf-input size="1/2" icon-start="search" placeholder=${text("findLanguage")} value=${account.query}
                @input=${(event) => controller.setLanguageQuery(event.target?.value ?? event.detail?.value ?? "")}></sf-input>
        </div>
        <ul class="sf-admin-nav-account-list" role="listbox" aria-label=${text("language")}>
            ${list.length ? list.map((entry) => html`<li role="option" aria-selected=${String(entry.code === current)}
                lang=${entry.code}>
                <button type="button" class="sf-admin-nav-row" @click=${() => controller.chooseLanguage(entry.code)}>
                    <span class="sf-admin-nav-row-label">${entry.native}
                        <span class="sf-admin-nav-row-caption" lang="en">${entry.english}</span></span>
                    ${entry.code === current ? icon("check", "sf-admin-nav-row-chevron") : nothing}
                </button>
            </li>`) : html`<li class="sf-admin-nav-empty">${text("nothingFound")}</li>`}
        </ul>
        <p class="sf-admin-nav-account-note">${text("languageNote")}</p>`;
}

function renderProfiles(context) {
    const {controller, navigation, text} = context;
    const profiles = navigation.profiles;
    return html`
        ${renderBack(controller, text("menuProfile"), text("back"))}
        <ul class="sf-admin-nav-account-list" role="listbox" aria-label=${text("menuProfile")}>
            ${profiles.list.map((entry) => html`<li role="option" aria-selected=${String(entry.key === profiles.current)}>
                <button type="button" class="sf-admin-nav-row" @click=${() => controller.chooseProfile(entry.key)}>
                    ${icon(entry.kind === "personal" ? "person" : "group")}
                    <span class="sf-admin-nav-row-label">${entry.label}</span>
                    ${entry.key === profiles.current ? icon("check", "sf-admin-nav-row-chevron") : nothing}
                </button>
            </li>`)}
        </ul>`;
}

export function renderAccount(context) {
    const {account, text, navigation} = context;
    if (!account.open || !navigation?.person) return nothing;
    const view = account.view === "language" ? renderLanguages(context)
        : account.view === "profile" ? renderProfiles(context)
            : renderMenu(context);
    return html`<div class="sf-admin-nav-account" role="dialog" aria-label=${text("account")}>${view}</div>`;
}

// The person at the bottom: a button that opens the menu above it.
export function renderPersonButton(context) {
    const {controller, navigation, compact, text, account} = context;
    const person = navigation?.person;
    if (!person) return nothing;
    return html`<button type="button" class="sf-admin-nav-person"
        aria-haspopup="dialog" aria-expanded=${String(account.open)}
        aria-label=${compact ? `${person.name}. ${text("account")}` : nothing}
        title=${compact ? person.name : nothing}
        @click=${() => controller.toggleAccount()}>
        ${avatar(person)}
        ${compact ? nothing : html`<span class="sf-admin-nav-person-text">
            <span class="sf-admin-nav-person-name">${person.name}</span>
            ${person.email ? html`<span class="sf-admin-nav-person-email">${person.email}</span>` : nothing}
        </span>
        ${icon("unfold_more", "sf-admin-nav-site-chevron")}`}
    </button>`;
}
