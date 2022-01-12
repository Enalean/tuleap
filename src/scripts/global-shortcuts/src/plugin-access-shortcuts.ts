/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { Shortcut, ShortcutsGroup } from "@tuleap/keyboard-shortcuts";
import type { GettextProvider, ServiceID, ServiceShortcut } from "./type";
import { AGILEDASHBOARD, DOCUMENTS, GIT, TESTMANAGEMENT, TRACKERS } from "./type";

export function getServicesShortcutsGroup(
    doc: HTMLElement,
    gettext_provider: GettextProvider
): ShortcutsGroup | null {
    const focus_sidebar_shortcut = getFocusSidebarShortcut(doc, gettext_provider);
    if (!focus_sidebar_shortcut) {
        return null;
    }

    const services_shortcuts = getServicesShortcuts(doc, gettext_provider);
    const shortcuts: Shortcut[] = [focus_sidebar_shortcut, ...services_shortcuts];

    return {
        title: gettext_provider.gettext("Services quick access"),
        shortcuts,
    };
}

function getFocusSidebarShortcut(
    doc: HTMLElement,
    gettext_provider: GettextProvider
): Shortcut | null {
    const first_sidebar_service = doc.querySelector("[data-shortcut-sidebar]");
    if (!(first_sidebar_service instanceof HTMLElement)) {
        return null;
    }

    return {
        keyboard_inputs: "shift+g",
        description: gettext_provider.gettext("Focus first sidebar service"),
        handle: (): void => first_sidebar_service.focus(),
    };
}

function getServicesShortcuts(doc: HTMLElement, gettext_provider: GettextProvider): Shortcut[] {
    const services: ServiceShortcut[] = [
        { name: TESTMANAGEMENT, keyboard_inputs: "g+e" },
        { name: TRACKERS, keyboard_inputs: "g+t" },
        { name: GIT, keyboard_inputs: "g+i" },
        { name: DOCUMENTS, keyboard_inputs: "g+d" },
        { name: AGILEDASHBOARD, keyboard_inputs: "g+a" },
    ];

    const services_shortcuts: Shortcut[] = [];

    services.forEach((service) => {
        const service_element = getSidebarServiceElement(doc, service.name);
        if (!service_element) {
            return;
        }

        services_shortcuts.push({
            keyboard_inputs: service.keyboard_inputs,
            description: gettext_provider.gettext("Go to %s").replace("%s", service_element.title),
            handle: (): void => service_element.click(),
        });
    });

    return services_shortcuts;
}

function getSidebarServiceElement(doc: HTMLElement, service_id: ServiceID): HTMLElement | null {
    const sidebar_service = doc.querySelector(`[data-shortcut-sidebar="sidebar-${service_id}"]`);
    if (!(sidebar_service instanceof HTMLElement) || !sidebar_service.title) {
        return null;
    }
    return sidebar_service;
}
