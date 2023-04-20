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

import type {
    ManageSelection,
    LazyboxSelectionStateMultiple,
    RenderedItem,
    LazyboxSelectionBadgeCallback,
} from "../type";
import type { DropdownManager } from "../dropdown/DropdownManager";
import type { ItemsMapManager } from "../items/ItemsMapManager";
import type { LazyboxSelectionCallback } from "../type";
import type { RemoveCurrentSelectionCallback } from "./templates/clear-selection-button-template";
import { buildClearSelectionButtonElement } from "./templates/clear-selection-button-template";
import type { SearchInput } from "../SearchInput";

export class MultipleSelectionManager implements ManageSelection {
    private readonly selection_state: LazyboxSelectionStateMultiple;
    private readonly clear_selection_state_button_element: Element;

    constructor(
        private readonly source_select_box: HTMLSelectElement,
        private readonly selection_element: Element,
        private readonly search_field: HTMLElement & SearchInput,
        private readonly placeholder_text: string,
        private readonly dropdown_manager: DropdownManager,
        private readonly items_map_manager: ItemsMapManager,
        private readonly callback: LazyboxSelectionCallback,
        private readonly selection_badge_callback: LazyboxSelectionBadgeCallback
    ) {
        this.selection_state = {
            selected_items: new Map(),
            selected_values_elements: new Map(),
        };
        this.clear_selection_state_button_element = buildClearSelectionButtonElement(
            this.getOnRemoveAllValuesCallback()
        );
        this.observeBackspaceKeyPress();
    }

    public processSelection(item: Element): void {
        if (!(item instanceof HTMLElement) || !item.dataset.itemId) {
            throw new Error("No data-item-id found on element.");
        }

        const list_item = this.items_map_manager.findLazyboxItemInItemMap(item.dataset.itemId);
        this.selectItem(list_item);
    }

    private selectItem(list_item: RenderedItem): void {
        if (list_item.is_disabled) {
            return;
        }
        if (list_item.is_selected) {
            // We won't unselect it
            this.search_field.clear();

            return;
        }

        this.selection_state.selected_items.set(list_item.id, list_item);
        const badge = this.createItemBadgeElement(list_item);
        this.selection_state.selected_values_elements.set(list_item.id, badge);

        this.selection_element.insertBefore(badge, this.search_field.parentElement);
        list_item.is_selected = true;
        list_item.element.setAttribute("aria-selected", "true");

        this.applyChangesPostSelectionStateChange();
        this.search_field.clear();
    }

    private togglePlaceholder(): void {
        if (!this.hasSelection()) {
            this.search_field.placeholder = this.placeholder_text;
            return;
        }

        this.search_field.placeholder = "";
    }

    private toggleClearValuesButton(): void {
        if (this.source_select_box.disabled) {
            return;
        }

        if (!this.hasSelection()) {
            this.removeClearSelectionStateButton();
            return;
        }

        if (!this.selection_element.contains(this.clear_selection_state_button_element)) {
            this.selection_element.insertAdjacentElement(
                "beforeend",
                this.clear_selection_state_button_element
            );
        }
    }

    private removeClearSelectionStateButton(): void {
        if (!this.selection_element.contains(this.clear_selection_state_button_element)) {
            return;
        }
        this.selection_element.removeChild(this.clear_selection_state_button_element);
    }

    private getOnRemoveAllValuesCallback(): RemoveCurrentSelectionCallback {
        return (event: Event): void => {
            event.stopPropagation();

            this.clearSelectionState();
            this.dropdown_manager.openLazybox();
        };
    }

    private applyChangesPostSelectionStateChange(): void {
        this.togglePlaceholder();
        this.toggleClearValuesButton();
        this.callback(
            Array.from(this.selection_state.selected_items.values()).map(({ value }) => value)
        );
    }

    private createItemBadgeElement(list_item: RenderedItem): Element {
        const badge = this.selection_badge_callback(list_item);
        badge.setAttribute("data-test", "selected-value-badge");

        badge.addEventListener("remove-badge", (): void => {
            if (this.source_select_box.disabled) {
                return;
            }

            this.removeListItemFromSelection(list_item);
            this.applyChangesPostSelectionStateChange();
            this.dropdown_manager.openLazybox();
        });

        return badge;
    }

    private removeListItemFromSelection(list_item: RenderedItem): void {
        const badge = this.selection_state.selected_values_elements.get(list_item.id);
        const selected_item = this.selection_state.selected_items.get(list_item.id);

        if (!badge || !selected_item) {
            throw new Error("Item not found in selection state.");
        }

        this.selection_element.removeChild(badge);
        this.selection_state.selected_values_elements.delete(list_item.id);
        this.selection_state.selected_items.delete(list_item.id);

        list_item.is_selected = false;
        list_item.element.removeAttribute("aria-selected");
    }

    private clearSelectionState(): void {
        Array.from(this.selection_state.selected_items.values()).forEach((item) => {
            this.removeListItemFromSelection(item);
        });
        this.applyChangesPostSelectionStateChange();
    }

    public hasSelection(): boolean {
        return this.selection_state.selected_items.size !== 0;
    }

    public clearSelection(): void {
        if (!this.hasSelection()) {
            return;
        }
        this.clearSelectionState();
    }

    public updateSelectionAfterDropdownContentChange(): void {
        if (!this.hasSelection()) {
            return;
        }

        Array.from(this.selection_state.selected_items.values()).forEach((item) => {
            const item_in_map = this.items_map_manager.getItemWithValue(item.value);
            if (!item_in_map) {
                return;
            }

            item_in_map.is_selected = true;
            item_in_map.element.setAttribute("aria-selected", "true");
        });
    }

    public setSelection(selection: ReadonlyArray<RenderedItem>): void {
        this.clearSelection();

        selection.forEach((item) => {
            this.selectItem(item);
        });
    }

    private observeBackspaceKeyPress(): void {
        this.search_field.addEventListener("backspace-pressed", () => {
            if (!this.hasSelection()) {
                return;
            }
            const last_selected_item = Array.from(this.selection_state.selected_items.values())[
                this.selection_state.selected_items.size - 1
            ];

            this.removeListItemFromSelection(last_selected_item);
            this.applyChangesPostSelectionStateChange();
        });
    }
}
