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

import { describe, it, expect, beforeEach } from "vitest";
import { ItemsMapManager } from "./ItemsMapManager";
import { ListItemMapBuilder } from "./ListItemMapBuilder";
import { GroupCollectionBuilder } from "../../tests/builders/GroupCollectionBuilder";
import { TemplatingCallbackStub } from "../../tests/stubs/TemplatingCallbackStub";

describe("ItemsMapManager", () => {
    let items_manager: ItemsMapManager, value_5: unknown;

    beforeEach(() => {
        items_manager = new ItemsMapManager(ListItemMapBuilder(TemplatingCallbackStub.build()));
        const groups = GroupCollectionBuilder.withTwoGroups();
        value_5 = groups[1].items[2].value;
        items_manager.refreshItemsMap(groups);
    });

    describe("findLazyboxItemInItemMap", () => {
        it("Given an item map and an item id, Then it should return the corresponding LazyboxItem", () => {
            const item = items_manager.findLazyboxItemInItemMap("lazybox-item-group1-value-2");

            expect(item.id).toBe("lazybox-item-group1-value-2");
        });

        it("should throw an error when the given item id does not reference a LazyboxItem", () => {
            expect(() =>
                items_manager.findLazyboxItemInItemMap("the-item-that-does-not-exist")
            ).toThrowError("Item with id the-item-that-does-not-exist not found in item map");
        });
    });

    describe("getItemWithValue", () => {
        it("should return the corresponding LazyboxItem", () => {
            expect(items_manager.getItemWithValue(value_5)).toStrictEqual({
                element: expect.any(Element),
                group_id: "group2",
                id: "lazybox-item-group2-value-5",
                is_disabled: true,
                is_selected: false,
                template: expect.anything(),
                value: value_5,
            });
        });

        it("should return null if there is no item with this value", () => {
            expect(items_manager.getItemWithValue("value_25")).toBeNull();
        });
    });

    it("gets lazybox items", () => {
        expect(items_manager.getLazyboxItems().length).toBeGreaterThan(0);
    });
});
