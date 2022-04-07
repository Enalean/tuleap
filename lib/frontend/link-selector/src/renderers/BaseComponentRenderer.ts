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
        private readonly doc: HTMLDocument,
        private readonly source_select_box: HTMLSelectElement,
        private readonly placeholder: string
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

        selection_element.appendChild(placeholder_element);

        if (!this.source_select_box.disabled) {
            selection_element.setAttribute("tabindex", "0");
        }

        const search_section_element = this.createSearchSectionElement();
        search_section_element.appendChild(search_field_element);
        dropdown_element.appendChild(search_section_element);

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

    private createDropdownListElement(): Element {
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
        selection_element.classList.add("link-selector-single");
        selection_element.setAttribute("role", "textbox");
        selection_element.setAttribute("aria-readonly", "true");

        return selection_element;
    }

    private createLinkSelectorElement(): Element {
        const list_picker_element = document.createElement("span");
        list_picker_element.classList.add("link-selector");

        if (this.source_select_box.disabled) {
            list_picker_element.classList.add("link-selector-disabled");
        }

        return list_picker_element;
    }

    private createSearchSectionElement(): Element {
        const search_section = document.createElement("span");
        search_section.classList.add("link-selector-single-dropdown-search-section");

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

        return search_field_element;
    }
}
