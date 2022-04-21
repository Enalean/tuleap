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

import type { RenderedItem, RenderedItemMap } from "../type";
import type { ListItemMapBuilder } from "./ListItemMapBuilder";
import type { GroupCollection } from "./GroupCollection";

export class ItemsMapManager {
    private items_map!: RenderedItemMap;

    constructor(private readonly list_item_builder: ListItemMapBuilder) {}

    public getLinkSelectorItems(): ReadonlyArray<RenderedItem> {
        return [...this.items_map.values()];
    }

    public findLinkSelectorItemInItemMap(item_id: string): RenderedItem {
        const list_item = this.items_map.get(item_id);
        if (!list_item) {
            throw new Error(`Item with id ${item_id} not found in item map`);
        }
        return list_item;
    }

    public refreshItemsMap(groups: GroupCollection): void {
        this.items_map = this.list_item_builder.buildLinkSelectorItemsMap(groups);
    }

    public getItemWithValue(value: string): RenderedItem | null {
        return this.getLinkSelectorItems().find((item) => item.value === value) ?? null;
    }
}
