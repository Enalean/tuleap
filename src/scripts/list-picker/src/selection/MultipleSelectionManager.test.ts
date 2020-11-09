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

import { DropdownToggler } from "../dropdown/DropdownToggler";
import { ListPickerItem } from "../type";
import { appendSimpleOptionsToSourceSelectBox } from "../test-helpers/select-box-options-generator";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";
import { MultipleSelectionManager } from "./MultipleSelectionManager";
import { GetText } from "../../../tuleap/gettext/gettext-init";
import { expectChangeEventToHaveBeenFiredOnSourceSelectBox } from "../test-helpers/selection-manager-test-helpers";
import { ItemsMapManager } from "../items/ItemsMapManager";
import { ListItemMapBuilder } from "../items/ListItemMapBuilder";

describe("MultipleSelectionManager", () => {
    let source_select_box: HTMLSelectElement,
        manager: MultipleSelectionManager,
        item_map_manager: ItemsMapManager,
        selection_container: Element,
        search_input: HTMLInputElement,
        gettext_provider: GetText,
        item_1: ListPickerItem,
        item_2: ListPickerItem,
        openListPicker: () => void;

    function isItemSelected(item: ListPickerItem): boolean {
        return (
            item.is_selected &&
            item.element.getAttribute("aria-selected") === "true" &&
            item.target_option.getAttribute("selected") === "selected"
        );
    }

    beforeEach(async () => {
        source_select_box = document.createElement("select");
        source_select_box.setAttribute("multiple", "multiple");
        appendSimpleOptionsToSourceSelectBox(source_select_box);

        const { selection_element, search_field_element } = new BaseComponentRenderer(
            source_select_box,
            {
                placeholder: "Please select some values",
            }
        ).renderBaseComponent();

        gettext_provider = {
            gettext: (english: string) => english,
        } as GetText;
        search_input = search_field_element;
        selection_container = selection_element;
        openListPicker = jest.fn();

        item_map_manager = new ItemsMapManager(new ListItemMapBuilder(source_select_box));
        manager = new MultipleSelectionManager(
            source_select_box,
            selection_element,
            search_field_element,
            "Please select some values",
            { openListPicker } as DropdownToggler,
            item_map_manager,
            gettext_provider
        );

        await item_map_manager.refreshItemsMap();
        item_1 = item_map_manager.findListPickerItemInItemMap("list-picker-item-value_1");
        item_2 = item_map_manager.findListPickerItemInItemMap("list-picker-item-value_2");
        jest.spyOn(source_select_box, "dispatchEvent");
    });

    describe("initSelection", () => {
        it("should select items bound to selected <option> as selected", () => {
            item_1.target_option.setAttribute("selected", "selected");
            item_2.target_option.setAttribute("selected", "selected");

            manager.initSelection();

            [item_1, item_2].forEach((item) => {
                expect(item.is_selected).toBe(true);
                expect(item.element.getAttribute("aria-selected")).toEqual("true");
            });

            expect(search_input.hasAttribute("placeholder")).toBe(false);
            expect(
                selection_container.querySelector(".list-picker-selected-value-remove-button")
            ).not.toBeNull();
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(source_select_box, 1);
        });
    });

    describe("processSelection", () => {
        it("when an item is already selected, then it unselects it", () => {
            item_1.target_option.setAttribute("selected", "selected");

            manager.initSelection();
            manager.processSelection(item_1.element);

            expect(isItemSelected(item_1)).toBe(false);
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(source_select_box, 2);
        });

        it("when an item is unselected and was the only selected item, then the search input placeholder should be reset and the 'remove all values' button removed", () => {
            item_1.target_option.setAttribute("selected", "selected");
            manager.initSelection();
            manager.processSelection(item_1.element);

            expect(search_input.getAttribute("placeholder")).toBe("Please select some values");
            expect(
                selection_container.querySelector(".list-picker-selected-value-remove-button")
            ).toBeNull();
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(source_select_box, 2);
        });

        it("when the first item is selected, the placeholder on the search input is removed, and the 'clear all values' button is added", () => {
            expect(search_input.getAttribute("placeholder")).toBe("Please select some values");
            expect(
                selection_container.querySelector(".list-picker-selected-value-remove-button")
            ).toBeNull();

            manager.processSelection(item_1.element);

            expect(search_input.hasAttribute("placeholder")).toBe(false);
            expect(
                selection_container.querySelector(".list-picker-selected-value-remove-button")
            ).not.toBeNull();
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(source_select_box, 1);
        });

        it("selects items", () => {
            manager.processSelection(item_1.element);
            manager.processSelection(item_2.element);

            expect(isItemSelected(item_1)).toBe(true);
            expect(isItemSelected(item_2)).toBe(true);

            const items_badges = selection_container.querySelectorAll(".list-picker-badge");

            if (items_badges === null) {
                throw new Error("Badges of selected items are not found in selection element");
            }

            expect(items_badges?.length).toEqual(2);
            expect(items_badges[0].textContent).toContain("Value 1");
            expect(items_badges[1].textContent).toContain("Value 2");
            expect(
                items_badges[0].querySelector(".list-picker-value-remove-button")
            ).not.toBeNull();
            expect(
                items_badges[1].querySelector(".list-picker-value-remove-button")
            ).not.toBeNull();
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(source_select_box, 2);
        });
    });

    describe("unselecting items", () => {
        it("When the X button in the badge of a selected item is clicked, then the item should be unselected", () => {
            manager.processSelection(item_1.element);

            const x_button = selection_container.querySelector(
                ".list-picker-badge[title='Value 1'] > .list-picker-value-remove-button"
            );

            if (x_button === null) {
                throw new Error("x button not found");
            }

            x_button.dispatchEvent(new Event("click"));

            expect(isItemSelected(item_1)).toBe(false);
            expect(openListPicker).toHaveBeenCalled();
            expect(
                selection_container.querySelector(
                    ".list-picker-badge[title='Value 1'] > .list-picker-value-remove-button"
                )
            ).toBeNull();
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(source_select_box, 2);
        });

        it("should not unselect the item if the source <select> is disabled", () => {
            source_select_box.setAttribute("disabled", "disabled");
            manager.processSelection(item_1.element);

            const x_button = selection_container.querySelector(
                ".list-picker-badge[title='Value 1'] > .list-picker-value-remove-button"
            );

            if (x_button === null) {
                throw new Error("x button not found");
            }

            x_button.dispatchEvent(new Event("click"));

            expect(isItemSelected(item_1)).toBe(true);
            expect(openListPicker).not.toHaveBeenCalled();
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(source_select_box, 1);
        });

        it("When the 'remove all value' button is clicked, then all values should be unselected", () => {
            manager.processSelection(item_1.element);
            manager.processSelection(item_2.element);

            const clear_values_button = selection_container.querySelector(
                ".list-picker-selected-value-remove-button"
            );
            if (clear_values_button === null) {
                throw new Error("'remove all values' button not found in selection container");
            }

            clear_values_button.dispatchEvent(new Event("click"));

            expect(isItemSelected(item_1)).toBe(false);
            expect(isItemSelected(item_2)).toBe(false);
            expect(selection_container.querySelectorAll(".list-picker-badge").length).toEqual(0);
            expect(
                selection_container.querySelector(".list-picker-selected-value-remove-button")
            ).toBeNull();
            expect(search_input.getAttribute("placeholder")).toEqual("Please select some values");
            expect(openListPicker).toHaveBeenCalled();
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(source_select_box, 3);
        });
    });

    describe("handleBackSpaceKey", () => {
        it("should remove the last selected item and set the value of the search input with its template", () => {
            const backspace_down_event = new KeyboardEvent("keydown");
            manager.processSelection(item_1.element);
            manager.handleBackspaceKey(backspace_down_event);

            expect(isItemSelected(item_1)).toBe(false);
            expect(search_input.value).toEqual(item_1.label);
            expect(backspace_down_event.cancelBubble).toBe(true);
            expect(
                selection_container.querySelector(".list-picker-selected-value-remove-button")
            ).toBeNull();
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(source_select_box, 2);
        });

        it("should let the user delete the content of the search input", () => {
            const backspace_down_event = new KeyboardEvent("keydown");
            search_input.value = item_1.label;

            manager.handleBackspaceKey(backspace_down_event);

            expect(backspace_down_event.cancelBubble).toBe(false);
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(source_select_box, 0);
        });

        it("when no item is selected and the user deletes the last letter of the input content, then it should put the placeholder back", () => {
            const backspace_down_event = new KeyboardEvent("keydown");
            search_input.value = "V";

            manager.handleBackspaceKey(backspace_down_event);

            expect(backspace_down_event.cancelBubble).toBe(false);
            expect(search_input.getAttribute("placeholder")).toEqual("Please select some values");
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(source_select_box, 0);
        });
    });

    describe("resetAfterDependenciesUpdate", () => {
        it("should remove the values from the previous selection that do not appear in the new options", async () => {
            manager.processSelection(item_1.element);
            manager.processSelection(item_2.element);

            source_select_box.options[2].value = "a_brand_new_value";
            await item_map_manager.refreshItemsMap();
            manager.resetAfterDependenciesUpdate();

            const new_item_with_item_1_value = item_map_manager.getItemWithValue(item_1.value);
            const new_item_with_item_2_value = item_map_manager.getItemWithValue(item_2.value);
            if (new_item_with_item_1_value === null) {
                throw new Error(
                    "an item matching item_1's value should have been found in the items map"
                );
            }
            expect(isItemSelected(new_item_with_item_1_value)).toBe(true);
            expect(new_item_with_item_2_value).toBeNull();

            expectChangeEventToHaveBeenFiredOnSourceSelectBox(source_select_box, 3);
        });

        it("should put back the placeholder and remove the [remove all values] button when no item are selected", async () => {
            manager.processSelection(item_1.element);
            manager.processSelection(item_2.element);

            source_select_box.options[1].value = "a_brand_new_value";
            source_select_box.options[2].value = "another_brand_new_value";
            await item_map_manager.refreshItemsMap();
            manager.resetAfterDependenciesUpdate();

            expect(
                selection_container.querySelector(".list-picker-selected-value-remove-button")
            ).toBeNull();
            expect(search_input.getAttribute("placeholder")).toEqual("Please select some values");
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(source_select_box, 2);
        });
    });
});
