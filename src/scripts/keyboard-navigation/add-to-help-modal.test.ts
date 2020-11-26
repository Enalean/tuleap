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
import { addToShortcutsModalTable, addHelpModalHeader } from "./add-to-help-modal";

describe("add-to-help-modal.ts", () => {
    let doc: Document;
    let shortcuts_table: HTMLTableSectionElement;
    let fake_shortcuts_table: HTMLElement;

    let shortcut_row;
    let keyboard_inputs_cell;
    let keyboard_input_element;

    const table_attribute = "data-modal-shortcuts";
    const group_title = "group_title";
    const cell_id = "cell_id";

    const shortcut_simple = {
        keyboard_inputs: "a",
        displayed_inputs: "c",
        description: "description",
    } as Shortcut;

    const shortcut_two_options = {
        keyboard_inputs: "a, b",
        description: "description",
    } as Shortcut;

    const shortcut_two_keystrokes = {
        keyboard_inputs: "a + b",
        description: "description",
    } as Shortcut;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        setupDocumentTable(doc);
    });

    describe("addToShortcutsModalTable", () => {
        it("inserts a row in shortcuts table", () => {
            addToShortcutsModalTable(doc, cell_id, shortcut_simple);
            expect(shortcuts_table.firstChild).toBeInstanceOf(HTMLTableRowElement);
        });

        it("inserts a single and final kbd element if there is a single shortcut option using one keystroke", () => {
            addToShortcutsModalTable(doc, cell_id, shortcut_simple);

            shortcut_row = shortcuts_table.firstChild;
            keyboard_inputs_cell = shortcut_row?.firstChild;
            keyboard_input_element = keyboard_inputs_cell?.firstChild;

            expect(keyboard_inputs_cell?.childNodes.length).toBe(1);
            expect(keyboard_input_element?.hasChildNodes()).toBe(false);
        });

        it("displays a different keyboard input if there is one provided", () => {
            addToShortcutsModalTable(doc, cell_id, shortcut_simple);

            shortcut_row = shortcuts_table.firstChild;
            keyboard_inputs_cell = shortcut_row?.firstChild;
            keyboard_input_element = keyboard_inputs_cell?.firstChild;

            if (!(keyboard_input_element instanceof HTMLElement)) {
                keyboard_input_element = null;
            }

            expect(keyboard_input_element?.innerText).toBe("c");
        });

        it("inserts two kbd elements separated by ' / ' if there are two shortcuts options", () => {
            addToShortcutsModalTable(doc, cell_id, shortcut_two_options);

            shortcut_row = shortcuts_table.firstChild;
            keyboard_inputs_cell = shortcut_row?.firstChild;

            expect(keyboard_inputs_cell?.childNodes.length).toBe(3);
            expect(keyboard_inputs_cell?.childNodes[1].nodeValue).toEqual(" / ");
        });

        it("inserts a single kbd element containing two kbd elements separated by ' + ' if passed a double input", () => {
            addToShortcutsModalTable(doc, cell_id, shortcut_two_keystrokes);

            shortcut_row = shortcuts_table.firstChild;
            keyboard_inputs_cell = shortcut_row?.firstChild;
            keyboard_input_element = keyboard_inputs_cell?.firstChild;

            expect(keyboard_inputs_cell?.childNodes.length).toBe(1);
            expect(keyboard_input_element?.childNodes.length).toBe(3);
            expect(keyboard_input_element?.childNodes[1].nodeValue).toEqual(" + ");
        });

        it("throws an error if shortcuts modal was not found and stops", () => {
            shortcuts_table.removeAttribute(table_attribute);
            expect(() => {
                addToShortcutsModalTable(doc, cell_id, shortcut_simple);
            }).toThrow();
            expect(shortcuts_table.childNodes.length).toBe(0);
        });

        it("throws an error if shortcuts modal is not a HTMLTableSectionElement found and stops", () => {
            fake_shortcuts_table.setAttribute(table_attribute, "");
            expect(() => {
                addToShortcutsModalTable(doc, cell_id, shortcut_simple);
            }).toThrow();
            expect(shortcuts_table.childNodes.length).toBe(0);
        });
    });

    describe("addHelpModalHeader", () => {
        it("inserts a header row in shortcuts table", () => {
            addHelpModalHeader(doc, group_title, cell_id);
            expect(shortcuts_table.childNodes.length).toBe(1);
        });

        it("throws an error if shortcuts modal was not found and stops", () => {
            shortcuts_table.removeAttribute(table_attribute);
            expect(() => {
                addHelpModalHeader(doc, group_title, cell_id);
            }).toThrow();
            expect(shortcuts_table.childNodes.length).toBe(0);
        });

        it("throws an error if shortcuts modal is not a HTMLTableSectionElement and stops", () => {
            fake_shortcuts_table.setAttribute(table_attribute, "");
            expect(() => {
                addHelpModalHeader(doc, group_title, cell_id);
            }).toThrow();
            expect(shortcuts_table.childNodes.length).toBe(0);
        });
    });

    function setupDocumentTable(doc: Document): void {
        shortcuts_table = doc.createElement("tbody");
        shortcuts_table.setAttribute(table_attribute, "");
        fake_shortcuts_table = doc.createElement("div");
        doc.body.append(fake_shortcuts_table, shortcuts_table);
    }
});
