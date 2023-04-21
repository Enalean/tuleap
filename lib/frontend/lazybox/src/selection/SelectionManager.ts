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

import type { ManageSelection } from "../type";
import type { ItemsMapManager } from "../items/ItemsMapManager";
import type { SelectionElement } from "./SelectionElement";

export class SelectionManager implements ManageSelection {
    constructor(
        private readonly selection_element: SelectionElement,
        private readonly items_map_manager: ItemsMapManager
    ) {}

    public processSelection(item: Element): void {
        if (!(item instanceof HTMLElement) || !item.dataset.itemId) {
            throw new Error("No data-item-id found on element.");
        }

        const list_item = this.items_map_manager.findLazyboxItemInItemMap(item.dataset.itemId);
        this.selection_element.selectItem(list_item);
    }

    public updateSelectionAfterDropdownContentChange(): void {
        const available_items = this.items_map_manager.getLazyboxItems();
        if (available_items.length === 0) {
            this.clearSelection();
            return;
        }
        const previous_selection = this.selection_element.getSelection();
        if (!previous_selection) {
            return;
        }
        const item = this.items_map_manager.getItemWithValue(previous_selection.value);
        if (item) {
            this.selection_element.selectItem(item);
        }
    }

    public clearSelection(): void {
        this.selection_element.clearSelection();
    }

    public hasSelection(): boolean {
        return this.selection_element.hasSelection();
    }

    public setSelection(): void {
        // Not implemented yet
    }
}
