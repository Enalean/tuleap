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

import type { Direction } from "../type";
import { BOTTOM, NEXT, TOP } from "../type";
import {
    getNextSibling,
    getPreviousSibling,
    getTableFirstChild,
    getTableLastChild,
} from "./get-target-row";
import { getFocusedRow } from "./get-focused-row";

export function callNavigationShortcut(doc: Document, direction: Direction): void {
    const target_row: HTMLElement | null = getTargetRow(doc, direction);
    if (!target_row) {
        return;
    }

    const target_row_link = target_row.querySelector(".document-folder-subitem-link");
    if (target_row_link instanceof HTMLElement) {
        target_row_link.focus();
    }
}

function getTargetRow(doc: Document, direction: Direction): HTMLElement | null {
    const table_body: HTMLTableSectionElement | null = doc.querySelector(
        ".document-folder-pane tbody",
    );
    if (!table_body || (table_body && !table_body.hasChildNodes())) {
        return null;
    }
    if (direction === BOTTOM) {
        return getTableLastChild(table_body);
    }
    if (direction === TOP) {
        return getTableFirstChild(table_body);
    }

    const focused_row = getFocusedRow(table_body);
    if (!focused_row) {
        return null;
    }
    if (direction === NEXT) {
        return getNextSibling(focused_row);
    }
    return getPreviousSibling(focused_row);
}
