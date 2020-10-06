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

import { ListPickerItem } from "../type";
import { findListPickerItemInItemMap } from "./list-picker-items-helper";

describe("list-picker-items-helper", () => {
    let item_map: Map<string, ListPickerItem>;

    beforeEach(() => {
        item_map = new Map([
            ["item-1", { id: "item-1" } as ListPickerItem],
            ["item-2", { id: "item-2" } as ListPickerItem],
            ["item-3", { id: "item-3" } as ListPickerItem],
        ]);
    });

    it("Given an item map and an item id, Then it should return the corresponding ListPickerItem", () => {
        const item = findListPickerItemInItemMap(item_map, "item-2");

        expect(item.id).toEqual("item-2");
    });

    it("should throw an error when the given item id does not reference a ListPickerItem", () => {
        expect(() =>
            findListPickerItemInItemMap(item_map, "the-item-that-does-not-exists")
        ).toThrowError("Item with id the-item-that-does-not-exists not found in item map");
    });
});
