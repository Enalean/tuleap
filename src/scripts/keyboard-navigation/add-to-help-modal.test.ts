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

import type { Shortcut, ShortcutsGroup } from "./type";
import {
    createShortcutsGroupInHelpModal,
    createKeyboardInputElement,
    createShortcutCell,
} from "./add-to-help-modal";

describe("add-to-help-modal.ts", () => {
    let doc: Document;
    let shortcuts_modal: HTMLElement;
    let shortcuts_modal_body: HTMLElement;
    let specific_shortcuts_section: HTMLElement;

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
        details: "shortcuts_group details",
        shortcuts: [shortcut_simple, shortcut_two_options, shortcut_two_keystrokes],
    };

    const snapshot = `
        <section data-shortcuts-specific-section="" class="help-modal-shortcuts-section">
          <div class="help-modal-shortcuts-group-head">
            <h2 class="tlp-modal-subtitle">shortcuts_group title</h2>
            <p class="help-modal-shortcuts-group-details">shortcuts_group details</p>
          </div>
          <table class="tlp-table help-modal-shortcuts-table">
            <thead>
              <tr>
                <th class="help-modal-shortcuts-description">Description</th>
                <th class="tlp-table-cell-actions">Shortcut</th>
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
        </section>
    `;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    describe("createShortcutsGroupInHelpModal", () => {
        beforeEach(() => {
            setupDocument(doc);
            setupTableHeadTemplate(doc);
        });

        it("creates a shortcuts group and adds it to the specific shortcuts section in the help modal", () => {
            specific_shortcuts_section = doc.createElement("section");
            specific_shortcuts_section.setAttribute("data-shortcuts-specific-section", "");
            specific_shortcuts_section.classList.add("help-modal-shortcuts-section");
            shortcuts_modal_body.append(specific_shortcuts_section);

            createShortcutsGroupInHelpModal(doc, shortcuts_group);

            expect(shortcuts_modal_body.innerHTML).toMatchInlineSnapshot(snapshot);
        });

        it("creates the specific shortcuts section in the help modal if this section was not found", () => {
            createShortcutsGroupInHelpModal(doc, shortcuts_group);

            expect(shortcuts_modal_body.innerHTML).toMatchInlineSnapshot(snapshot);
            expect(shortcuts_modal.classList.contains("tlp-modal-medium-sized")).toBe(true);
        });

        it(`throws an error if the help modal was not found
            while trying to widen it and stops`, () => {
            shortcuts_modal.id = "";

            expect(() => {
                createShortcutsGroupInHelpModal(doc, shortcuts_group);
            }).toThrow();
            expect(shortcuts_modal_body.innerHTML).toBe("");
        });

        it(`throws an error if the help modal body was not found
            while trying to create the specific shortcuts section in it and stops`, () => {
            shortcuts_modal_body.removeAttribute("data-shortcuts-modal-body");

            expect(() => {
                createShortcutsGroupInHelpModal(doc, shortcuts_group);
            }).toThrow();
            expect(shortcuts_modal_body.innerHTML).toBe("");
        });

        it(`throws an error if table_head_template was not found
            while trying to get table head`, () => {
            const table_head_template = doc.querySelector("[data-shortcuts-help-header-template]");
            table_head_template?.removeAttribute("data-shortcuts-help-header-template");

            expect(() => {
                createShortcutsGroupInHelpModal(doc, shortcuts_group);
            }).toThrow();
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

    function setupDocument(doc: Document): void {
        shortcuts_modal = doc.createElement("div");
        shortcuts_modal.id = "help-modal-shortcuts";

        shortcuts_modal_body = doc.createElement("div");
        shortcuts_modal_body.setAttribute("data-shortcuts-modal-body", "");

        shortcuts_modal.append(shortcuts_modal_body);
        doc.body.appendChild(shortcuts_modal);
    }

    function setupTableHeadTemplate(doc: Document): void {
        doc.body.insertAdjacentHTML(
            "beforeend",
            `<template data-shortcuts-help-header-template="">
                    <thead>
                       <tr>
                         <th class="help-modal-shortcuts-description">Description</th>
                         <th class="tlp-table-cell-actions">Shortcut</th>
                       </tr>
                    </thead>
                </template>`
        );
    }
});
