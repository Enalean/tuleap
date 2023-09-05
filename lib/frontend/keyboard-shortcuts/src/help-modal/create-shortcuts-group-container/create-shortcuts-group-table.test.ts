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

import { describe, it, expect, beforeEach } from "vitest";
import type { Shortcut, ShortcutsGroup } from "../../type";
import { createShortcutsGroupTable, getTableHead } from "./create-shortcuts-group-table";

describe("createShortcutsGroupTable", () => {
    let doc: Document;

    const shortcut_a = {
        keyboard_inputs: "a",
        description: "shortcut description a",
    } as Shortcut;

    const shortcut_b = {
        keyboard_inputs: "b",
        description: "shortcut description b",
    } as Shortcut;

    const shortcuts_group: ShortcutsGroup = {
        shortcuts: [shortcut_a, shortcut_b],
    } as ShortcutsGroup;

    const table_head_snapshot = `
            <thead>
              <tr>
                <th class="help-modal-shortcuts-description">Description</th>
                <th class="tlp-table-cell-actions">Shortcut</th>
              </tr>
            </thead>
    `;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        setupTableHeadTemplate(doc);
    });

    it(`returns a table containing one line per shortcuts`, () => {
        const shortcuts_group_table = createShortcutsGroupTable(doc, shortcuts_group);
        const shortcuts_rows = shortcuts_group_table.querySelectorAll("tbody tr");

        expect(shortcuts_rows).toHaveLength(2);
    });

    it(`has table rows containing the shortcut description`, () => {
        const shortcuts_group_table = createShortcutsGroupTable(doc, shortcuts_group);
        const first_description_cell = shortcuts_group_table.querySelector("tbody td");

        expect(first_description_cell?.textContent).toBe("shortcut description a");
    });

    describe("getTableHead", () => {
        it("returns a table head matching the template", () => {
            const table_head = getTableHead(doc);
            if (!(table_head instanceof HTMLElement)) {
                throw new Error("table_head is not an HTMLElement");
            }

            expect(table_head.outerHTML).toMatchInlineSnapshot(table_head_snapshot);
        });

        it(`throws an error if table_head_template was not found`, () => {
            const table_head_template = doc.querySelector("[data-shortcuts-help-header-template]");
            table_head_template?.removeAttribute("data-shortcuts-help-header-template");

            expect(() => {
                getTableHead(doc);
            }).toThrow();
        });
    });

    function setupTableHeadTemplate(doc: Document): void {
        doc.body.insertAdjacentHTML(
            "beforeend",
            `<template data-shortcuts-help-header-template="">` +
                table_head_snapshot +
                `</template>`,
        );
    }
});
