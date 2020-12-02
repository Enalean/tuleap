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

import { Shortcut, ShortcutsGroup } from "./type";
import {
    createShortcutsGroupInHelpModal,
    createKeyboardInputElement,
    createShortcutCell,
} from "./add-to-help-modal";

describe("add-to-help-modal.ts", () => {
    let doc: Document;
    let shortcuts_modal: HTMLElement;

    const shortcuts_help_attribute = "data-shortcuts-help";

    const shortcut_simple = {
        keyboard_inputs: "a",
        displayed_inputs: "c",
        description: "shortcut_simple description",
    } as Shortcut;

    const shortcut_two_options = {
        keyboard_inputs: "a,b",
        description: "shortcut_two_options description",
    } as Shortcut;

    const shortcut_two_keystrokes = {
        keyboard_inputs: "a+b",
        description: "shortcut_two_keystrokes description",
    } as Shortcut;

    const shortcuts_group: ShortcutsGroup = {
        title: "shortcuts_group title",
        shortcuts: [shortcut_simple, shortcut_two_options, shortcut_two_keystrokes],
    };

    const snapshot = `
        <h2 class="tlp-modal-subtitle">shortcuts_group title</h2>
        <table class="tlp-table help-modal-shortcuts-table">
          <thead>
            <tr>
              <th scope="colgroup" class="help-modal-shortcuts-description">Description</th>
              <th scope="colgroup" class="tlp-table-cell-actions">Shortcut</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>shortcut_simple description</td>
              <td class="help-modal-shortcuts-kbds tlp-table-cell-actions"><kbd>c</kbd></td>
            </tr>
            <tr>
              <td>shortcut_two_options description</td>
              <td class="help-modal-shortcuts-kbds tlp-table-cell-actions"><kbd>a</kbd> / <kbd>b</kbd></td>
            </tr>
            <tr>
              <td>shortcut_two_keystrokes description</td>
              <td class="help-modal-shortcuts-kbds tlp-table-cell-actions"><kbd><kbd>a</kbd> + <kbd>b</kbd></kbd></td>
            </tr>
          </tbody>
        </table>
    `;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        shortcuts_modal = doc.createElement("section");
        shortcuts_modal.setAttribute(shortcuts_help_attribute, "");
        doc.body.appendChild(shortcuts_modal);
    });

    describe("createShortcutsGroupInHelpModal", () => {
        it("creates a shortcuts group and appends it to the Shortcuts Help modal", () => {
            createShortcutsGroupInHelpModal(doc, shortcuts_group);

            expect(shortcuts_modal.innerHTML).toMatchInlineSnapshot(snapshot);
        });

        it("throws an error if shortcuts modal was not found and stops", () => {
            shortcuts_modal.removeAttribute(shortcuts_help_attribute);
            expect(() => {
                createShortcutsGroupInHelpModal(doc, shortcuts_group);
            }).toThrow();
            expect(shortcuts_modal.childNodes.length).toBe(0);
        });
    });

    describe("createShortcutCell", () => {
        it("returns a cell containing a different keyboard input if there is one provided", () => {
            const shortcut_cell = createShortcutCell(doc, shortcut_simple);
            const keyboard_input_element = shortcut_cell.firstElementChild;

            expect(keyboard_input_element?.innerHTML).toBe("c");
        });

        it("return a cell containing two kbd elements separated by ' / ' if there are two shortcuts options", () => {
            const shortcut_cell = createShortcutCell(doc, shortcut_two_options);

            expect(shortcut_cell?.childNodes.length).toBe(3);
            expect(shortcut_cell?.childNodes[1].textContent).toEqual(" / ");
        });
    });

    describe("createKeyboardInputElement", () => {
        it("returns a kbd element containing two kbd elements separated by ' + ' if passed a double input", () => {
            const keyboard_input_element = createKeyboardInputElement(
                doc,
                shortcut_two_keystrokes.keyboard_inputs
            );

            expect(keyboard_input_element?.childNodes.length).toBe(3);
            expect(keyboard_input_element?.childNodes[1].textContent).toEqual(" + ");
        });
    });
});
