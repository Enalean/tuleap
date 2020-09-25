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
    let list_picker: Element, dropdown: Element, list: Element, toggler: DropdownToggler;

    beforeEach(() => {
        const {
            list_picker_element,
            dropdown_element,
            dropdown_list_element,
        } = new BaseComponentRenderer(document.createElement("select")).renderBaseComponent();

        list_picker = list_picker_element;
        dropdown = dropdown_element;
        list = dropdown_list_element;
        toggler = new DropdownToggler(list_picker, dropdown, list);
    });

    it("opens the dropdown by appending a 'shown' class to the dropdown element", () => {
        toggler.openListPicker();

        expect(list_picker.classList).toContain("list-picker-with-open-dropdown");
        expect(dropdown.classList).toContain("list-picker-dropdown-shown");
        expect(list.getAttribute("aria-expanded")).toBe("true");
    });

    it("closes the dropdown by removing the 'shown' class to the dropdown element", () => {
        toggler.closeListPicker();

        expect(list_picker.classList).not.toContain("list-picker-with-open-dropdown");
        expect(dropdown.classList).not.toContain("list-picker-dropdown-shown");
        expect(list.getAttribute("aria-expanded")).toBe("false");
    });
});
