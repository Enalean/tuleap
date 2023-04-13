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

import type { MockedFunction } from "vitest";
import { describe, beforeEach, expect, it, vi } from "vitest";
import { SelectionManager } from "./SelectionManager";
import type { DropdownManager } from "../dropdown/DropdownManager";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";
import { ItemsMapManager } from "../items/ItemsMapManager";
import type { LazyboxSelectionCallback, RenderedItem } from "../type";
import { ListItemMapBuilder } from "../items/ListItemMapBuilder";
import { GroupCollectionBuilder } from "../../tests/builders/GroupCollectionBuilder";
import { TemplatingCallbackStub } from "../../tests/stubs/TemplatingCallbackStub";
import { ClearSearchFieldStub } from "../../tests/stubs/ClearSearchFieldStub";
import { selectOrThrow } from "@tuleap/dom";
import { OptionsBuilder } from "../../tests/builders/OptionsBuilder";

describe("SelectionManager", () => {
    let source_select_box: HTMLSelectElement,
        selection_container: Element,
        placeholder: Element,
        dropdown: Element,
        manager: SelectionManager,
        items_map_manager: ItemsMapManager,
        dropdown_manager: DropdownManager,
        item_1: RenderedItem,
        item_2: RenderedItem,
        selection_callback: MockedFunction<LazyboxSelectionCallback>,
        clear_search_field: ClearSearchFieldStub;

    beforeEach(() => {
        source_select_box = document.createElement("select");

        const { dropdown_element, selection_element, placeholder_element } =
            new BaseComponentRenderer(
                document.implementation.createHTMLDocument(),
                source_select_box,
                OptionsBuilder.withoutNewItem().build()
            ).renderBaseComponent();

        selection_container = selection_element;
        placeholder = placeholder_element;
        dropdown = dropdown_element;

        selection_callback = vi.fn();
        items_map_manager = new ItemsMapManager(ListItemMapBuilder(TemplatingCallbackStub.build()));
        dropdown_manager = { openLazybox: vi.fn() } as unknown as DropdownManager;
        clear_search_field = ClearSearchFieldStub();
        manager = new SelectionManager(
            source_select_box,
            dropdown,
            selection_container,
            placeholder,
            dropdown_manager,
            items_map_manager,
            selection_callback,
            clear_search_field
        );
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

    describe("processSelection", () => {
        it("does nothing if item is already selected", () => {
            item_1.element.setAttribute("aria-selected", "true");
            item_1.is_selected = true;

            manager.processSelection(item_1.element);

            expect(item_1.element.getAttribute("aria-selected")).toBe("true");
            expect(selection_callback).not.toHaveBeenCalled();
        });

        it("reset current value with placeholder if item is disabled", () => {
            item_1.is_disabled = true;
            manager.processSelection(item_1.element);
            const selected_value = selection_container.querySelector(".lazybox-selected-value");
            expect(selection_container.contains(placeholder)).toBe(true);
            expect(selected_value).toBeNull();
            expect(selection_callback).toHaveBeenCalledWith(null);
        });

        it(`replaces the placeholder with the currently selected value
            and toggles the selected attribute on the corresponding dropdown element`, () => {
            manager.processSelection(item_1.element);

            const selected_value = selection_container.querySelector(".lazybox-selected-value");

            expect(selection_container.contains(placeholder)).toBe(false);
            expect(selected_value).not.toBeNull();
            expect(selected_value?.textContent).toContain("Value 1");
            expect(item_1.is_selected).toBe(true);
            expect(item_1.element.getAttribute("aria-selected")).toBe("true");
            expect(selection_callback).toHaveBeenCalledWith(item_1.value);
        });

        it(`replaces the previously selected value with the current one
          and toggles the selected attributes on the corresponding dropdown elements`, () => {
            manager.processSelection(item_1.element);
            manager.processSelection(item_2.element);

            const selected_value = selection_container.querySelector(".lazybox-selected-value");
            expect(selected_value).not.toBeNull();
            expect(selected_value?.textContent).toContain("Value 2");

            expect(item_1.element.getAttribute("aria-selected")).toBe("false");
            expect(item_1.is_selected).toBe(false);

            expect(item_2.element.getAttribute("aria-selected")).toBe("true");
            expect(item_2.is_selected).toBe(true);

            expect(selection_callback).toHaveBeenCalledWith(item_2.value);
        });
    });

    describe("unselects the option and item when the user clicks on the cross in the selection container", () => {
        it(`should replace the currently selected value with the placeholder
            and remove the selected attribute on the source corresponding dropdown element`, () => {
            selection_container.appendChild(placeholder);

            // First select the item
            manager.processSelection(item_1.element);

            expect(item_1.is_selected).toBe(true);
            expect(item_1.element.getAttribute("aria-selected")).toBe("true");
            expect(selection_container.contains(placeholder)).toBe(false);

            // Now unselect the item
            selectOrThrow(
                selection_container,
                "[data-test=clear-current-selection-button]"
            ).dispatchEvent(new MouseEvent("pointerup"));
            expect(item_1.is_selected).toBe(false);
            expect(item_1.element.getAttribute("aria-selected")).toBe("false");
            expect(selection_container.contains(placeholder)).toBe(true);
            expect(dropdown_manager.openLazybox).toHaveBeenCalled();

            expect(selection_callback).toHaveBeenCalledWith(null);
            expect(clear_search_field.getCallsCount()).toBe(1);
        });

        it("should not remove the current selection when the source <select> is disabled", () => {
            source_select_box.disabled = true;

            manager.processSelection(item_1.element);

            selectOrThrow(
                selection_container,
                "[data-test=clear-current-selection-button]"
            ).dispatchEvent(new MouseEvent("pointerup"));

            expect(item_1.is_selected).toBe(true);
            expect(item_1.element.getAttribute("aria-selected")).toBe("true");
            expect(selection_container.contains(placeholder)).toBe(false);
            expect(dropdown_manager.openLazybox).not.toHaveBeenCalled();
        });
    });

    describe("updateSelectionAfterDropdownContentChange", () => {
        it(`when an item is selected but there is no item in the items map anymore,
            then it should display the placeholder`, () => {
            manager.processSelection(item_1.element);
            items_map_manager.refreshItemsMap(GroupCollectionBuilder.withEmptyGroup());
            manager.updateSelectionAfterDropdownContentChange();

            expect(item_1.is_selected).toBe(false);
            expect(item_1.element.getAttribute("aria-selected")).toBe("false");
            expect(selection_container.contains(placeholder)).toBe(true);
            expect(selection_callback).toHaveBeenCalledWith(null);
        });

        it(`when no item has been selected and there is no item in the items map anymore,
            then it should do nothing`, () => {
            items_map_manager.refreshItemsMap(GroupCollectionBuilder.withEmptyGroup());
            manager.updateSelectionAfterDropdownContentChange();
            expect(selection_container.contains(placeholder)).toBe(true);
        });

        it(`when an item has been selected, and is still available in the new items, then it should keep it selected`, () => {
            manager.processSelection(item_1.element);

            const groups = GroupCollectionBuilder.withSingleGroup({
                items: [
                    { id: "value-0", value: { id: 0 }, is_disabled: false },
                    { id: "value-1", value: item_1.value, is_disabled: false },
                ],
            });
            items_map_manager.refreshItemsMap(groups);
            manager.updateSelectionAfterDropdownContentChange();

            const new_item_1 = items_map_manager.findLazyboxItemInItemMap(item_1.id);
            expect(new_item_1.is_selected).toBe(true);
            expect(new_item_1.element.getAttribute("aria-selected")).toBe("true");
            expect(selection_container.contains(placeholder)).toBe(false);
        });
    });
});
