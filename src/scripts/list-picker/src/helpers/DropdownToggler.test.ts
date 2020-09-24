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

describe("dropdown-toggler", () => {
    let root: HTMLElement, dropdown: HTMLElement, list: HTMLElement, toggler: DropdownToggler;

    beforeEach(() => {
        root = document.createElement("span");
        dropdown = document.createElement("span");
        list = document.createElement("span");
        list.setAttribute("class", "list-picker-dropdown-values-list");
        dropdown.appendChild(list);
        toggler = new DropdownToggler(root, dropdown);
    });

    it("opens the dropdown by appending a 'shown' class to the dropdown element", () => {
        toggler.openListPicker();

        expect(root.classList).toContain("list-picker-with-open-dropdown");
        expect(dropdown.classList).toContain("list-picker-dropdown-shown");
        expect(list.getAttribute("aria-expanded")).toBe("true");
    });

    it("closes the dropdown by removing the 'shown' class to the dropdown element", () => {
        toggler.closeListPicker();

        expect(root.classList).not.toContain("list-picker-with-open-dropdown");
        expect(dropdown.classList).not.toContain("list-picker-dropdown-shown");
        expect(list.getAttribute("aria-expanded")).toBe("false");
    });
});
