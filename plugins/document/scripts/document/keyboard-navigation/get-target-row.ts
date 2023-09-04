/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

export function getTableFirstChild(
    table_body: HTMLTableSectionElement,
): HTMLTableRowElement | null {
    const table_first_child = table_body.firstElementChild;
    if (!(table_first_child instanceof HTMLTableRowElement)) {
        return null;
    }

    return table_first_child;
}

export function getTableLastChild(table_body: HTMLTableSectionElement): HTMLTableRowElement | null {
    let table_last_child = table_body.lastElementChild;
    if (!(table_last_child instanceof HTMLTableRowElement)) {
        return null;
    }

    while (table_last_child.classList.contains("document-tree-item-hidden")) {
        table_last_child = table_last_child.previousElementSibling;
        if (!(table_last_child instanceof HTMLTableRowElement)) {
            return null;
        }
    }

    return table_last_child;
}

export function getPreviousSibling(current_row: HTMLTableRowElement): HTMLTableRowElement | null {
    let previous_row = current_row.previousElementSibling;
    if (!(previous_row instanceof HTMLTableRowElement)) {
        return null;
    }

    while (previous_row.classList.contains("document-tree-item-hidden")) {
        previous_row = previous_row.previousElementSibling;
        if (!(previous_row instanceof HTMLTableRowElement)) {
            return null;
        }
    }

    return previous_row;
}

export function getNextSibling(current_row: HTMLTableRowElement): HTMLTableRowElement | null {
    let next_row: ChildNode | null = current_row.nextElementSibling;
    if (!(next_row instanceof HTMLTableRowElement)) {
        return null;
    }

    while (next_row.classList.contains("document-tree-item-hidden")) {
        next_row = next_row.nextElementSibling;
        if (!(next_row instanceof HTMLTableRowElement)) {
            return null;
        }
    }

    return next_row;
}
