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

import {
    Shortcut,
    ShortcutsGroup,
    addShortcutsGroupToPlugin,
} from "@tuleap/core/scripts/keyboard-navigation/plugin-shortcuts";

import { Direction } from "../type";
import { callNavigationShortcut } from "./handle-navigation-shortcut";

export function setupDocumentShortcuts(): void {
    const move_to_bottom_file: Shortcut = {
        keyboard_inputs: "ctrl+down",
        handle: () => {
            callNavigationShortcut(document, Direction.bottom);
        },
    };

    const move_to_top_file: Shortcut = {
        keyboard_inputs: "ctrl+up",
        handle: () => {
            callNavigationShortcut(document, Direction.top);
        },
    };

    const move_to_previous_file: Shortcut = {
        keyboard_inputs: "j,up",
        handle: () => {
            callNavigationShortcut(document, Direction.previous);
        },
    };

    const move_to_next_file: Shortcut = {
        keyboard_inputs: "k,down",
        handle: () => {
            callNavigationShortcut(document, Direction.next);
        },
    };

    const document_navigation_shortcuts_group: ShortcutsGroup = {
        shortcuts: [
            move_to_top_file,
            move_to_bottom_file,
            move_to_previous_file,
            move_to_next_file,
        ],
    };

    addShortcutsGroupToPlugin(document, document_navigation_shortcuts_group);
}
