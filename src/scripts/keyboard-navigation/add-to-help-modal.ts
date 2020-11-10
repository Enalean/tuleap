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

import { Shortcut } from "./type";

export function addToShortcutsModalTable(
    doc: Document,
    cells_id: string,
    shortcut: Shortcut
): void {
    const shortcuts_table = doc.querySelector("[data-modal-shortcuts]");
    if (!(shortcuts_table instanceof HTMLTableSectionElement)) {
        throw new Error("Could not find shortcuts modal");
    }

    const shortcut_row = shortcuts_table.insertRow();

    const keyboard_inputs_cell = createKeyboardInputsCell(doc, cells_id, shortcut);

    const description_cell = shortcut_row.insertCell();
    description_cell.innerText = shortcut.description;
    description_cell.headers = `action ${cells_id}`;

    shortcut_row.append(keyboard_inputs_cell, description_cell);
    shortcuts_table.appendChild(shortcut_row);
}

function createKeyboardInputsCell(
    doc: Document,
    cells_id: string,
    shortcut: Shortcut
): HTMLTableDataCellElement {
    const keyboard_inputs_cell = doc.createElement("td");
    keyboard_inputs_cell.classList.add("help-modal-shortcuts-kbds");
    keyboard_inputs_cell.headers = `shortcut ${cells_id}`;

    const keyboard_inputs_array = shortcut.keyboard_inputs.split(",");

    keyboard_inputs_array.forEach((keyboard_input, i = 0) => {
        const keyboard_input_element = createKeyboardInputElement(doc, keyboard_input);
        keyboard_inputs_cell.append(keyboard_input_element);

        if (i < keyboard_inputs_array.length - 1) {
            keyboard_inputs_cell.append(" / ");
        }
        i++;
    });

    return keyboard_inputs_cell;
}

function createKeyboardInputElement(doc: Document, keyboard_input: string): HTMLElement {
    const keyboard_input_element = doc.createElement("kbd");

    const keystrokes = keyboard_input.split("+");
    if (keystrokes.length === 1) {
        keyboard_input_element.innerText = keyboard_input;
        return keyboard_input_element;
    }

    keystrokes.forEach((keystroke, i = 0) => {
        const inner_keyboard_input_element = doc.createElement("kbd");
        inner_keyboard_input_element.innerText = keystroke;
        keyboard_input_element.appendChild(inner_keyboard_input_element);
        if (i < keystrokes.length - 1) {
            keyboard_input_element.append(" + ");
        }
        i++;
    });

    return keyboard_input_element;
}

export function addHelpModalHeader(doc: Document, group_title: string, cell_id: string): void {
    const shortcuts_table = doc.querySelector("[data-modal-shortcuts]");
    if (!(shortcuts_table instanceof HTMLTableSectionElement)) {
        throw new Error("Could not find shortcuts modal");
    }

    const header_row = shortcuts_table.insertRow();

    const header_cell = doc.createElement("th");
    header_cell.id = cell_id;
    header_cell.classList.add("help-modal-shortcuts-group-title");
    header_cell.scope = "colgroup";
    header_cell.colSpan = 2;
    header_cell.innerText = group_title;

    header_row.appendChild(header_cell);
    shortcuts_table.appendChild(header_row);
}
