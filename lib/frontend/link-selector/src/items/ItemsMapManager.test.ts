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

import { ItemsMapManager } from "./ItemsMapManager";
import { ListItemMapBuilder } from "./ListItemMapBuilder";
import type { HTMLTemplateResult } from "lit/html.js";
import { html } from "lit/html.js";
import { GroupCollectionBuilder } from "../../tests/builders/GroupCollectionBuilder";

describe("ItemsMapManager", () => {
    let items_manager: ItemsMapManager;

    beforeEach(() => {
        items_manager = new ItemsMapManager(new ListItemMapBuilder());
        items_manager.refreshItemsMap(GroupCollectionBuilder.withTwoGroups());
    });

    describe("findLinkSelectorItemInItemMap", () => {
        it("Given an item map and an item id, Then it should return the corresponding LinkSelectorItem", () => {
            const item = items_manager.findLinkSelectorItemInItemMap(
                "link-selector-item-group1-value_2"
            );

            expect(item.id).toBe("link-selector-item-group1-value_2");
        });

        it("should throw an error when the given item id does not reference a LinkSelectorItem", () => {
            expect(() =>
                items_manager.findLinkSelectorItemInItemMap("the-item-that-does-not-exist")
            ).toThrowError("Item with id the-item-that-does-not-exist not found in item map");
        });
    });

    describe("getItemWithValue", () => {
        it("should return the corresponding LinkSelectorItem", () => {
            expect(items_manager.getItemWithValue("value_5")).toStrictEqual({
                element: expect.any(Element),
                group_id: "group2",
                id: "link-selector-item-group2-value_5",
                is_disabled: false,
                is_selected: false,
                target_option: expect.any(HTMLOptionElement),
                template: buildTemplateResult("Value 5"),
                value: "value_5",
            });
        });

        it("should return null if there is no item with this value", () => {
            expect(items_manager.getItemWithValue("value_25")).toBeNull();
        });
    });

    it("gets link-selector items", () => {
        expect(items_manager.getLinkSelectorItems().length).toBeGreaterThan(0);
    });
});

function buildTemplateResult(value: string): HTMLTemplateResult {
    return html`
        ${value}
    `;
}
