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

import type { Shortcut } from "../../type";

export function createShortcutCell(doc: Document, shortcut: Shortcut): HTMLTableDataCellElement {
    const shortcut_cell = doc.createElement("td");
    shortcut_cell.classList.add("help-modal-shortcuts-kbds", "tlp-table-cell-actions");

    const keyboard_inputs = shortcut.displayed_inputs
        ? shortcut.displayed_inputs
        : shortcut.keyboard_inputs;

    const keyboard_inputs_parts = keyboard_inputs.split(",");
    keyboard_inputs_parts.forEach((keyboard_input, i = 0) => {
        const keyboard_input_element = createKeyboardInputElement(doc, keyboard_input);
        shortcut_cell.append(keyboard_input_element);

        if (i < keyboard_inputs_parts.length - 1) {
            shortcut_cell.append(" / ");
        }
        i++;
    });

    return shortcut_cell;
}

export function createKeyboardInputElement(doc: Document, keyboard_input: string): HTMLElement {
    const keyboard_input_element = doc.createElement("kbd");

    const keystrokes = keyboard_input.split("+");
    if (keystrokes.length === 1) {
        keyboard_input_element.append(keyboard_input);
        return keyboard_input_element;
    }

    keystrokes.forEach((keystroke, i = 0) => {
        const inner_keyboard_input_element = doc.createElement("kbd");
        inner_keyboard_input_element.append(keystroke);
        keyboard_input_element.appendChild(inner_keyboard_input_element);
        if (i < keystrokes.length - 1) {
            keyboard_input_element.append(" + ");
        }
        i++;
    });

    return keyboard_input_element;
}
