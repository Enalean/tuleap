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
import { createKeyboardInputElement, createShortcutCell } from "./create-shortcut-cell";
import type { Shortcut } from "../../type";

describe("create-shortcut-cell.ts", () => {
    let doc: Document;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    describe("createShortcutCell", () => {
        it("returns a cell containing the keyboard input", () => {
            const shortcut = {
                keyboard_inputs: "a",
            } as Shortcut;
            const shortcut_cell = createShortcutCell(doc, shortcut);

            expect(shortcut_cell.textContent).toBe("a");
        });

        it("returns a cell containing the input to display if one is provided", () => {
            const shortcut = {
                keyboard_inputs: "a",
                displayed_inputs: "c",
            } as Shortcut;
            const shortcut_cell = createShortcutCell(doc, shortcut);

            expect(shortcut_cell.textContent).toBe("c");
        });

        it("returns a cell containing two kbd elements separated by ' / ' if there are two shortcuts options", () => {
            const shortcut = {
                keyboard_inputs: "a,b",
            } as Shortcut;
            const shortcut_cell = createShortcutCell(doc, shortcut);

            expect(shortcut_cell.innerHTML).toBe("<kbd>a</kbd> / <kbd>b</kbd>");
        });
    });

    describe("createKeyboardInputElement", () => {
        it("returns a kbd element containing two kbd elements separated by ' + ' if passed a double input", () => {
            const shortcut = {
                keyboard_inputs: "a+b",
            } as Shortcut;
            const keyboard_input_element = createKeyboardInputElement(
                doc,
                shortcut.keyboard_inputs,
            );

            expect(keyboard_input_element.innerHTML).toBe("<kbd>a</kbd> + <kbd>b</kbd>");
        });
    });
});
