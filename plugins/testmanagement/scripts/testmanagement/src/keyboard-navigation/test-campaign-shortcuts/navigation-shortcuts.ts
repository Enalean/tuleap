/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import type {
    Shortcut,
    ShortcutsGroup,
} from "@tuleap/core/scripts/keyboard-navigation/add-shortcuts-group";
import { addShortcutsGroup } from "@tuleap/core/scripts/keyboard-navigation/add-shortcuts-group";

import type { GettextProvider } from "../type";
import { Direction } from "../type";

import { moveFocus } from "../shortcuts-handles/move-focus";

export function setupNavigationShortcuts(gettextCatalog: GettextProvider): void {
    const move_to_next_test: Shortcut = {
        keyboard_inputs: "k,down",
        displayed_inputs: "k,↓",
        description: gettextCatalog.getString("Select next test tab from test list"),
        handle: (): void => {
            moveFocus(document, Direction.next);
        },
    };

    const move_to_previous_test: Shortcut = {
        keyboard_inputs: "j,up",
        displayed_inputs: "j,↑",
        description: gettextCatalog.getString("Select previous test tab from test list"),
        handle: (): void => {
            moveFocus(document, Direction.previous);
        },
    };

    const move_to_last_test: Shortcut = {
        keyboard_inputs: "end",
        displayed_inputs: "End",
        description: gettextCatalog.getString("Select last test tab from test list"),
        handle: (): void => {
            moveFocus(document, Direction.bottom);
        },
    };

    const move_to_first_test: Shortcut = {
        keyboard_inputs: "home",
        displayed_inputs: "Home,↖",
        description: gettextCatalog.getString("Select first test tab from test list"),
        handle: (): void => {
            moveFocus(document, Direction.top);
        },
    };

    const test_management_navigation_shortcuts_group: ShortcutsGroup = {
        title: gettextCatalog.getString("Navigation in Test Management"),
        details: gettextCatalog.getString(
            "Navigation shortcuts are available when a test is selected in tests list"
        ),
        shortcuts: [
            move_to_next_test,
            move_to_previous_test,
            move_to_last_test,
            move_to_first_test,
        ],
    };
    addShortcutsGroup(document, test_management_navigation_shortcuts_group);
}
