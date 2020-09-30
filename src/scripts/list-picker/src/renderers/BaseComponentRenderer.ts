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
import { ListPickerComponent, ListPickerOptions } from "../type";

export class BaseComponentRenderer {
    constructor(
        private readonly source_select_box: HTMLSelectElement,
        private readonly options?: ListPickerOptions
    ) {}

    public renderBaseComponent(): ListPickerComponent {
        this.hideSourceSelectBox();
        const wrapper_element = document.createElement("span");
        wrapper_element.classList.add("list-picker-component-wrapper");

        const list_picker_element = this.createListPickerElement();
        const selection_element = this.createSelectionElement();
        const placeholder_element = this.createPlaceholderElement();
        const dropdown_element = this.createDropdownElement();
        const dropdown_list_element = this.createDropdownListElement();

        let search_field_element = null;
        if (this.options?.is_filterable) {
            const search_section_element = this.createSearchSectionElement();
            search_field_element = this.createSearchFieldElement();
            search_section_element.appendChild(search_field_element);
            dropdown_element.appendChild(search_section_element);
        }

        selection_element.appendChild(placeholder_element);
        list_picker_element.appendChild(selection_element);
        dropdown_element.appendChild(dropdown_list_element);
        wrapper_element.appendChild(list_picker_element);
        wrapper_element.appendChild(dropdown_element);

        this.source_select_box.insertAdjacentElement("afterend", wrapper_element);

        return {
            wrapper_element,
            list_picker_element,
            dropdown_element,
            selection_element,
            placeholder_element,
            dropdown_list_element,
            search_field_element,
        };
    }

    private hideSourceSelectBox(): void {
        this.source_select_box.classList.add("list-picker-hidden-accessible");
        this.source_select_box.setAttribute("tabindex", "-1");
        this.source_select_box.setAttribute("aria-hidden", "true");
    }

    private createDropdownListElement(): Element {
        const dropdown_list_element = document.createElement("ul");
        dropdown_list_element.classList.add("list-picker-dropdown-values-list");
        dropdown_list_element.setAttribute("role", "listbox");
        dropdown_list_element.setAttribute("aria-expanded", "false");
        dropdown_list_element.setAttribute("aria-hidden", "false");
        return dropdown_list_element;
    }

    private createDropdownElement(): Element {
        const dropdown_element = document.createElement("span");
        dropdown_element.classList.add("list-picker-dropdown");
        return dropdown_element;
    }

    private createPlaceholderElement(): Element {
        const placeholder_element = document.createElement("span");
        placeholder_element.classList.add("list-picker-placeholder");
        placeholder_element.appendChild(document.createTextNode(this.options?.placeholder ?? ""));
        return placeholder_element;
    }

    private createSelectionElement(): Element {
        const selection_element = document.createElement("span");
        selection_element.classList.add("list-picker-selection", "list-picker-single");
        selection_element.setAttribute("role", "textbox");
        selection_element.setAttribute("aria-readonly", "true");
        return selection_element;
    }

    private createListPickerElement(): Element {
        const list_picker_element = document.createElement("span");
        list_picker_element.classList.add("list-picker");

        if (this.source_select_box.disabled) {
            list_picker_element.classList.add("list-picker-disabled");
        }
        return list_picker_element;
    }

    private createSearchSectionElement(): Element {
        const search_section = document.createElement("span");
        search_section.classList.add("list-picker-dropdown-search-section");

        return search_section;
    }

    private createSearchFieldElement(): HTMLInputElement {
        const search_field_element = document.createElement("input");
        search_field_element.classList.add("list-picker-search-field");
        search_field_element.setAttribute("type", "search");
        search_field_element.setAttribute("tabindex", "0");
        search_field_element.setAttribute("autocomplete", "off");
        search_field_element.setAttribute("autocorrect", "off");
        search_field_element.setAttribute("autocapitalize", "none");
        search_field_element.setAttribute("spellcheck", "none");
        search_field_element.setAttribute("role", "searchbox");
        search_field_element.setAttribute("aria-autocomplete", "list");
        search_field_element.setAttribute("aria-controls", "list-picker-dropdown-values-list");

        return search_field_element;
    }
}
