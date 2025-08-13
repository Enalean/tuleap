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

import type { MockInstance } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { clickOnDatashortcutElement } from "./click-on-datashortcut-element";
import * as getter_focused_row from "./get-focused-row";

vi.mock("./get-focused-row");

describe("callNavigationShortcut", () => {
    let doc: Document;
    let document_table: HTMLElement;
    let table_body: HTMLTableSectionElement;
    let header_button: HTMLElement;
    let row: HTMLTableRowElement;
    let row_button: HTMLElement;

    const datashortcut_attribute = "datashortcut";
    const datashortcut_selector = `[${datashortcut_attribute}]`;

    let focusHeaderButton: MockInstance;
    let clickHeaderButton: MockInstance;
    let clickRowButton: MockInstance;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        setupDocumentTable(doc);

        vi.spyOn(getter_focused_row, "getFocusedRow").mockReturnValue(row);

        focusHeaderButton = vi.spyOn(header_button, "focus");
        clickHeaderButton = vi.spyOn(header_button, "click");
        clickRowButton = vi.spyOn(row_button, "click");
    });

    describe("clickOnDatashortcutElement", () => {
        it(`clicks on the button with corresponding datashortcut attribute in focused row,
        even if datashortcut attribute is found in both document header and focused row`, () => {
            clickOnDatashortcutElement(doc, datashortcut_selector);

            expect(clickRowButton).toHaveBeenCalled();
            expect(focusHeaderButton).not.toHaveBeenCalled();
            expect(clickHeaderButton).not.toHaveBeenCalled();
        });

        it(`focuses and clicks on the button with corresponding datashortcut attribute in document header
        if datashortcut attribute was not found in focused row`, () => {
            row_button.removeAttribute(datashortcut_attribute);
            clickOnDatashortcutElement(doc, datashortcut_selector);

            expect(clickRowButton).not.toHaveBeenCalled();
            expect(focusHeaderButton).toHaveBeenCalled();
            expect(clickHeaderButton).toHaveBeenCalled();
        });
    });

    function setupDocumentTable(doc: Document): void {
        document_table = doc.createElement("table");
        table_body = doc.createElement("tbody");
        table_body.setAttribute("data-shortcut-table", "");

        const header: HTMLElement = doc.createElement("div");
        header.setAttribute("data-shortcut-header-actions", "");
        header_button = doc.createElement("button");
        header_button.setAttribute(datashortcut_attribute, "");

        row = doc.createElement("tr");
        row_button = doc.createElement("button");
        row_button.setAttribute(datashortcut_attribute, "");

        header.appendChild(header_button);
        row.appendChild(row_button);
        table_body.appendChild(row);
        document_table.appendChild(table_body);
        doc.body.append(header, document_table);
    }
});
