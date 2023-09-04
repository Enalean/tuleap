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

import type { Shortcut, ShortcutsGroup } from "../../type";
import { createShortcutCell } from "./create-shortcut-cell";
import { selectOrThrow } from "@tuleap/dom";

export function createShortcutsGroupTable(
    doc: Document,
    shortcuts_group: ShortcutsGroup,
): HTMLTableElement {
    const shortcuts_group_table = doc.createElement("table");
    shortcuts_group_table.classList.add("tlp-table", "help-modal-shortcuts-table");

    const table_head = getTableHead(doc);
    const table_body = doc.createElement("tbody");
    table_body.append(
        ...shortcuts_group.shortcuts.map((shortcut) => createShortcutRow(doc, shortcut)),
    );

    shortcuts_group_table.append(table_head, table_body);
    return shortcuts_group_table;
}

export function getTableHead(doc: Document): Node {
    const table_head_template = selectOrThrow(
        doc,
        "[data-shortcuts-help-header-template]",
        HTMLTemplateElement,
    );
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
