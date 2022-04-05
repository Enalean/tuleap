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

import { BaseComponentRenderer } from "./BaseComponentRenderer";

describe("base-component-renderer", () => {
    let renderer: BaseComponentRenderer, select: HTMLSelectElement, doc: HTMLDocument;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        select = document.createElement("select");
        select.id = "source-select-box";

        doc.body.appendChild(select);

        renderer = new BaseComponentRenderer(
            doc,
            select,
            "Create a new artifact or search by id or title"
        );
    });

    it("should render the base component and append it right after the source <select>", () => {
        const {
            link_selector_element,
            dropdown_element,
            selection_element,
            placeholder_element,
            dropdown_list_element,
        } = renderer.renderBaseComponent();

        const base_component = doc.body.querySelector(
            "#source-select-box + .link-selector-component-wrapper"
        );

        if (!base_component) {
            throw new Error("Can't find the link selector in the DOM");
        }

        expect(base_component.contains(link_selector_element)).toBe(true);
        expect(base_component.contains(selection_element)).toBe(true);
        expect(base_component.contains(placeholder_element)).toBe(true);
        expect(doc.body.contains(dropdown_element)).toBe(true);
        expect(dropdown_element.contains(dropdown_list_element)).toBe(true);
    });

    it("When the source <select> is disabled, then the link-selector should be disabled", () => {
        select.setAttribute("disabled", "disabled");

        const { link_selector_element, search_field_element } = renderer.renderBaseComponent();

        expect(link_selector_element.classList.contains("link-selector-disabled")).toBe(true);
        expect(search_field_element.hasAttribute("disabled")).toBe(true);
    });

    describe("placeholder element", () => {
        it("Should display the a placeholder text", () => {
            const { placeholder_element } = renderer.renderBaseComponent();
            expect(placeholder_element.textContent).toBe(
                "Create a new artifact or search by id or title"
            );
        });
    });
});
