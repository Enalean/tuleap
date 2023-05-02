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
import { selectOrThrow } from "@tuleap/dom";
import { BaseComponentRenderer } from "./BaseComponentRenderer";
import type { LazyboxComponent, LazyboxOptions } from "../type";
import { OptionsBuilder } from "../../tests/builders/OptionsBuilder";

const PLACEHOLDER = "Create a new artifact or search by id or title";
const INPUT_PLACEHOLDER = "Id, title...";

describe("base-component-renderer", () => {
    let select: HTMLSelectElement, doc: Document, options: LazyboxOptions;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        select = document.createElement("select");
        select.id = "source-select-box";

        doc.body.appendChild(select);

        options = OptionsBuilder.withoutNewItem().build();
    });

    const render = (): LazyboxComponent => {
        const renderer = new BaseComponentRenderer(doc, select, options);
        return renderer.renderBaseComponent();
    };

    it("should render the base component and append it right after the source <select>", () => {
        options = OptionsBuilder.withMultiple().build();
        const { lazybox_element, dropdown_element, selection_element } = render();

        const base_component = selectOrThrow(
            doc.body,
            "#source-select-box + .lazybox-component-wrapper"
        );

        expect(base_component.contains(lazybox_element)).toBe(true);
        expect(base_component.contains(selection_element)).toBe(true);
        expect(base_component.contains(dropdown_element)).toBe(true);
    });

    it("When the source <select> is disabled, then the lazybox should be disabled", () => {
        select.setAttribute("disabled", "disabled");

        const { lazybox_element, search_field_element } = render();

        expect(lazybox_element.classList.contains("lazybox-disabled")).toBe(true);
        expect(search_field_element.disabled).toBe(true);
    });

    it(`Given an input_placeholder option; it will set the "placeholder" attribute on the search input`, () => {
        options = OptionsBuilder.withoutNewItem()
            .withPlaceholder(PLACEHOLDER)
            .withSearchInputPlaceholder(INPUT_PLACEHOLDER)
            .build();
        const { search_field_element } = render();

        expect(search_field_element.placeholder).toBe(INPUT_PLACEHOLDER);
    });
});
