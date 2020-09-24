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

export class DropdownToggler {
    constructor(
        private readonly list_picker_element: Element,
        private readonly dropdown_element: Element,
        private readonly dropdown_list_element: Element
    ) {}

    public closeListPicker(): void {
        this.dropdown_element.classList.remove("list-picker-dropdown-shown");
        this.list_picker_element.classList.remove("list-picker-with-open-dropdown");
        this.dropdown_list_element.setAttribute("aria-expanded", "false");
    }

    public openListPicker(): void {
        this.dropdown_element.classList.add("list-picker-dropdown-shown");
        this.list_picker_element.classList.add("list-picker-with-open-dropdown");
        this.dropdown_list_element.setAttribute("aria-expanded", "true");
    }
}
