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
import type { RenderedItem, LazyboxSelectionStateSingle, ManageSelection } from "../type";
import type { ItemsMapManager } from "../items/ItemsMapManager";
import { html, render } from "lit/html.js";
import type { LazyboxSelectionCallback } from "../type";
import { buildClearSelectionButtonElement } from "./templates/clear-selection-button-template";
import type { SearchInput } from "../SearchInput";

export class SelectionManager implements ManageSelection {
    private selection_state: LazyboxSelectionStateSingle | null;

    constructor(
        private readonly source_select_box: HTMLSelectElement,
        private readonly dropdown_element: Element,
        private readonly selection_element: Element,
        private readonly placeholder_element: Element,
        private readonly dropdown_manager: DropdownManager,
        private readonly items_map_manager: ItemsMapManager,
        private readonly callback: LazyboxSelectionCallback,
        private readonly search_field: SearchInput
    ) {
        this.selection_state = null;
    }

    public processSelection(item: Element): void {
        if (!(item instanceof HTMLElement) || !item.dataset.itemId) {
            throw new Error("No data-item-id found on element.");
        }

        const list_item = this.items_map_manager.findLazyboxItemInItemMap(item.dataset.itemId);
        if (list_item.is_selected) {
            // We won't unselect it
            return;
        }

        if (list_item.is_disabled) {
            this.replaceCurrentValueWithPlaceholder(list_item);
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

    public updateSelectionAfterDropdownContentChange(): void {
        const available_items = this.items_map_manager.getLazyboxItems();
        if (available_items.length === 0) {
            this.clearSelection();
            return;
        }

        if (this.selection_state === null) {
            return;
        }

        const item = this.items_map_manager.getItemWithValue(
            this.selection_state.selected_item.value
        );

        if (item) {
            this.processSelection(item.element);
        }
    }

    private createCurrentSelectionElement(item: RenderedItem): DocumentFragment {
        const document_fragment = document.createDocumentFragment();
        render(
            html`
                <span class="lazybox-selected-value" aria-readonly="true">${item.template}</span>
            `,
            document_fragment
        );

        return document_fragment;
    }

    private replacePlaceholderWithCurrentSelection(item: RenderedItem, placeholder: Element): void {
        const selected_value = this.createCurrentSelectionElement(item);

        this.selection_element.appendChild(selected_value);
        this.selection_element.appendChild(this.createRemoveCurrentSelectionButton());
        this.selection_element.removeChild(placeholder);

        item.is_selected = true;
        item.element.setAttribute("aria-selected", "true");
        this.callback(item.value);

        this.selection_state = {
            selected_item: item,
            selected_value_element: selected_value,
        };
    }

    private replacePreviousSelectionWithCurrentOne(
        newly_selected_item: RenderedItem,
        selection_state: LazyboxSelectionStateSingle
    ): void {
        const new_selected_value_element = this.createCurrentSelectionElement(newly_selected_item);

        selection_state.selected_item.is_selected = false;
        selection_state.selected_item.element.setAttribute("aria-selected", "false");

        newly_selected_item.is_selected = true;
        newly_selected_item.element.setAttribute("aria-selected", "true");

        this.callback(newly_selected_item.value);
        this.selection_element.innerHTML = "";
        this.selection_element.appendChild(new_selected_value_element);
        this.selection_element.appendChild(this.createRemoveCurrentSelectionButton());
        this.selection_state = {
            selected_item: newly_selected_item,
            selected_value_element: new_selected_value_element,
        };
    }

    private createRemoveCurrentSelectionButton(): Element {
        return buildClearSelectionButtonElement((event: Event) => {
            event.stopPropagation();

            if (this.source_select_box.disabled) {
                return;
            }

            this.search_field.clear();
            this.clearSelection();
            this.dropdown_manager.openLazybox();
        });
    }

    public clearSelection(): void {
        if (!this.selection_state) {
            return;
        }
        this.replaceCurrentValueWithPlaceholder(this.selection_state.selected_item);
    }

    private replaceCurrentValueWithPlaceholder(item_to_unselect: RenderedItem): void {
        this.selection_element.innerHTML = "";
        this.selection_element.appendChild(this.placeholder_element);
        this.source_select_box.selectedIndex = -1;
        item_to_unselect.element.setAttribute("aria-selected", "false");
        item_to_unselect.is_selected = false;

        this.callback(null);
        this.selection_state = null;
    }

    public hasSelection(): boolean {
        return this.selection_state !== null;
    }

    public setSelection(): void {
        // Not implemented yet
    }
}
