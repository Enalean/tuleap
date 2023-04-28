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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { SelectionManager } from "./SelectionManager";
import { ItemsMapManager } from "../items/ItemsMapManager";
import type { RenderedItem } from "../type";
import { ListItemMapBuilder } from "../items/ListItemMapBuilder";
import { GroupCollectionBuilder } from "../../tests/builders/GroupCollectionBuilder";
import { TemplatingCallbackStub } from "../../tests/stubs/TemplatingCallbackStub";
import type { SelectionElement } from "./SelectionElement";

const noop = (): void => {
    //Do nothing
};

describe("SelectionManager", () => {
    let selection_element: SelectionElement,
        manager: SelectionManager,
        items_map_manager: ItemsMapManager,
        item_1: RenderedItem,
        item_2: RenderedItem;

    beforeEach(() => {
        items_map_manager = new ItemsMapManager(ListItemMapBuilder(TemplatingCallbackStub.build()));
        selection_element = {
            getSelection: () => {
                const selection: ReadonlyArray<RenderedItem> = [];
                return selection;
            },
            clearSelection: noop,
            selectItem: (item) => {
                if (item) {
                    //Do nothing
                }
            },
            replaceSelection(items: ReadonlyArray<RenderedItem>) {
                if (items) {
                    //Do nothing
                }
            },
        } as SelectionElement;
        manager = new SelectionManager(selection_element, items_map_manager);
        items_map_manager.refreshItemsMap(
            GroupCollectionBuilder.withSingleGroup({
                items: [
                    { id: "value-0", value: { id: 0 }, is_disabled: false },
                    { id: "value-1", value: { id: 1 }, is_disabled: false },
                    { id: "value-2", value: { id: 2 }, is_disabled: false },
                    { id: "value-3", value: { id: 3 }, is_disabled: false },
                ],
            })
        );
        item_1 = items_map_manager.findLazyboxItemInItemMap("lazybox-item-value-1");
        item_2 = items_map_manager.findLazyboxItemInItemMap("lazybox-item-value-2");
    });

    describe("processSelection()", () => {
        it(`finds the corresponding item in the Item Map
            and asks the selection element to select it`, () => {
            const select = vi.spyOn(selection_element, "selectItem");

            manager.processSelection(item_1.element);

            expect(select).toHaveBeenCalledWith(item_1);
        });
    });

    describe(`setSelection()`, () => {
        it(`replaces the previous selection by the new one`, () => {
            const replaceSelection = vi.spyOn(selection_element, "replaceSelection");

            const items = [item_1, item_2];
            manager.setSelection(items);

            expect(replaceSelection).toHaveBeenCalledWith(items);
        });
    });

    describe("updateSelectionAfterDropdownContentChange()", () => {
        it(`when items have been selected, and are still available in the new dropdown content,
            then it should set their "selected" property and assign their aria-selected attribute`, () => {
            vi.spyOn(selection_element, "getSelection").mockReturnValue([item_1, item_2]);
            manager.setSelection([item_1, item_2]);

            const groups = GroupCollectionBuilder.withSingleGroup({
                items: [
                    { id: "value-0", value: { id: 0 }, is_disabled: false },
                    { id: "value-1", value: item_1.value, is_disabled: false },
                    { id: "value-2", value: item_2.value, is_disabled: false },
                ],
            });
            items_map_manager.refreshItemsMap(groups);
            manager.updateSelectionAfterDropdownContentChange();

            const new_item_1 = items_map_manager.findLazyboxItemInItemMap(item_1.id);
            const new_item_2 = items_map_manager.findLazyboxItemInItemMap(item_2.id);
            expect(new_item_1.is_selected).toBe(true);
            expect(new_item_1.element.getAttribute("aria-selected")).toBe("true");
            expect(new_item_2.is_selected).toBe(true);
            expect(new_item_2.element.getAttribute("aria-selected")).toBe("true");
        });
    });
});
