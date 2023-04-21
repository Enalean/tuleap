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
import { RenderedItemStub } from "../../tests/stubs/RenderedItemStub";

const noop = (): void => {
    //Do nothing
};

describe("SelectionManager", () => {
    let selection_element: SelectionElement,
        manager: SelectionManager,
        items_map_manager: ItemsMapManager,
        item_1: RenderedItem;

    beforeEach(() => {
        items_map_manager = new ItemsMapManager(ListItemMapBuilder(TemplatingCallbackStub.build()));
        selection_element = {
            getSelection: () => RenderedItemStub.withDefaults(),
            clearSelection: noop,
            selectItem: (item) => {
                if (item) {
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
    });

    describe("processSelection", () => {
        it(`finds the corresponding item in the Item Map
            and asks the selection element to select it`, () => {
            const select = vi.spyOn(selection_element, "selectItem");

            manager.processSelection(item_1.element);

            expect(select).toHaveBeenCalledWith(item_1);
        });
    });

    describe("updateSelectionAfterDropdownContentChange", () => {
        it(`when an item is selected but there is no item in the items map anymore,
            then it asks the selection element to clear`, () => {
            const clear = vi.spyOn(selection_element, "clearSelection");
            manager.processSelection(item_1.element);
            items_map_manager.refreshItemsMap(GroupCollectionBuilder.withEmptyGroup());
            manager.updateSelectionAfterDropdownContentChange();

            expect(clear).toHaveBeenCalled();
        });

        it(`when an item has been selected, and is still available in the new items,
            then it should re-select it (to deal with new Element references)`, () => {
            vi.spyOn(selection_element, "getSelection").mockReturnValue(item_1);
            manager.processSelection(item_1.element);
            const select = vi.spyOn(selection_element, "selectItem");

            const groups = GroupCollectionBuilder.withSingleGroup({
                items: [
                    { id: "value-0", value: { id: 0 }, is_disabled: false },
                    { id: "value-1", value: item_1.value, is_disabled: false },
                ],
            });
            items_map_manager.refreshItemsMap(groups);
            manager.updateSelectionAfterDropdownContentChange();

            const new_item_1 = items_map_manager.findLazyboxItemInItemMap(item_1.id);
            expect(select).toHaveBeenCalledWith(new_item_1);
        });
    });
});
