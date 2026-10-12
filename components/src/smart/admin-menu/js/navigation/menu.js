// The navigation's menus are the Framework's standard menu (owner,
// 2026-10-12): one sf-context-menu beside the page, filled with the entries of
// whichever menu is asked for and opened for the control that asked. It lives
// outside the navigation's own rendering, so a re-render of the columns does
// not take an open menu away.

export function openMenu(controller, anchor, {label, entries, select}) {
    if (!(anchor instanceof Element)) return null;
    let menu = controller._menu;
    if (!menu?.isConnected) {
        menu = document.createElement("sf-context-menu");
        menu.setAttribute("popup", "");
        menu.dataset.sfAdminNavMenu = "";
        menu.addEventListener("sf-context-menu-select", (event) => controller._menuSelect?.(event.detail?.id, event));
        (controller.element.closest("dialog[open]") || document.body).append(menu);
        controller._menu = menu;
    }
    if (menu.isOpen && controller._menuAnchor === anchor) {
        menu.close();
        return null;
    }
    menu.setAttribute("aria-label", label ?? "");
    menu.entries = entries;
    controller._menuSelect = select;
    controller._menuAnchor = anchor;
    return menu.openFor(anchor);
}

export function closeMenu(controller) {
    controller._menu?.close?.({restoreFocus: false});
}

export function removeMenu(controller) {
    closeMenu(controller);
    controller._menu?.remove();
    controller._menu = null;
}
