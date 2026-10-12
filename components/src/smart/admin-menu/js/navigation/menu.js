// The navigation's menus are the Framework's standard menu (owner,
// 2026-10-12): one sf-context-menu beside the page, filled with the entries of
// whichever menu is asked for and opened for the control that asked. It lives
// outside the navigation's own rendering, so a re-render of the columns does
// not take an open menu away.

export function openMenu(controller, anchor, {label, entries, select, side = "block-end", align = "start", search = null, onSearch = null, onClose = null}) {
    if (!(anchor instanceof Element)) return null;
    let menu = controller._menu;
    if (!menu?.isConnected) {
        menu = document.createElement("sf-context-menu");
        menu.setAttribute("popup", "");
        menu.dataset.sfAdminNavMenu = "";
        menu.addEventListener("sf-context-menu-select", (event) => controller._menuSelect?.(event.detail?.id, event));
        menu.addEventListener("sf-context-menu-search", (event) => controller._menuSearch?.(event.detail?.query ?? ""));
        menu.addEventListener("sf-context-menu-close", (event) => {
            if (event.target !== menu) return;
            const closed = controller._menuClose;
            controller._menuClose = null;
            closed?.();
        });
        (controller.element.closest("dialog[open]") || document.body).append(menu);
        controller._menu = menu;
    }
    if (menu.isOpen && controller._menuAnchor === anchor) {
        menu.close();
        return null;
    }
    // The menu that was open hears that it closed before another takes its place.
    if (menu.isOpen) menu.close({restoreFocus: false});
    menu.setAttribute("aria-label", label ?? "");
    menu.search = search;
    menu.entries = entries;
    controller._menuSelect = select;
    controller._menuSearch = onSearch;
    controller._menuClose = onClose;
    controller._menuAnchor = anchor;
    return menu.openFor(anchor, {side, align});
}

// Whether the shared menu is open for this anchor now.
export function menuOpenFor(controller, anchor) {
    return Boolean(controller._menu?.isOpen && controller._menuAnchor === anchor);
}

// New entries for the menu -- a search answered, a page more. Set whether or
// not it has finished opening: the first answer can arrive before it has, and
// a menu that took it only when open stayed empty.
export function updateMenu(controller, entries) {
    if (controller._menu) controller._menu.entries = entries;
}

export function closeMenu(controller) {
    controller._menu?.close?.({restoreFocus: false});
}

export function removeMenu(controller) {
    closeMenu(controller);
    controller._menu?.remove();
    controller._menu = null;
}
