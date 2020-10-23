/*
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
import { DropdownToggler } from "../dropdown/DropdownToggler";
import { findListPickerItemInItemMap } from "../helpers/list-picker-items-helper";
import { ListPickerItem, ListPickerSelectionStateSingle, SelectionManager } from "../type";

export class SingleSelectionManager implements SelectionManager {
    private selection_state: ListPickerSelectionStateSingle | null;

    constructor(
        private readonly source_select_box: HTMLSelectElement,
        private readonly dropdown_element: Element,
        private readonly selection_element: Element,
        private readonly placeholder_element: Element,
        private readonly dropdown_toggler: DropdownToggler,
        private readonly item_map: Map<string, ListPickerItem>
    ) {
        this.selection_state = null;
    }

    public processSelection(item: Element): void {
        const list_item = findListPickerItemInItemMap(this.item_map, item.id);
        if (list_item.is_selected) {
            // We won't unselect it
            return;
        }

        if (this.selection_element.contains(this.placeholder_element)) {
            this.replacePlaceholderWithCurrentSelection(list_item, this.placeholder_element);
            return;
        }

        if (this.selection_state !== null) {
            this.replacePreviousSelectionWithCurrentOne(list_item, this.selection_state);
            return;
        }

        throw new Error("Nothing has been selected");
    }

    public initSelection(): void {
        const selected_option = this.source_select_box.querySelector("option[selected]");

        if (!(selected_option instanceof HTMLElement) || !selected_option.dataset.itemId) {
            return;
        }

        this.replacePlaceholderWithCurrentSelection(
            findListPickerItemInItemMap(this.item_map, selected_option.dataset.itemId),
            this.placeholder_element
        );
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public handleBackspaceKey(event: KeyboardEvent): void {
        // Do nothing, we are in single selection mode
    }

    private createCurrentSelectionElement(item: ListPickerItem): Element {
        const selected_value = document.createElement("span");
        selected_value.classList.add("list-picker-selected-value");
        selected_value.setAttribute("data-item-id", item.id);
        selected_value.setAttribute("aria-readonly", "true");

        selected_value.appendChild(document.createTextNode(item.template));
        selected_value.appendChild(this.createRemoveCurrentSelectionButton(item));

        return selected_value;
    }

    private replacePlaceholderWithCurrentSelection(
        item: ListPickerItem,
        placeholder: Element
    ): void {
        const selected_value = this.createCurrentSelectionElement(item);

        this.selection_element.appendChild(selected_value);
        this.selection_element.removeChild(placeholder);

        item.is_selected = true;
        item.element.setAttribute("aria-selected", "true");
        item.target_option.setAttribute("selected", "selected");
        this.source_select_box.dispatchEvent(new Event("change"));

        this.selection_state = {
            selected_item: item,
            selected_value_element: selected_value,
        };
    }

    private replacePreviousSelectionWithCurrentOne(
        newly_selected_item: ListPickerItem,
        selection_state: ListPickerSelectionStateSingle
    ): void {
        const new_selected_value_element = this.createCurrentSelectionElement(newly_selected_item);

        selection_state.selected_item.is_selected = false;
        selection_state.selected_item.element.setAttribute("aria-selected", "false");
        selection_state.selected_item.target_option.removeAttribute("selected");

        newly_selected_item.is_selected = true;
        newly_selected_item.element.setAttribute("aria-selected", "true");
        newly_selected_item.target_option.setAttribute("selected", "selected");

        this.source_select_box.dispatchEvent(new Event("change"));
        this.selection_element.removeChild(selection_state.selected_value_element);
        this.selection_element.appendChild(new_selected_value_element);
        this.selection_state = {
            selected_item: newly_selected_item,
            selected_value_element: new_selected_value_element,
        };
    }

    private createRemoveCurrentSelectionButton(item: ListPickerItem): Element {
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

    private replaceCurrentValueWithPlaceholder(item_to_unselect: ListPickerItem): void {
        this.selection_element.innerHTML = "";
        this.selection_element.appendChild(this.placeholder_element);
        item_to_unselect.element.setAttribute("aria-selected", "false");
        item_to_unselect.target_option.removeAttribute("selected");
        item_to_unselect.is_selected = false;
        this.source_select_box.dispatchEvent(new Event("change"));

        this.selection_state = null;
    }
}
