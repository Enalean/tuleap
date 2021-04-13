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
import { BOTTOM, NEXT, PREVIOUS, TOP } from "../type";
import { callNavigationShortcut } from "./handle-navigation-shortcut";
import * as getter_focused_row from "./get-focused-row";
import * as getter_target_row from "./get-target-row";

jest.mock("./get-target-row");
jest.mock("./get-focused-row");

describe("callNavigationShortcut", () => {
    let doc: Document;
    let document_table: HTMLElement;

    let direction: Direction;

    let table_body: HTMLTableSectionElement;
    let row: HTMLTableRowElement;
    let row_link: HTMLAnchorElement;

    let focus: jest.SpyInstance;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        setupDocumentTable(doc);
        focus = jest.spyOn(row_link, "focus");
    });

    describe("querying table body", () => {
        it("ends if table_body was not found", () => {
            document_table.removeChild(table_body);
            callNavigationShortcut(doc, direction);
            expect(focus).not.toHaveBeenCalled();
        });
        it("ends if table_body is empty", () => {
            table_body.removeChild(row);
            callNavigationShortcut(doc, direction);
            expect(focus).not.toHaveBeenCalled();
        });
    });

    describe("`BOTTOM` is passed, we want the table last row", () => {
        it("focuses the row link returned by getTableLastChild()", () => {
            direction = BOTTOM;
            jest.spyOn(getter_target_row, "getTableLastChild").mockReturnValue(row);

            callNavigationShortcut(doc, direction);
            expect(focus).toHaveBeenCalled();
        });

        it("does not focus row link if getTableLastChild() returns null", () => {
            direction = BOTTOM;
            jest.spyOn(getter_target_row, "getTableLastChild").mockReturnValue(null);

            callNavigationShortcut(doc, direction);
            expect(focus).not.toHaveBeenCalled();
        });
    });

    describe("`TOP` is passed, we want the table first row", () => {
        it("focuses the row link returned by getTableFirstChild()", () => {
            direction = TOP;
            jest.spyOn(getter_target_row, "getTableFirstChild").mockReturnValue(row);

            callNavigationShortcut(doc, direction);
            expect(focus).toHaveBeenCalled();
        });

        it("does not focus row link if getTableFirstChild() returns null", () => {
            direction = TOP;
            jest.spyOn(getter_target_row, "getTableFirstChild").mockReturnValue(null);

            callNavigationShortcut(doc, direction);
            expect(focus).not.toHaveBeenCalled();
        });
    });

    describe("`PREVIOUS` is passed, we want the focused row previous one", () => {
        it("focuses the row link returned by getPreviousSibling()", () => {
            direction = PREVIOUS;
            jest.spyOn(getter_focused_row, "getFocusedRow").mockReturnValue(row);
            jest.spyOn(getter_target_row, "getPreviousSibling").mockReturnValue(row);

            callNavigationShortcut(doc, direction);
            expect(focus).toHaveBeenCalled();
        });

        it("does not focus row link if getPreviousSibling() returns null", () => {
            direction = PREVIOUS;
            jest.spyOn(getter_focused_row, "getFocusedRow").mockReturnValue(row);
            jest.spyOn(getter_target_row, "getPreviousSibling").mockReturnValue(null);

            callNavigationShortcut(doc, direction);
            expect(focus).not.toHaveBeenCalled();
        });

        it("does not focus row link if no row is focused in the first place", () => {
            direction = PREVIOUS;
            jest.spyOn(getter_focused_row, "getFocusedRow").mockReturnValue(null);

            callNavigationShortcut(doc, direction);
            expect(focus).not.toHaveBeenCalled();
        });
    });

    describe("`NEXT` is passed, we want the focused row next one", () => {
        it("focuses the row link returned by getNextSibling()", () => {
            direction = NEXT;
            jest.spyOn(getter_focused_row, "getFocusedRow").mockReturnValue(row);
            jest.spyOn(getter_target_row, "getNextSibling").mockReturnValue(row);

            callNavigationShortcut(doc, direction);
            expect(focus).toHaveBeenCalled();
        });

        it("does not focus row link if getNextSibling() returns null", () => {
            direction = NEXT;
            jest.spyOn(getter_focused_row, "getFocusedRow").mockReturnValue(row);
            jest.spyOn(getter_target_row, "getNextSibling").mockReturnValue(null);

            callNavigationShortcut(doc, direction);
            expect(focus).not.toHaveBeenCalled();
        });

        it("does not focus row link if no row is focused in the first place", () => {
            direction = NEXT;
            jest.spyOn(getter_focused_row, "getFocusedRow").mockReturnValue(null);

            callNavigationShortcut(doc, direction);
            expect(focus).not.toHaveBeenCalled();
        });
    });

    function setupDocumentTable(doc: Document): void {
        document_table = doc.createElement("section");
        document_table.classList.add("document-folder-pane");

        table_body = doc.createElement("tbody");

        row = doc.createElement("tr");
        row_link = doc.createElement("a");
        row_link.classList.add("document-folder-subitem-link");

        row.appendChild(row_link);
        table_body.appendChild(row);
        document_table.appendChild(table_body);
        doc.body.appendChild(document_table);
    }
});
