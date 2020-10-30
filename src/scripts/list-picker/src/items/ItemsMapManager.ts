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

import { ListPickerItem } from "../type";
import { generateItemMapBasedOnSourceSelectOptions } from "../helpers/static-list-helper";

export class ItemsMapManager {
    private items_map: Map<string, ListPickerItem>;

    constructor(private readonly source_select_box: HTMLSelectElement) {
        this.items_map = generateItemMapBasedOnSourceSelectOptions(source_select_box);
    }

    public getListPickerItems(): Array<ListPickerItem> {
        return Array.from(this.items_map.values());
    }

    public findListPickerItemInItemMap(item_id: string): ListPickerItem {
        const list_item = this.items_map.get(item_id);
        if (!list_item) {
            throw new Error(`Item with id ${item_id} not found in item map`);
        }
        return list_item;
    }

    public rebuildItemsMap(): void {
        this.items_map = generateItemMapBasedOnSourceSelectOptions(this.source_select_box);
    }

    public getItemWithValue(value: string): ListPickerItem | null {
        return this.getListPickerItems().find((item) => item.value === value) ?? null;
    }
}
