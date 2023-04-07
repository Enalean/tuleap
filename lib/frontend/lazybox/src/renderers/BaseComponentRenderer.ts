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
import type { LazyboxComponent } from "../type";
import { isLazyboxInAModal } from "../helpers/lazybox-in-modals-helper";

export class BaseComponentRenderer {
    constructor(
        private readonly doc: Document,
        private readonly source_select_box: HTMLSelectElement,
        private readonly placeholder: string,
        private readonly input_placeholder: string,
        private readonly is_multiple: boolean
    ) {}

    public renderBaseComponent(): LazyboxComponent {
        const wrapper_element = document.createElement("span");
        wrapper_element.classList.add("lazybox-component-wrapper");

        const lazybox_element = this.createLazyboxElement();
        const dropdown_element = this.createDropdownElement();
        const dropdown_list_element = this.createDropdownListElement();
        const selection_element = this.createSelectionElement();
        const placeholder_element = this.createPlaceholderElement();
        const search_field_element = this.createSearchFieldElement();

        if (!this.source_select_box.disabled) {
            selection_element.setAttribute("tabindex", "0");
        }

        if (this.is_multiple) {
            search_field_element.setAttribute("placeholder", this.placeholder);
            const new_search_section = this.createSearchSectionForMultipleListPicker();
            new_search_section.appendChild(search_field_element);
            selection_element.appendChild(new_search_section);
        } else {
            const search_section_element = this.createSearchSectionElement();
            search_section_element.appendChild(search_field_element);
            dropdown_element.appendChild(search_section_element);
            selection_element.appendChild(placeholder_element);
        }

        lazybox_element.appendChild(selection_element);
        dropdown_element.appendChild(dropdown_list_element);
        wrapper_element.appendChild(lazybox_element);
        this.doc.body.insertAdjacentElement("beforeend", dropdown_element);

        this.source_select_box.insertAdjacentElement("afterend", wrapper_element);

        if (isLazyboxInAModal(wrapper_element)) {
            dropdown_element.classList.add("lazybox-dropdown-over-modal");
        }

        return {
            wrapper_element,
            lazybox_element: lazybox_element,
            dropdown_element,
            selection_element,
            placeholder_element,
            dropdown_list_element,
            search_field_element,
        };
    }

    private createDropdownListElement(): HTMLElement {
        const dropdown_list_element = document.createElement("ul");
        dropdown_list_element.classList.add("lazybox-dropdown-values-list");
        dropdown_list_element.setAttribute("role", "listbox");
        dropdown_list_element.setAttribute("aria-expanded", "false");
        dropdown_list_element.setAttribute("aria-hidden", "false");
        return dropdown_list_element;
    }

    private createDropdownElement(): HTMLElement {
        const dropdown_element = document.createElement("span");
        dropdown_element.classList.add("lazybox-dropdown");
        dropdown_element.setAttribute("data-test", "lazybox-dropdown");
        return dropdown_element;
    }

    private createPlaceholderElement(): Element {
        const placeholder_element = document.createElement("span");
        placeholder_element.classList.add("lazybox-placeholder");
        placeholder_element.appendChild(document.createTextNode(this.placeholder));
        return placeholder_element;
    }

    private createSelectionElement(): HTMLElement {
        const selection_element = document.createElement("span");
        selection_element.classList.add("lazybox-selection");
        selection_element.setAttribute("data-test", "lazybox-selection");

        if (this.is_multiple) {
            selection_element.classList.add("lazybox-multiple");
            selection_element.setAttribute("aria-haspopup", "true");
            selection_element.setAttribute("aria-expanded", "false");
            selection_element.setAttribute("role", "combobox");
        } else {
            selection_element.classList.add("lazybox-single");
            selection_element.setAttribute("role", "textbox");
            selection_element.setAttribute("aria-readonly", "true");
        }

        return selection_element;
    }

    private createLazyboxElement(): Element {
        const lazybox_element = document.createElement("span");
        lazybox_element.classList.add("lazybox");
        lazybox_element.setAttribute("data-test", "lazybox");

        if (this.source_select_box.disabled) {
            lazybox_element.classList.add("lazybox-disabled");
        }

        return lazybox_element;
    }

    private createSearchSectionElement(): Element {
        const search_section = document.createElement("span");
        search_section.classList.add("lazybox-single-dropdown-search-section");

        return search_section;
    }

    private createSearchSectionForMultipleListPicker(): Element {
        const search_section = document.createElement("span");

        search_section.classList.add("lazybox-multiple-search-section");
        return search_section;
    }

    private createSearchFieldElement(): HTMLInputElement {
        const search_field_element = document.createElement("input");
        if (this.source_select_box.disabled) {
            search_field_element.setAttribute("disabled", "disabled");
        }
        search_field_element.setAttribute("data-test", "lazybox-search-field");
        search_field_element.classList.add("lazybox-search-field");
        search_field_element.setAttribute("type", "search");
        search_field_element.setAttribute("tabindex", "0");
        search_field_element.setAttribute("autocomplete", "off");
        search_field_element.setAttribute("autocorrect", "off");
        search_field_element.setAttribute("autocapitalize", "none");
        search_field_element.setAttribute("spellcheck", "none");
        search_field_element.setAttribute("role", "searchbox");
        search_field_element.setAttribute("aria-autocomplete", "list");
        search_field_element.setAttribute("aria-controls", "lazybox-dropdown-values-list");
        search_field_element.setAttribute("placeholder", this.input_placeholder);

        return search_field_element;
    }
}
