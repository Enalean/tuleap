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

import type { Shortcut, ShortcutsGroup } from "@tuleap/keyboard-shortcuts";
import { addShortcutsGroup } from "@tuleap/keyboard-shortcuts";

import type { GettextProvider } from "../type";
import { DOWN, UP, RIGHT, LEFT } from "../type";
import { moveFocus } from "./move-focus";

export function setupTaskboardShortcuts(doc: Document, gettext_provider: GettextProvider): void {
    const next: Shortcut = {
        keyboard_inputs: "k,down",
        displayed_inputs: "k,↓",
        description: gettext_provider.$gettext("Move from one swimlane or card to the next one"),
        handle: (): void => {
            moveFocus(doc, DOWN);
        },
    };

    const previous: Shortcut = {
        keyboard_inputs: "j,up",
        displayed_inputs: "j,↑",
        description: gettext_provider.$gettext(
            "Move from one swimlane or card to the previous one"
        ),
        handle: (): void => {
            moveFocus(doc, UP);
        },
    };

    const right: Shortcut = {
        keyboard_inputs: "l,right",
        displayed_inputs: "l,→",
        description: gettext_provider.$gettext("Move to the first card of right cell"),
        handle: (): void => {
            moveFocus(doc, RIGHT);
        },
    };

    const left: Shortcut = {
        keyboard_inputs: "h,left",
        displayed_inputs: "h,←",
        description: gettext_provider.$gettext("Move to the first card of left cell"),
        handle: (): void => {
            moveFocus(doc, LEFT);
        },
    };

    const shortcut_group: ShortcutsGroup = {
        title: gettext_provider.$gettext("Navigation in Taskboard"),
        shortcuts: [next, previous, right, left],
    };
    addShortcutsGroup(document, shortcut_group);
}
