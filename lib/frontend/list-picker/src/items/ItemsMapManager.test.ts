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

import { describe, beforeEach, it, expect } from "vitest";
import { ItemsMapManager } from "./ItemsMapManager";
import { appendGroupedOptionsToSourceSelectBox } from "../test-helpers/select-box-options-generator";
import { ListItemMapBuilder } from "./ListItemMapBuilder";
import type { TemplateResult } from "lit/html.js";
import { html } from "lit/html.js";

describe("ItemsMapManager", () => {
    let items_manager: ItemsMapManager, source_select_box: HTMLSelectElement;

    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();
        source_select_box = doc.createElement("select");
        appendGroupedOptionsToSourceSelectBox(source_select_box);
        items_manager = new ItemsMapManager(new ListItemMapBuilder(source_select_box));
        items_manager.refreshItemsMap();
    });

    describe("findListPickerItemInItemMap", () => {
        it("Given an item map and an item id, Then it should return the corresponding ListPickerItem", () => {
            const item = items_manager.findListPickerItemInItemMap(
                "list-picker-item-group1-value_2",
            );

            expect(item.id).toBe("list-picker-item-group1-value_2");
        });

        it("should throw an error when the given item id does not reference a ListPickerItem", () => {
            expect(() =>
                items_manager.findListPickerItemInItemMap("the-item-that-does-not-exist"),
            ).toThrowError("Item with id the-item-that-does-not-exist not found in item map");
        });
    });

    describe("getItemWithValue", () => {
        it("should return the corresponding ListPickerItem", () => {
            expect(items_manager.getItemWithValue("value_5")).toEqual({
                element: expect.any(Element),
                group_id: "group2",
                id: "list-picker-item-group2-value_5",
                is_disabled: true,
                is_selected: false,
                target_option: expect.any(HTMLOptionElement),
                template: buildTemplateResult("Value 5"),
                label: "Value 5",
                value: "value_5",
            });
        });

        it("should return null if there is no item with this value", () => {
            expect(items_manager.getItemWithValue("value_25")).toBeNull();
        });
    });

    it("gets list picker items", () => {
        expect(items_manager.getListPickerItems().length).toBeGreaterThan(0);
    });

    function buildTemplateResult(value: string): TemplateResult {
        return html`${value}`;
    }
});
