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

import { ListPickerItem, ListPickerItemMap } from "../type";
import { ListItemMapBuilder } from "./ListItemMapBuilder";

export class ItemsMapManager {
    private items_map!: ListPickerItemMap;

    constructor(private readonly list_item_builder: ListItemMapBuilder) {}

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

    public async refreshItemsMap(): Promise<void> {
        this.items_map = await this.list_item_builder.buildListPickerItemsMap();
    }

    public getItemWithValue(value: string): ListPickerItem | null {
        return this.getListPickerItems().find((item) => item.value === value) ?? null;
    }
}
