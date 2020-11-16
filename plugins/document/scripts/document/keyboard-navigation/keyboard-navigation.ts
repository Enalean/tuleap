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

import { addPluginShortcut } from "@tuleap/core/scripts/keyboard-navigation/plugin-shortcuts";
import { Direction } from "../type";
import { callNavigationShortcut } from "./handle-navigation-shortcut";

export function setupDocumentShortcuts(): void {
    const navigation_shortcuts = [
        {
            keys: "ctrl+down",
            direction: Direction.bottom,
        },
        {
            keys: "ctrl+up",
            direction: Direction.top,
        },
        {
            keys: "j,up",
            direction: Direction.previous,
        },
        {
            keys: "k,down",
            direction: Direction.next,
        },
    ];
    navigation_shortcuts.forEach((shortcut) => {
        addPluginShortcut(shortcut.keys, () => {
            callNavigationShortcut(document, shortcut.direction);
        });
    });
}
