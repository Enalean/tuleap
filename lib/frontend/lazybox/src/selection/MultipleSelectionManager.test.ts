/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach, vi } from "vitest";
import type { MockedFunction } from "vitest";
import { ItemsMapManager } from "../items/ItemsMapManager";
import type { DropdownManager } from "../dropdown/DropdownManager";
import type { LazyboxSelectionCallback, RenderedItem } from "../type";
import { ClearSearchFieldStub } from "../../tests/stubs/ClearSearchFieldStub";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";
import { ListItemMapBuilder } from "../items/ListItemMapBuilder";
import { TemplatingCallbackStub } from "../../tests/stubs/TemplatingCallbackStub";
import { GroupCollectionBuilder } from "../../tests/builders/GroupCollectionBuilder";
import { MultipleSelectionManager } from "./MultipleSelectionManager";
import { selectOrThrow } from "@tuleap/dom";

describe("MultipleSelectionManager", () => {
    let source_select_box: HTMLSelectElement,
        selection_container: Element,
        search_field: HTMLInputElement,
        manager: MultipleSelectionManager,
        items_map_manager: ItemsMapManager,
        dropdown_manager: DropdownManager,
        item_1: RenderedItem,
        item_2: RenderedItem,
        selection_callback: MockedFunction<LazyboxSelectionCallback>,
        clear_search_field: ClearSearchFieldStub;

    beforeEach(() => {
        source_select_box = document.createElement("select");

        const { selection_element, search_field_element } = new BaseComponentRenderer(
            document.implementation.createHTMLDocument(),
            source_select_box,
            "",
            "",
            true
        ).renderBaseComponent();

        selection_container = selection_element;
        search_field = search_field_element;

        selection_callback = vi.fn();
        items_map_manager = new ItemsMapManager(ListItemMapBuilder(TemplatingCallbackStub.build()));
        dropdown_manager = { openLazybox: vi.fn() } as unknown as DropdownManager;
        clear_search_field = ClearSearchFieldStub();
        manager = new MultipleSelectionManager(
            source_select_box,
            selection_container,
            search_field,
            "Please select some values",
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
            expect(clear_search_field.getCallsCount()).toBe(1);
        });

        it("does nothing if item is disabled", () => {
            item_1.is_disabled = true;

            manager.processSelection(item_1.element);

            expect(item_1.element.getAttribute("aria-selected")).toBe("false");
            expect(selection_callback).not.toHaveBeenCalled();
        });

        it(`when a first value has been selected, it:
            - removes the placeholder text from the search field
            - displays the [Clear all values] button`, () => {
            expect(search_field.hasAttribute("placeholder")).toBe(true);
            expect(
                selection_container.querySelector("[data-test=clear-current-selection-button]")
            ).toBeNull();

            manager.processSelection(item_1.element);

            expect(search_field.hasAttribute("placeholder")).toBe(false);
            expect(
                selection_container.querySelector("[data-test=clear-current-selection-button]")
            ).not.toBeNull();
        });

        it(`Each time a value is selected, it should:
            - display it
            - call the selection callback`, () => {
            manager.processSelection(item_1.element);
            manager.processSelection(item_2.element);

            const displayed_values = selection_container.querySelectorAll(
                "[data-test=lazybox-selected-value]"
            );
            expect(displayed_values).toHaveLength(2);

            expect(displayed_values[0]?.textContent).toContain("Value 1");
            expect(displayed_values[1]?.textContent).toContain("Value 2");

            expect(selection_callback.mock.calls[0][0]).toStrictEqual([item_1.value]);
            expect(selection_callback.mock.calls[1][0]).toStrictEqual([item_1.value, item_2.value]);
        });
    });

    describe("unselect values", () => {
        beforeEach(() => {
            manager.processSelection(item_1.element);
            manager.processSelection(item_2.element);
        });
        it(`Given that the user has selected some values
            When he clicks consecutively on the crosses on each values
            Then it should unselect consecutively these values
            And display back the placeholder + remove the [Clear all values] button`, () => {
            const displayed_values = selection_container.querySelectorAll(
                "[data-test=lazybox-selected-value]"
            );
            const item_1_remove_button = selectOrThrow(
                displayed_values[0],
                "[data-test=remove-value-button]"
            );
            const item_2_remove_button = selectOrThrow(
                displayed_values[1],
                "[data-test=remove-value-button]"
            );

            item_1_remove_button.dispatchEvent(new Event("pointerup"));
            item_2_remove_button.dispatchEvent(new Event("pointerup"));

            expect(selection_callback.mock.calls[2][0]).toStrictEqual([item_2.value]);
            expect(selection_callback.mock.calls[3][0]).toStrictEqual([]);

            expect(
                selection_container.querySelectorAll("[data-test=lazybox-selected-value]")
            ).toHaveLength(0);
            expect(
                selection_container.querySelector("[data-test=clear-current-selection-button]")
            ).toBeNull();
            expect(search_field.hasAttribute("placeholder")).toBe(true);
        });

        it(`Given that the user has selected some values
            When he clicks on the [Clear all values] button
            Then it should clear the selection
            And display back the placeholder + remove the [Clear all values] button`, () => {
            selectOrThrow(
                selection_container,
                "[data-test=clear-current-selection-button]"
            ).dispatchEvent(new Event("pointerup"));

            expect(selection_callback.mock.calls[2][0]).toStrictEqual([]);

            expect(
                selection_container.querySelectorAll("[data-test=lazybox-selected-value]")
            ).toHaveLength(0);
            expect(
                selection_container.querySelector("[data-test=clear-current-selection-button]")
            ).toBeNull();
            expect(search_field.hasAttribute("placeholder")).toBe(true);
        });
    });

    describe("updateSelectionAfterDropdownContentChange", () => {
        it(`Given that the user has selected a value
            When the new dropdown content contains this value
            Then it should give it the selected state in the dropdown`, () => {
            manager.processSelection(item_2.element);

            items_map_manager.refreshItemsMap(
                GroupCollectionBuilder.withSingleGroup({
                    items: [item_2, { id: "value-3", value: { id: 3 }, is_disabled: false }],
                })
            );

            manager.updateSelectionAfterDropdownContentChange();

            const item_2_in_dropdown = items_map_manager.getItemWithValue(item_2.value);
            if (item_2_in_dropdown === null) {
                throw new Error("Expected to find item_2 in the dropdown");
            }

            expect(item_2_in_dropdown.is_selected).toBe(true);
            expect(item_2_in_dropdown.element.getAttribute("aria-selected")).toBe("true");
        });
    });

    describe("setSelection", () => {
        it("Given an array of rendered items, then it should select those items", () => {
            manager.setSelection([item_1, item_2]);

            expect(selection_callback.mock.calls[0][0]).toStrictEqual([item_1.value]);
            expect(selection_callback.mock.calls[1][0]).toStrictEqual([item_1.value, item_2.value]);
        });
    });

    describe("Pressing the backspace key in the search field", () => {
        const pressBackspaceKey = (): void => {
            search_field.dispatchEvent(new KeyboardEvent("keydown", { key: "Backspace" }));
        };

        it("should do nothing when there is no selected value", () => {
            search_field.value = "";
            pressBackspaceKey();

            expect(selection_callback).not.toHaveBeenCalled();
        });

        it("should do nothing when there are selected values but something has been written inside the search input", () => {
            manager.processSelection(item_1.element);

            search_field.value = "Some query being typed";
            pressBackspaceKey();

            expect(selection_callback).not.toHaveBeenCalledWith([]);
        });

        it("should remove the last selected value from the selection when the search input is empty", () => {
            manager.processSelection(item_1.element);
            manager.processSelection(item_2.element);
            search_field.value = "";

            pressBackspaceKey();
            expect(selection_callback.mock.calls[2][0]).toStrictEqual([item_1.value]);

            pressBackspaceKey();
            expect(selection_callback.mock.calls[3][0]).toStrictEqual([]);

            expect(
                selection_container.querySelectorAll("[data-test=lazybox-selected-value]")
            ).toHaveLength(0);
        });
    });
});
