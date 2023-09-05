/**
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

import { describe, expect, it, beforeEach } from "vitest";
import { BaseComponentRenderer } from "./BaseComponentRenderer";

describe("base-component-renderer", () => {
    let renderer: BaseComponentRenderer, select: HTMLSelectElement, doc: HTMLDocument;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        select = document.createElement("select");
        select.id = "source-select-box";

        doc.body.appendChild(select);

        renderer = new BaseComponentRenderer(doc, select, {
            placeholder: "Please select a value",
        });
    });

    it("should render the base component and append it right after the source <select>", () => {
        const {
            list_picker_element,
            dropdown_element,
            selection_element,
            placeholder_element,
            dropdown_list_element,
        } = renderer.renderBaseComponent();

        const base_component = doc.body.querySelector(
            "#source-select-box + .list-picker-component-wrapper",
        );

        if (!base_component) {
            throw new Error("Can't find the list picker in the DOM");
        }

        expect(base_component.contains(list_picker_element)).toBe(true);
        expect(base_component.contains(selection_element)).toBe(true);
        expect(base_component.contains(placeholder_element)).toBe(true);
        expect(doc.body.contains(dropdown_element)).toBe(true);
        expect(dropdown_element.contains(dropdown_list_element)).toBe(true);
    });

    it("When the source <select> is disabled, then the list-picker should be disabled", () => {
        select.setAttribute("disabled", "disabled");

        const { list_picker_element, search_field_element } = renderer.renderBaseComponent();

        expect(list_picker_element.classList.contains("list-picker-disabled")).toBe(true);
        expect(search_field_element.hasAttribute("disabled")).toBe(true);
    });

    describe("placeholder element", () => {
        it("Should display the placeholder text when passed through the options", () => {
            const { placeholder_element } = renderer.renderBaseComponent();
            expect(placeholder_element.textContent).toBe("Please select a value");
        });

        it("Should display an empty string otherwise", () => {
            const { placeholder_element } = new BaseComponentRenderer(
                doc,
                select,
            ).renderBaseComponent();
            expect(placeholder_element.textContent).toBe("");
        });
    });

    describe("multiple <select>", () => {
        beforeEach(() => {
            select.setAttribute("multiple", "multiple");
        });

        it("should append the search field to the selection_element", () => {
            const { selection_element, search_field_element } = renderer.renderBaseComponent();

            expect(selection_element.contains(search_field_element)).toBe(true);
            expect(search_field_element.getAttribute("placeholder")).toBe("Please select a value");

            if (!search_field_element.parentElement) {
                throw new Error("Search input has no parent");
            }

            expect(search_field_element.getAttribute("data-test")).toBe("list-picker-search-field");
            expect(
                search_field_element.parentElement.classList.contains(
                    "list-picker-multiple-search-section",
                ),
            ).toBe(true);
        });

        it("should add a 'disabled' class on the search field parent when the source <select> is disabled", () => {
            select.setAttribute("disabled", "disabled");
            const { search_field_element } = renderer.renderBaseComponent();

            if (!search_field_element.parentElement) {
                throw new Error("Search input has no parent");
            }

            expect(
                search_field_element.parentElement.classList.contains(
                    "list-picker-multiple-search-section-disabled",
                ),
            ).toBe(true);
        });

        it("should create a selection element tailored to contain multiple values", () => {
            const { selection_element } = renderer.renderBaseComponent();

            expect(selection_element.getAttribute("aria-haspopup")).toBe("true");
            expect(selection_element.getAttribute("aria-expanded")).toBe("false");
            expect(selection_element.getAttribute("role")).toBe("combobox");
            expect(selection_element.getAttribute("tabindex")).toBe("-1");
            expect(selection_element.getAttribute("aria-disabled")).toBe("false");
        });

        it("should disable the selection element when the source <select> is disabled", () => {
            select.setAttribute("disabled", "disabled");
            const { selection_element } = renderer.renderBaseComponent();

            expect(selection_element.hasAttribute("tabindex")).toBe(false);
            expect(selection_element.getAttribute("aria-disabled")).toBe("true");
        });
    });
});
