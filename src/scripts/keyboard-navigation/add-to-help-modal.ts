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

import type { Shortcut, ShortcutsGroup } from "./type";

export function createShortcutsGroupInHelpModal(
    doc: Document,
    shortcuts_group: ShortcutsGroup
): void {
    let specific_shortcuts_section = doc.querySelector("[data-shortcuts-specific-section]");
    if (!(specific_shortcuts_section instanceof HTMLElement)) {
        widenModalSize(doc);
        specific_shortcuts_section = createSpecificShortcutsSectionInHelpModal(doc);
    }

    const shortcuts_group_head = createShortcutsGroupHead(doc, shortcuts_group);
    const shortcuts_group_table = createShortcutsGroupTable(doc, shortcuts_group);
    specific_shortcuts_section.append(shortcuts_group_head, shortcuts_group_table);
}

function widenModalSize(doc: Document): void {
    const shortcuts_modal = doc.getElementById("help-modal-shortcuts");
    if (!(shortcuts_modal instanceof HTMLElement)) {
        throw new Error("Could not find shortcuts modal");
    }
    shortcuts_modal.classList.add("tlp-modal-medium-sized");
}

function createSpecificShortcutsSectionInHelpModal(doc: Document): HTMLElement {
    const specific_shortcuts_section = doc.createElement("section");
    specific_shortcuts_section.setAttribute("data-shortcuts-specific-section", "");
    specific_shortcuts_section.classList.add("help-modal-shortcuts-section");

    const shortcuts_modal_body = doc.querySelector("[data-shortcuts-modal-body]");
    if (!(shortcuts_modal_body instanceof HTMLElement)) {
        throw new Error("Could not find shortcuts modal body");
    }
    shortcuts_modal_body.append(specific_shortcuts_section);

    return specific_shortcuts_section;
}

function createShortcutsGroupHead(doc: Document, shortcuts_group: ShortcutsGroup): HTMLElement {
    const shortcuts_group_head = doc.createElement("div");
    shortcuts_group_head.classList.add("help-modal-shortcuts-group-head");

    const group_title = doc.createElement("h2");
    group_title.classList.add("tlp-modal-subtitle");
    group_title.append(shortcuts_group.title);
    shortcuts_group_head.append(group_title);

    if (shortcuts_group.details) {
        const group_details = doc.createElement("p");
        group_details.classList.add("help-modal-shortcuts-group-details");
        group_details.append(shortcuts_group.details);
        shortcuts_group_head.append(group_details);
    }

    return shortcuts_group_head;
}

function createShortcutsGroupTable(
    doc: Document,
    shortcuts_group: ShortcutsGroup
): HTMLTableElement {
    const shortcuts_group_table = doc.createElement("table");
    shortcuts_group_table.classList.add("tlp-table", "help-modal-shortcuts-table");

    const table_head = getTableHead(doc);
    const table_body = doc.createElement("tbody");
    table_body.append(
        ...shortcuts_group.shortcuts.map((shortcut) => createShortcutRow(doc, shortcut))
    );

    shortcuts_group_table.append(table_head, table_body);
    return shortcuts_group_table;
}

function getTableHead(doc: Document): Node {
    const table_head_template = doc.querySelector("[data-shortcuts-help-header-template]");
    if (!(table_head_template instanceof HTMLTemplateElement)) {
        throw new Error("table_head_template was not found or is not a HTMLTemplateElement");
    }

    const table_head = table_head_template.content.firstElementChild;
    if (!table_head) {
        throw new Error("table_head_template should have one element child");
    }
    return table_head.cloneNode(true);
}

function createShortcutRow(doc: Document, shortcut: Shortcut): HTMLTableRowElement {
    const shortcut_row = doc.createElement("tr");

    const shortcut_cell = createShortcutCell(doc, shortcut);

    const description_cell = shortcut_row.insertCell();
    description_cell.append(shortcut.description);

    shortcut_row.append(description_cell, shortcut_cell);
    return shortcut_row;
}

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
