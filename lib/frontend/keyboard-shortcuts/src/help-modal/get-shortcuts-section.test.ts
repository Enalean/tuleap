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
import { getGlobalShortcutsSection, getSpecificShortcutsSection } from "./get-shortcuts-section";

describe("get-shortcuts-section.ts", () => {
    let doc: Document;
    let shortcuts_modal: HTMLElement;
    let shortcuts_modal_body: HTMLElement;
    let global_shortcuts_section: HTMLElement;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        setupDocument(doc);
    });

    describe("getGlobalShortcutsSection", () => {
        it("creates a shortcuts group and adds it to the global shortcuts section in the help modal if the `Scope.global` argument is provided", () => {
            const global_shortcuts_section = getGlobalShortcutsSection(doc);

            expect(global_shortcuts_section.innerHTML).toBe("");
        });

        it("throws an error if global shortcuts section was not found", () => {
            shortcuts_modal_body.removeChild(global_shortcuts_section);

            expect(() => {
                getGlobalShortcutsSection(doc);
            }).toThrow();
        });
    });

    describe("getSpecificShortcutsSection", () => {
        it("returns the specific shortcuts section in the help modal", () => {
            const specific_shortcuts_section = doc.createElement("section");
            specific_shortcuts_section.setAttribute("data-shortcuts-specific-section", "");
            specific_shortcuts_section.classList.add("help-modal-shortcuts-section");
            shortcuts_modal_body.append(specific_shortcuts_section);

            expect(getSpecificShortcutsSection(doc)).toBe(specific_shortcuts_section);
        });

        it("creates the specific shortcuts section in the help modal if this section was not found", () => {
            getSpecificShortcutsSection(doc);
            const specific_shortcuts_section_in_modal = shortcuts_modal.querySelector(
                "[data-shortcuts-specific-section]",
            );

            expect(specific_shortcuts_section_in_modal).not.toBeNull();
        });

        it("widen the help modal when specific shortcuts section is not found and should be created", () => {
            getSpecificShortcutsSection(doc);
            const wide_shortcuts_modal =
                shortcuts_modal.classList.contains("tlp-modal-medium-sized");

            expect(wide_shortcuts_modal).toBe(true);
        });

        it(`throws an error if the help modal was not found while trying to widen it`, () => {
            shortcuts_modal.id = "";

            expect(() => {
                getSpecificShortcutsSection(doc);
            }).toThrow();
        });

        it(`throws an error if the help modal body was not found
        while trying to create the specific shortcuts section in it`, () => {
            shortcuts_modal_body.removeAttribute("data-shortcuts-modal-body");

            expect(() => {
                getSpecificShortcutsSection(doc);
            }).toThrow();
        });
    });

    function setupDocument(doc: Document): void {
        shortcuts_modal = doc.createElement("div");
        shortcuts_modal.id = "help-modal-shortcuts";

        shortcuts_modal_body = doc.createElement("div");
        shortcuts_modal_body.setAttribute("data-shortcuts-modal-body", "");

        global_shortcuts_section = doc.createElement("section");
        global_shortcuts_section.setAttribute("data-shortcuts-global-section", "");

        shortcuts_modal_body.append(global_shortcuts_section);
        shortcuts_modal.append(shortcuts_modal_body);
        doc.body.appendChild(shortcuts_modal);
    }
});
