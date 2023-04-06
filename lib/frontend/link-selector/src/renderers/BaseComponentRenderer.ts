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
import type { LinkSelectorComponent } from "../type";
import { isLinkSelectorInAModal } from "../helpers/link-selector-in-modals-helper";

export class BaseComponentRenderer {
    constructor(
        private readonly doc: Document,
        private readonly source_select_box: HTMLSelectElement,
        private readonly placeholder: string,
        private readonly input_placeholder: string,
        private readonly is_multiple: boolean
    ) {}

    public renderBaseComponent(): LinkSelectorComponent {
        const wrapper_element = document.createElement("span");
        wrapper_element.classList.add("link-selector-component-wrapper");

        const link_selector_element = this.createLinkSelectorElement();
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

        link_selector_element.appendChild(selection_element);
        dropdown_element.appendChild(dropdown_list_element);
        wrapper_element.appendChild(link_selector_element);
        this.doc.body.insertAdjacentElement("beforeend", dropdown_element);

        this.source_select_box.insertAdjacentElement("afterend", wrapper_element);

        if (isLinkSelectorInAModal(wrapper_element)) {
            dropdown_element.classList.add("link-selector-dropdown-over-modal");
        }

        return {
            wrapper_element,
            link_selector_element,
            dropdown_element,
            selection_element,
            placeholder_element,
            dropdown_list_element,
            search_field_element,
        };
    }

    private createDropdownListElement(): HTMLElement {
        const dropdown_list_element = document.createElement("ul");
        dropdown_list_element.classList.add("link-selector-dropdown-values-list");
        dropdown_list_element.setAttribute("role", "listbox");
        dropdown_list_element.setAttribute("aria-expanded", "false");
        dropdown_list_element.setAttribute("aria-hidden", "false");
        return dropdown_list_element;
    }

    private createDropdownElement(): HTMLElement {
        const dropdown_element = document.createElement("span");
        dropdown_element.classList.add("link-selector-dropdown");
        dropdown_element.setAttribute("data-test", "link-selector-dropdown");
        return dropdown_element;
    }

    private createPlaceholderElement(): Element {
        const placeholder_element = document.createElement("span");
        placeholder_element.classList.add("link-selector-placeholder");
        placeholder_element.appendChild(document.createTextNode(this.placeholder));
        return placeholder_element;
    }

    private createSelectionElement(): HTMLElement {
        const selection_element = document.createElement("span");
        selection_element.classList.add("link-selector-selection");
        selection_element.setAttribute("data-test", "link-selector-selection");

        if (this.is_multiple) {
            selection_element.classList.add("link-selector-multiple");
            selection_element.setAttribute("aria-haspopup", "true");
            selection_element.setAttribute("aria-expanded", "false");
            selection_element.setAttribute("role", "combobox");
        } else {
            selection_element.classList.add("link-selector-single");
            selection_element.setAttribute("role", "textbox");
            selection_element.setAttribute("aria-readonly", "true");
        }

        return selection_element;
    }

    private createLinkSelectorElement(): Element {
        const link_selector_element = document.createElement("span");
        link_selector_element.classList.add("link-selector");
        link_selector_element.setAttribute("data-test", "link-selector");

        if (this.source_select_box.disabled) {
            link_selector_element.classList.add("link-selector-disabled");
        }

        return link_selector_element;
    }

    private createSearchSectionElement(): Element {
        const search_section = document.createElement("span");
        search_section.classList.add("link-selector-single-dropdown-search-section");

        return search_section;
    }

    private createSearchSectionForMultipleListPicker(): Element {
        const search_section = document.createElement("span");

        search_section.classList.add("link-selector-multiple-search-section");
        return search_section;
    }

    private createSearchFieldElement(): HTMLInputElement {
        const search_field_element = document.createElement("input");
        if (this.source_select_box.disabled) {
            search_field_element.setAttribute("disabled", "disabled");
        }
        search_field_element.setAttribute("data-test", "link-selector-search-field");
        search_field_element.classList.add("link-selector-search-field");
        search_field_element.setAttribute("type", "search");
        search_field_element.setAttribute("tabindex", "0");
        search_field_element.setAttribute("autocomplete", "off");
        search_field_element.setAttribute("autocorrect", "off");
        search_field_element.setAttribute("autocapitalize", "none");
        search_field_element.setAttribute("spellcheck", "none");
        search_field_element.setAttribute("role", "searchbox");
        search_field_element.setAttribute("aria-autocomplete", "list");
        search_field_element.setAttribute("aria-controls", "link-selector-dropdown-values-list");
        search_field_element.setAttribute("placeholder", this.input_placeholder);

        return search_field_element;
    }
}
