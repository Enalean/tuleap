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

import { DropdownToggler } from "./DropdownToggler";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";

describe("dropdown-toggler", () => {
    let list_picker: Element,
        dropdown: Element,
        list: Element,
        search_field: HTMLInputElement,
        selection_container: Element,
        toggler: DropdownToggler;

    function getSearchField(search_field_element: HTMLInputElement | null): HTMLInputElement {
        if (search_field_element === null) {
            throw new Error("search_field is null");
        }
        return search_field_element;
    }

    beforeEach(() => {
        const {
            list_picker_element,
            dropdown_element,
            dropdown_list_element,
            search_field_element,
            selection_element,
        } = new BaseComponentRenderer(document.createElement("select"), {
            is_filterable: true,
        }).renderBaseComponent();

        list_picker = list_picker_element;
        dropdown = dropdown_element;
        list = dropdown_list_element;
        selection_container = selection_element;
        search_field = getSearchField(search_field_element);
        toggler = new DropdownToggler(
            list_picker,
            dropdown,
            list,
            search_field_element,
            selection_element
        );
    });

    it("opens the dropdown by appending a 'shown' class to the dropdown element and focuses the search input", () => {
        jest.spyOn(search_field, "focus");
        toggler.openListPicker();

        expect(list_picker.classList).toContain("list-picker-with-open-dropdown");
        expect(dropdown.classList).toContain("list-picker-dropdown-shown");
        expect(list.getAttribute("aria-expanded")).toBe("true");
        expect(search_field.focus).toHaveBeenCalled();
    });

    it("closes the dropdown by removing the 'shown' class to the dropdown element", () => {
        toggler.closeListPicker();

        expect(list_picker.classList).not.toContain("list-picker-with-open-dropdown");
        expect(dropdown.classList).not.toContain("list-picker-dropdown-shown");
        expect(list.getAttribute("aria-expanded")).toBe("false");
    });

    it("should reset the filter when the input contains a query", () => {
        toggler.openListPicker();
        search_field.value = "filter query";
        jest.spyOn(search_field, "dispatchEvent");
        toggler.closeListPicker();
    });

    it("should not open the list picker if it's already open", () => {
        dropdown.classList.add("list-picker-dropdown-shown");

        jest.spyOn(dropdown.classList, "add");
        toggler.openListPicker();

        expect(dropdown.classList.add).not.toHaveBeenCalled();
    });

    it("should not close the list picker if it's already closed", () => {
        jest.spyOn(dropdown.classList, "remove");
        toggler.openListPicker();

        expect(dropdown.classList.remove).not.toHaveBeenCalled();
    });

    it("sets the aria-expanded attribute on the selection element when needed", () => {
        selection_container.setAttribute("aria-expanded", "false");
        toggler.openListPicker();
        expect(selection_container.getAttribute("aria-expanded")).toEqual("true");
        toggler.closeListPicker();
        expect(selection_container.getAttribute("aria-expanded")).toEqual("false");
    });
});
