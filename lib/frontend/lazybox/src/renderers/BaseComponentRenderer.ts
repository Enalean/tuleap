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
import type { LazyboxComponent, LazyboxOptions } from "../type";
import type { SearchInput } from "../SearchInput";
import { TAG as SEARCH_INPUT_TAG } from "../SearchInput";
import { TAG as SELECTION_TAG } from "../selection/SelectionElement";
import type { DropdownElement } from "../dropdown/DropdownElement";
import { TAG as DROPDOWN_TAG } from "../dropdown/DropdownElement";
import type { SelectionElement } from "../selection/SelectionElement";

const isSearchInput = (element: HTMLElement): element is HTMLElement & SearchInput =>
    element.tagName === SEARCH_INPUT_TAG.toUpperCase();

export const isSelection = (element: HTMLElement): element is HTMLElement & SelectionElement =>
    element.tagName === SELECTION_TAG.toUpperCase();

export const isDropdown = (element: HTMLElement): element is HTMLElement & DropdownElement =>
    element.tagName === DROPDOWN_TAG.toUpperCase();

export class BaseComponentRenderer {
    constructor(
        private readonly doc: Document,
        private readonly source_select_box: HTMLSelectElement,
        private readonly options: LazyboxOptions
    ) {}

    public renderBaseComponent(): LazyboxComponent {
        const wrapper_element = this.doc.createElement("span");
        wrapper_element.classList.add("lazybox-component-wrapper");

        const lazybox_element = this.createLazyboxElement();
        const dropdown_element = this.createDropdownElement();
        const search_field_element = this.createSearchFieldElement();
        const selection_element = this.createSelectionElement();

        dropdown_element.search_input = search_field_element;
        dropdown_element.selection = selection_element;
        selection_element.search_input = search_field_element;
        if (this.options.is_multiple) {
            search_field_element.classList.add("lazybox-multiple-search-section");
        }
        lazybox_element.appendChild(selection_element);

        wrapper_element.append(lazybox_element, dropdown_element);

        this.source_select_box.insertAdjacentElement("afterend", wrapper_element);

        return {
            wrapper_element,
            lazybox_element,
            dropdown_element,
            search_field_element,
            selection_element,
        };
    }

    private createDropdownElement(): HTMLElement & DropdownElement {
        const dropdown_element = this.doc.createElement(DROPDOWN_TAG);
        if (!isDropdown(dropdown_element)) {
            throw Error("Could not create the Dropdown element");
        }
        dropdown_element.classList.add("lazybox-dropdown");
        dropdown_element.multiple_selection = this.options.is_multiple;
        dropdown_element.templating_callback = this.options.templating_callback;
        if (this.options.new_item_callback !== undefined) {
            dropdown_element.new_item_callback = this.options.new_item_callback;
            dropdown_element.new_item_button_label = this.options.new_item_button_label;
        }
        return dropdown_element;
    }

    private createSelectionElement(): HTMLElement & SelectionElement {
        const selection_element = this.doc.createElement(SELECTION_TAG);
        if (!isSelection(selection_element)) {
            throw new Error("Could not create the SelectionElement");
        }
        selection_element.multiple = this.options.is_multiple;
        selection_element.placeholder_text = this.options.placeholder;
        selection_element.onSelection = this.options.selection_callback;
        selection_element.templating_callback = this.options.templating_callback;

        return selection_element;
    }

    private createLazyboxElement(): Element {
        const lazybox_element = this.doc.createElement("span");
        lazybox_element.classList.add("lazybox");
        lazybox_element.setAttribute("data-test", "lazybox");

        if (this.source_select_box.disabled) {
            lazybox_element.classList.add("lazybox-disabled");
        }

        return lazybox_element;
    }

    private createSearchFieldElement(): HTMLElement & SearchInput {
        const element = this.doc.createElement(SEARCH_INPUT_TAG);
        if (!isSearchInput(element)) {
            throw Error("Could not create search input");
        }
        element.disabled = this.source_select_box.disabled;
        element.placeholder = this.options.is_multiple
            ? this.options.placeholder
            : this.options.search_input_placeholder;
        element.search_callback = this.options.search_input_callback;
        return element;
    }
}
