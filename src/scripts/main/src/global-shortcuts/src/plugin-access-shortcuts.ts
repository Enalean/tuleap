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
import type { GettextProvider } from "./type";
import type { ProjectSidebarShortcut } from "@tuleap/project-sidebar-internal";
import {
    BACKLOG,
    DOCUMENTS,
    FIRST_TOOL,
    getAvailableShortcuts,
    GIT,
    KANBAN,
    TESTMANAGEMENT,
    TRACKERS,
} from "@tuleap/project-sidebar-internal";

export function getServicesShortcutsGroup(
    doc: HTMLElement,
    gettext_provider: GettextProvider,
): ShortcutsGroup | null {
    const available_shortcuts = getAvailableShortcuts(doc);

    if (available_shortcuts === null) {
        return null;
    }

    return {
        title: gettext_provider.gettext("Services quick access"),
        shortcuts: available_shortcuts.map((project_sidebar_shortcut) =>
            buildShortcutFromProjectSidebarShortcut(project_sidebar_shortcut, gettext_provider),
        ),
    };
}

function buildShortcutFromProjectSidebarShortcut(
    project_sidebar_shortcut: ProjectSidebarShortcut,
    gettext_provider: GettextProvider,
): Shortcut {
    let description = "";
    const shortcut_name = project_sidebar_shortcut.name;
    switch (shortcut_name) {
        case FIRST_TOOL:
            description = gettext_provider.gettext("Focus first sidebar service");
            break;
        case TESTMANAGEMENT:
        case TRACKERS:
        case GIT:
        case DOCUMENTS:
        case BACKLOG:
        case KANBAN:
            description = gettext_provider
                .gettext("Go to %s")
                .replace("%s", project_sidebar_shortcut.label);
            break;
        default:
            return ((val: never): never => val)(shortcut_name);
    }

    return {
        keyboard_inputs: project_sidebar_shortcut.keyboard_inputs,
        description,
        handle: null,
    };
}
