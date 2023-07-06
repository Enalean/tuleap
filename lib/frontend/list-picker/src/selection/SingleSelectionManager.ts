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

import type { DropdownManager } from "../dropdown/DropdownManager";
import type { ListPickerItem, ListPickerSelectionStateSingle, SelectionManager } from "../type";
import type { ItemsMapManager } from "../items/ItemsMapManager";
import { html, render } from "lit/html.js";

const markItemSelected = (item: ListPickerItem): void => {
    item.is_selected = true;
    item.element.setAttribute("aria-selected", "true");
    item.target_option.setAttribute("selected", "selected");
    item.target_option.selected = true;
};

const markItemUnselected = (item: ListPickerItem): void => {
    item.is_selected = false;
    item.element.setAttribute("aria-selected", "false");
    item.target_option.removeAttribute("selected");
    item.target_option.selected = false;
};

export class SingleSelectionManager implements SelectionManager {
    private selection_state: ListPickerSelectionStateSingle | null;

    constructor(
        private readonly source_select_box: HTMLSelectElement,
        private readonly dropdown_element: Element,
        private readonly selection_element: Element,
        private readonly placeholder_element: Element,
        private readonly dropdown_manager: DropdownManager,
        private readonly items_map_manager: ItemsMapManager
    ) {
        this.selection_state = null;
    }

    public processSelection(item: Element): void {
        if (!(item instanceof HTMLElement) || !item.dataset.itemId) {
            throw new Error("No data-item-id found on element.");
        }

        const list_item = this.items_map_manager.findListPickerItemInItemMap(item.dataset.itemId);
        this.selectListPickerItem(list_item, true);
    }

    private selectListPickerItem(list_item: ListPickerItem, should_dispatch_change: boolean): void {
        if (list_item.is_selected) {
            // We won't unselect it
            return;
        }
        if (list_item.is_disabled) {
            this.showPlaceholder();
            this.clearSelection();
            return;
        }
        if (this.selection_element.contains(this.placeholder_element)) {
            this.replacePlaceholderWithCurrentSelection(list_item);
            if (should_dispatch_change) {
                this.source_select_box.dispatchEvent(new Event("change", { bubbles: true }));
            }
            return;
        }
        if (this.selection_state !== null) {
            this.replacePreviousSelectionWithCurrentOne(list_item, this.selection_state);
            if (should_dispatch_change) {
                this.source_select_box.dispatchEvent(new Event("change", { bubbles: true }));
            }
            return;
        }

        throw new Error("Nothing has been selected");
    }

    public initSelection(): void {
        const item_to_select = this.readSelectedItemFromSelectElement();
        if (item_to_select) {
            this.replacePlaceholderWithCurrentSelection(item_to_select);
        }
    }

    private readSelectedItemFromSelectElement(): ListPickerItem | null {
        const item_to_select = this.items_map_manager.getItemWithValue(
            this.source_select_box.value
        );
        if (item_to_select) {
            return item_to_select;
        }

        const selected_option = this.source_select_box.selectedOptions.item(0);
        if (!selected_option || !selected_option.dataset.itemId) {
            return null;
        }
        return this.items_map_manager.findListPickerItemInItemMap(selected_option.dataset.itemId);
    }

    public handleBackspaceKey(): void {
        // Do nothing, we are in single selection mode
    }

    public resetAfterChangeInOptions(): void {
        const new_selected_item = this.readSelectedItemFromSelectElement();
        if (!new_selected_item) {
            this.showPlaceholder();
            this.clearSelection();
            return;
        }
        this.selectListPickerItem(new_selected_item, false);
    }

    private createCurrentSelectionElement(item: ListPickerItem): DocumentFragment {
        const document_fragment = document.createDocumentFragment();
        render(
            html`
                <span
                    class="list-picker-selected-value"
                    data-item-id="${item.id}"
                    aria-readonly="true"
                >
                    ${item.template}
                </span>
            `,
            document_fragment
        );

        return document_fragment;
    }

    private replacePlaceholderWithCurrentSelection(item: ListPickerItem): void {
        this.selection_element.replaceChildren(
            this.createCurrentSelectionElement(item),
            this.createRemoveCurrentSelectionButton()
        );

        markItemSelected(item);
        this.selection_state = { selected_item: item };
    }

    private replacePreviousSelectionWithCurrentOne(
        newly_selected_item: ListPickerItem,
        selection_state: ListPickerSelectionStateSingle
    ): void {
        this.selection_element.replaceChildren(
            this.createCurrentSelectionElement(newly_selected_item),
            this.createRemoveCurrentSelectionButton()
        );

        markItemUnselected(selection_state.selected_item);
        markItemSelected(newly_selected_item);
        this.selection_state = { selected_item: newly_selected_item };
    }

    private createRemoveCurrentSelectionButton(): Element {
        const remove_value_button = document.createElement("span");
        remove_value_button.classList.add("list-picker-selected-value-remove-button");
        remove_value_button.innerText = "×";

        if (this.source_select_box.disabled) {
            return remove_value_button;
        }

        remove_value_button.addEventListener("pointerdown", (event: Event) => {
            event.preventDefault();
            event.cancelBubble = true;

            this.showPlaceholder();
            this.clearSelection();
            this.dropdown_manager.openListPicker();
            this.source_select_box.dispatchEvent(new Event("change", { bubbles: true }));
        });

        return remove_value_button;
    }

    private clearSelection(): void {
        if (!this.selection_state) {
            return;
        }
        this.selection_state.selected_item.element.setAttribute("aria-selected", "false");
        const option = this.selection_state.selected_item.target_option;
        option.removeAttribute("selected");
        option.selected = false;
        this.selection_state.selected_item.is_selected = false;
        this.selection_state = null;
    }

    private showPlaceholder(): void {
        this.selection_element.replaceChildren(this.placeholder_element);
    }
}
