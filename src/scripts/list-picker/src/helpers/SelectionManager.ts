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
import { sanitize } from "dompurify";
import { DropdownToggler } from "./DropdownToggler";

export class SelectionManager {
    constructor(
        private readonly source_select_box: HTMLSelectElement,
        private readonly component_dropdown: Element,
        private readonly selection_container: Element,
        private readonly placeholder_element: Element,
        private readonly dropdown_toggler: DropdownToggler
    ) {}

    public processSingleSelection(item: Element): void {
        const is_selected = item.getAttribute("aria-selected") === "true";
        if (is_selected) {
            // We won't unselect it
            return;
        }

        if (this.selection_container.contains(this.placeholder_element)) {
            this.replacePlaceholderWithCurrentSelection(item, this.placeholder_element);
        }

        const selection_value = this.selection_container.querySelector(
            ".list-picker-selected-value"
        );

        if (selection_value instanceof HTMLElement) {
            const option_to_unselect = this.source_select_box.querySelector(
                `[data-item-id=${selection_value.dataset.itemId}]`
            );

            if (option_to_unselect) {
                option_to_unselect.removeAttribute("selected");
            }

            this.replacePreviousSelectionWithCurrentOne(item, selection_value);
        }

        const option_to_select = this.source_select_box.querySelector(`[data-item-id=${item.id}]`);
        if (option_to_select) {
            item.setAttribute("aria-selected", "true");
            option_to_select.setAttribute("selected", "selected");
        }
    }

    public initSelection(placeholder_element: Element): void {
        const selected_option = this.source_select_box.querySelector("option[selected]");

        if (!(selected_option instanceof HTMLElement)) {
            return;
        }

        const selected_item = this.component_dropdown.querySelector(
            "#" + selected_option.dataset.itemId
        );

        if (selected_item) {
            this.replacePlaceholderWithCurrentSelection(selected_item, placeholder_element);
            selected_item.setAttribute("aria-selected", "true");
        }
    }

    private createCurrentSelectionElement(item: Element): Element {
        const selected_value = document.createElement("span");
        selected_value.classList.add("list-picker-selected-value");
        selected_value.setAttribute("data-item-id", item.id);
        selected_value.setAttribute("aria-readonly", "true");

        selected_value.innerHTML = sanitize(item.innerHTML);
        selected_value.appendChild(this.createRemoveCurrentSelectionButton(item));

        return selected_value;
    }

    private replacePlaceholderWithCurrentSelection(item: Element, placeholder: Element): void {
        const selected_value = this.createCurrentSelectionElement(item);

        this.selection_container.appendChild(selected_value);
        this.selection_container.removeChild(placeholder);
    }

    private replacePreviousSelectionWithCurrentOne(item: Element, selection_value: Element): void {
        const currently_selected_item = this.component_dropdown.querySelector(
            "[aria-selected=true]"
        );

        if (!(currently_selected_item instanceof Element)) {
            return;
        }

        currently_selected_item.setAttribute("aria-selected", "false");

        this.selection_container.removeChild(selection_value);
        this.selection_container.appendChild(this.createCurrentSelectionElement(item));
    }

    private createRemoveCurrentSelectionButton(item: Element): Element {
        const remove_value_button = document.createElement("span");
        remove_value_button.classList.add("list-picker-selected-value-remove-button");
        remove_value_button.innerHTML = sanitize("&times");

        remove_value_button.addEventListener("click", (event: Event) => {
            event.preventDefault();
            event.cancelBubble = true;

            this.replaceCurrentValueWithPlaceholder(item);
            this.dropdown_toggler.openListPicker();
        });

        return remove_value_button;
    }

    private replaceCurrentValueWithPlaceholder(item_to_unselect: Element): void {
        this.selection_container.innerHTML = "";
        this.selection_container.appendChild(this.placeholder_element);
        item_to_unselect.setAttribute("aria-selected", "false");

        const option_to_unselect = this.source_select_box.querySelector(
            `[data-item-id=${item_to_unselect.id}]`
        );

        if (option_to_unselect) {
            option_to_unselect.removeAttribute("selected");
        }
    }
}
