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

import { getFocusedRow } from "./get-focused-row";

export function clickOnDatashortcutElement(doc: Document, datashortcut: string): void {
    const table_body: HTMLTableSectionElement | null = doc.querySelector("[data-shortcut-table]");

    if (table_body) {
        const focused_row = getFocusedRow(table_body);
        const focused_row_button = focused_row?.querySelector(datashortcut);
        if (focused_row_button instanceof HTMLElement) {
            focused_row_button.click();
            return;
        }
    }

    const header_button = doc.querySelector(`[data-shortcut-header-actions] ${datashortcut}`);
    if (header_button instanceof HTMLElement) {
        header_button.focus();
        header_button.click();
    }
}
