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

import { beforeEach, describe, expect, it } from "vitest";
import {
    getTableLastChild,
    getTableFirstChild,
    getPreviousSibling,
    getNextSibling,
} from "./get-target-row";

let doc: Document;

let empty_table: HTMLTableSectionElement;
let one_row_table: HTMLTableSectionElement;
let table: HTMLTableSectionElement;

let single_row: HTMLTableRowElement;
let second_row: HTMLTableRowElement;
let first_row: HTMLTableRowElement;
let third_row: HTMLTableRowElement;

describe("get-target-row.ts", () => {
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        setupDocumentTable(doc);
    });

    describe("getTableFirstChild", () => {
        it("returns the table first row", () => {
            const table_first_child = getTableFirstChild(table);
            expect(table_first_child).toBe(first_row);
        });

        it("returns null if there is no first row in the table", () => {
            const table_first_child = getTableFirstChild(empty_table);
            expect(table_first_child).toBeNull();
        });
    });

    describe("getTableLastChild", () => {
        it("returns the table last row", () => {
            const table_last_child = getTableLastChild(table);
            expect(table_last_child).toBe(third_row);
        });

        it("returns null if there is no last row in the table", () => {
            const table_last_child = getTableLastChild(empty_table);
            expect(table_last_child).toBeNull();
        });

        it("returns the table last non-hidden row", () => {
            third_row.classList.add("document-tree-item-hidden");
            const table_last_child = getTableLastChild(table);
            expect(table_last_child).toBe(second_row);
        });
    });

    describe("getPreviousSibling", () => {
        it("returns the given row's previous sibling", () => {
            const row_previous_sibling = getPreviousSibling(second_row);
            expect(row_previous_sibling).toBe(first_row);
        });

        it("returns null if there is no previous row in the table", () => {
            const row_previous_sibling = getPreviousSibling(first_row);
            expect(row_previous_sibling).toBeNull();
        });

        it("returns the given row's previous non-hidden sibling", () => {
            second_row.classList.add("document-tree-item-hidden");
            const row_previous_sibling = getPreviousSibling(third_row);
            expect(row_previous_sibling).toBe(first_row);
        });
    });

    describe("getNextSibling", () => {
        it("returns the given row's next sibling", () => {
            const row_next_sibling = getNextSibling(second_row);
            expect(row_next_sibling).toBe(third_row);
        });

        it("returns null if there is no next row in the table", () => {
            const row_next_sibling = getNextSibling(third_row);
            expect(row_next_sibling).toBeNull();
        });

        it("returns the given row's next non-hidden sibling", () => {
            second_row.classList.add("document-tree-item-hidden");
            const row_next_sibling = getNextSibling(first_row);
            expect(row_next_sibling).toBe(third_row);
        });

        it("returns null if there is no non-hidden row next to the given row", () => {
            third_row.classList.add("document-tree-item-hidden");
            const row_next_sibling = getNextSibling(second_row);
            expect(row_next_sibling).toBeNull();
        });
    });

    function setupDocumentTable(doc: Document): void {
        empty_table = doc.createElement("tbody");

        one_row_table = doc.createElement("tbody");
        single_row = doc.createElement("tr");

        table = doc.createElement("tbody");
        first_row = doc.createElement("tr");
        second_row = doc.createElement("tr");
        third_row = doc.createElement("tr");

        one_row_table.appendChild(single_row);
        table.append(first_row, second_row, third_row);
        doc.body.append(empty_table, one_row_table, table);
    }
});
