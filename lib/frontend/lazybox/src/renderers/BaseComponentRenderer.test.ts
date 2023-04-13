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
import type { LazyboxComponent } from "../type";
import { OptionsBuilder } from "../../tests/builders/OptionsBuilder";

const PLACEHOLDER = "Create a new artifact or search by id or title";
const INPUT_PLACEHOLDER = "Id, title...";

describe("base-component-renderer", () => {
    let select: HTMLSelectElement, doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        select = document.createElement("select");
        select.id = "source-select-box";

        doc.body.appendChild(select);
    });

    const render = (): LazyboxComponent => {
        const renderer = new BaseComponentRenderer(
            doc,
            select,
            OptionsBuilder.withoutNewItem()
                .withPlaceholder(PLACEHOLDER)
                .withSearchInputPlaceholder(INPUT_PLACEHOLDER)
                .build()
        );
        return renderer.renderBaseComponent();
    };

    it("should render the base component and append it right after the source <select>", () => {
        const {
            lazybox_element,
            dropdown_element,
            selection_element,
            placeholder_element,
            dropdown_list_element,
        } = render();

        const base_component = doc.body.querySelector(
            "#source-select-box + .lazybox-component-wrapper"
        );

        if (!base_component) {
            throw new Error("Can't find the lazybox in the DOM");
        }

        expect(base_component.contains(lazybox_element)).toBe(true);
        expect(base_component.contains(selection_element)).toBe(true);
        expect(base_component.contains(placeholder_element)).toBe(true);
        expect(doc.body.contains(dropdown_element)).toBe(true);
        expect(dropdown_element.contains(dropdown_list_element)).toBe(true);
    });

    it("When the source <select> is disabled, then the lazybox should be disabled", () => {
        select.setAttribute("disabled", "disabled");

        const { lazybox_element, search_field_element } = render();

        expect(lazybox_element.classList.contains("lazybox-disabled")).toBe(true);
        expect(search_field_element.hasAttribute("disabled")).toBe(true);
    });

    it(`Given an input_placeholder option; it will set the "placeholder" attribute on the search input`, () => {
        const { search_field_element } = render();

        expect(search_field_element.getAttribute("placeholder")).toBe(INPUT_PLACEHOLDER);
    });

    describe("placeholder element", () => {
        it("Should display the placeholder text", () => {
            const { placeholder_element } = render();
            expect(placeholder_element.textContent).toBe(PLACEHOLDER);
        });
    });
});
