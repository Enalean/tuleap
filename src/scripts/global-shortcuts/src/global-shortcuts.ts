/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { handleCreateShortcut } from "./handle-global-shortcuts/handle-create-shortcut";
import { handleSearchShortcut } from "./handle-global-shortcuts/handle-search-shortcut";
import { handleDashboardShortcut } from "./handle-global-shortcuts/handle-dashboard-shortcut";
import { handleHelpShortcut } from "./handle-global-shortcuts/handle-help-shortcut";

export function createGlobalShortcutsGroup(gettext_provider: GettextProvider): ShortcutsGroup {
    const new_dropdown: Shortcut = {
        keyboard_inputs: "c",
        description: gettext_provider.gettext(`Open the "+ New" dropdown`),
        handle: () => {
            handleCreateShortcut();
        },
    };

    const search_modal: Shortcut = {
        keyboard_inputs: "/,s",
        description: gettext_provider.gettext(`Open the "Switch to..." modal`),
        handle: (event: KeyboardEvent) => {
            handleSearchShortcut(event);
        },
    };

    const personal_dashboard: Shortcut = {
        keyboard_inputs: "d",
        description: gettext_provider.gettext("Go to personal dashboard"),
        handle: () => {
            handleDashboardShortcut();
        },
    };

    const open_shortcuts_modal: Shortcut = {
        // Due to the Shift key "?" does not work, therefore we're using wildcard as a workaround.
        // It is handled in keyboard-shortcut internal lib
        keyboard_inputs: "*",
        displayed_inputs: "?",
        description: gettext_provider.gettext("Open this window"),
        handle: () => {
            handleHelpShortcut();
        },
    };

    return {
        title: gettext_provider.gettext("Global shortcuts"),
        shortcuts: [open_shortcuts_modal, new_dropdown, personal_dashboard, search_modal],
    };
}
