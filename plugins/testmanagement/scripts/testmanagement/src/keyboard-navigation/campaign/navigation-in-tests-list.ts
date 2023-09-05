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

import type { Shortcut, ShortcutsGroup } from "../setup-shortcuts";
import type { GettextProvider } from "../type";
import { BOTTOM, NEXT, PREVIOUS, TOP } from "../type";

import { moveFocus } from "../shortcuts-handles/move-focus";

export function createTestsListNavigation(
    doc: Document,
    gettextCatalog: GettextProvider,
): ShortcutsGroup {
    const move_to_next_test: Shortcut = {
        keyboard_inputs: "k,down",
        displayed_inputs: "k,↓",
        description: gettextCatalog.getString("Select next test tab from test list"),
        handle: (): void => {
            moveFocus(document, NEXT);
        },
    };

    const move_to_previous_test: Shortcut = {
        keyboard_inputs: "j,up",
        displayed_inputs: "j,↑",
        description: gettextCatalog.getString("Select previous test tab from test list"),
        handle: (): void => {
            moveFocus(document, PREVIOUS);
        },
    };

    const move_to_last_test: Shortcut = {
        keyboard_inputs: "end",
        displayed_inputs: gettextCatalog.getString("End", null, "Keyboard input"),
        description: gettextCatalog.getString("Select last test tab from test list"),
        handle: (): void => {
            moveFocus(document, BOTTOM);
        },
    };

    const move_to_first_test: Shortcut = {
        keyboard_inputs: "home",
        displayed_inputs: gettextCatalog.getString("Home,↖", null, "Keyboard input"),
        description: gettextCatalog.getString("Select first test tab from test list"),
        handle: (): void => {
            moveFocus(document, TOP);
        },
    };

    return {
        title: gettextCatalog.getString("Navigation in tests list"),
        details: gettextCatalog.getString("Navigation in tests list when a test is selected."),
        shortcuts: [
            move_to_next_test,
            move_to_previous_test,
            move_to_last_test,
            move_to_first_test,
        ],
    };
}
