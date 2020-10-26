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
        private readonly dropdown_list_element: Element,
        private readonly search_field_element: HTMLInputElement | null,
        private readonly selection_element: Element
    ) {}

    public closeListPicker(): void {
        if (!this.dropdown_element.classList.contains("list-picker-dropdown-shown")) {
            return;
        }

        this.dropdown_element.classList.remove("list-picker-dropdown-shown");
        this.list_picker_element.classList.remove("list-picker-with-open-dropdown");
        this.setAriaExpandedAttribute(this.dropdown_list_element, false);

        if (this.selection_element.hasAttribute("aria-expanded")) {
            this.setAriaExpandedAttribute(this.selection_element, false);
        }
    }

    public openListPicker(): void {
        if (this.dropdown_element.classList.contains("list-picker-dropdown-shown")) {
            return;
        }

        this.dropdown_element.classList.add("list-picker-dropdown-shown");
        this.list_picker_element.classList.add("list-picker-with-open-dropdown");
        this.setAriaExpandedAttribute(this.dropdown_list_element, true);

        if (this.selection_element.hasAttribute("aria-expanded")) {
            this.setAriaExpandedAttribute(this.selection_element, true);
        }

        if (this.search_field_element) {
            this.search_field_element.focus();
        }
    }

    private setAriaExpandedAttribute(element: Element, is_expanded: boolean): void {
        element.setAttribute("aria-expanded", is_expanded.toString());
    }
}
