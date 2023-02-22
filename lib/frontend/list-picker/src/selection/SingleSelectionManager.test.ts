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

import { describe, beforeEach, expect, it, vi } from "vitest";
import { SingleSelectionManager } from "./SingleSelectionManager";
import type { DropdownManager } from "../dropdown/DropdownManager";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";
import { appendSimpleOptionsToSourceSelectBox } from "../test-helpers/select-box-options-generator";
import { expectChangeEventToHaveBeenFiredOnSourceSelectBox } from "../test-helpers/selection-manager-test-helpers";
import { ItemsMapManager } from "../items/ItemsMapManager";
import type { ListPickerItem } from "../type";
import { ListItemMapBuilder } from "../items/ListItemMapBuilder";

describe("SingleSelectionManager", () => {
    let source_select_box: HTMLSelectElement,
        selection_container: Element,
        placeholder: Element,
        dropdown: Element,
        manager: SingleSelectionManager,
        items_map_manager: ItemsMapManager,
        dropdown_manager: DropdownManager,
        item_1: ListPickerItem,
        item_2: ListPickerItem;

    beforeEach(async () => {
        source_select_box = document.createElement("select");
        appendSimpleOptionsToSourceSelectBox(source_select_box);

        const { dropdown_element, selection_element, placeholder_element } =
            new BaseComponentRenderer(
                document.implementation.createHTMLDocument(),
                source_select_box,
                {
                    placeholder: "Please select a value",
                }
            ).renderBaseComponent();

        selection_container = selection_element;
        placeholder = placeholder_element;
        dropdown = dropdown_element;

        items_map_manager = new ItemsMapManager(new ListItemMapBuilder(source_select_box));
        dropdown_manager = { openListPicker: vi.fn() } as unknown as DropdownManager;
        manager = new SingleSelectionManager(
            source_select_box,
            dropdown,
            selection_container,
            placeholder,
            dropdown_manager,
            items_map_manager
        );
        vi.spyOn(source_select_box, "dispatchEvent");
        await items_map_manager.refreshItemsMap();
        item_1 = items_map_manager.findListPickerItemInItemMap("list-picker-item-value_1");
        item_2 = items_map_manager.findListPickerItemInItemMap("list-picker-item-value_2");
    });

    describe("initSelection", () => {
        it("When a value is already selected in the source <select>, then it selects it in the list-picker", () => {
            item_1.target_option.setAttribute("selected", "selected");
            manager.initSelection();

            expect(selection_container.contains(placeholder)).toBe(false);
            expect(selection_container.querySelector(".list-picker-selected-value")).not.toBeNull();
            expect(item_1.element.getAttribute("aria-selected")).toBe("true");
            expect(item_1.target_option.hasAttribute("selected")).toBe(true);
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(source_select_box, 1);
        });

        it("When no value is selected yet in the source <select>, then it does nothing", () => {
            selection_container.appendChild(placeholder);
            source_select_box.value = "";
            manager.initSelection();

            expect(selection_container.contains(placeholder)).toBe(true);
            expect(selection_container.querySelector(".list-picker-selected-value")).toBeNull();
            expect(item_1.element.getAttribute("aria-selected")).toBe("false");
            expect(item_1.target_option.hasAttribute("selected")).toBe(false);
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(source_select_box, 0);
        });
    });

    describe("processSelection", () => {
        it("does nothing if item is already selected", () => {
            item_1.target_option.setAttribute("selected", "selected");
            item_1.element.setAttribute("aria-selected", "true");
            item_1.is_selected = true;

            manager.processSelection(item_1.element);

            expect(item_1.element.getAttribute("aria-selected")).toBe("true");
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(source_select_box, 0);
        });

        it("reset current value with placeholder if item is disabled", () => {
            item_1.is_disabled = true;
            manager.processSelection(item_1.element);
            const selected_value = selection_container.querySelector(".list-picker-selected-value");
            expect(selection_container.contains(placeholder)).toBe(true);
            expect(selected_value).toBeNull();
        });

        it("replaces the placeholder with the currently selected value and toggles the selected attributes on the <select> options", () => {
            manager.processSelection(item_1.element);

            const selected_value = selection_container.querySelector(".list-picker-selected-value");

            expect(selection_container.contains(placeholder)).toBe(false);
            expect(selected_value).not.toBeNull();
            expect(selected_value?.textContent).toContain("Value 1");
            expect(item_1.is_selected).toBe(true);
            expect(item_1.element.getAttribute("aria-selected")).toBe("true");
            expect(item_1.target_option.hasAttribute("selected")).toBe(true);
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(source_select_box, 1);
        });

        it("replaces the previously selected value with the current one and toggles the selected attributes on the <select> options", () => {
            manager.processSelection(item_1.element);
            manager.processSelection(item_2.element);

            const selected_value = selection_container.querySelector(".list-picker-selected-value");
            expect(selected_value).not.toBeNull();
            expect(selected_value?.textContent).toContain("Value 2");

            expect(item_1.element.getAttribute("aria-selected")).toBe("false");
            expect(item_1.is_selected).toBe(false);
            expect(item_1.target_option.hasAttribute("selected")).toBe(false);

            expect(item_2.element.getAttribute("aria-selected")).toBe("true");
            expect(item_2.is_selected).toBe(true);
            expect(item_2.target_option.hasAttribute("selected")).toBe(true);

            expectChangeEventToHaveBeenFiredOnSourceSelectBox(source_select_box, 2);
        });
    });

    describe("unselects the option and item when the user clicks on the cross in the selection container", () => {
        it("should replace the currently selected value with the placeholder and remove the selected attribute on the source <option>", () => {
            selection_container.appendChild(placeholder);

            // First select the item
            manager.processSelection(item_1.element);

            expect(item_1.is_selected).toBe(true);
            expect(item_1.element.getAttribute("aria-selected")).toBe("true");
            expect(selection_container.contains(placeholder)).toBe(false);

            expect(item_1.target_option.hasAttribute("selected")).toBe(true);
            const remove_item_button = selection_container.querySelector(
                ".list-picker-selected-value-remove-button"
            );
            if (!(remove_item_button instanceof Element)) {
                throw new Error("No remove button found, something has gone wrong");
            }

            // Now unselect the item
            remove_item_button.dispatchEvent(new MouseEvent("pointerdown"));
            expect(item_1.is_selected).toBe(false);
            expect(item_1.element.getAttribute("aria-selected")).toBe("false");
            expect(item_1.target_option.hasAttribute("selected")).toBe(false);
            expect(selection_container.contains(placeholder)).toBe(true);
            expect(dropdown_manager.openListPicker).toHaveBeenCalled();

            expectChangeEventToHaveBeenFiredOnSourceSelectBox(source_select_box, 3);
        });

        it("should not remove the current selection when the source <select> is disabled", () => {
            source_select_box.disabled = true;

            manager.processSelection(item_1.element);
            const remove_item_button = selection_container.querySelector(
                ".list-picker-selected-value-remove-button"
            );
            if (!(remove_item_button instanceof Element)) {
                throw new Error("No remove button found, something has gone wrong");
            }
            remove_item_button.dispatchEvent(new MouseEvent("pointerdown"));
            expect(item_1.is_selected).toBe(true);
            expect(item_1.element.getAttribute("aria-selected")).toBe("true");
            expect(item_1.target_option.hasAttribute("selected")).toBe(true);
            expect(selection_container.contains(placeholder)).toBe(false);
            expect(dropdown_manager.openListPicker).not.toHaveBeenCalled();
        });
    });

    describe("resetAfterDependenciesUpdate", () => {
        it("when an item is selected but there is no options in the source <select> anymore, then it should display the placeholder", async () => {
            manager.processSelection(item_1.element);
            source_select_box.innerHTML = "";
            await items_map_manager.refreshItemsMap();
            manager.resetAfterDependenciesUpdate();

            expect(item_1.is_selected).toBe(false);
            expect(item_1.element.getAttribute("aria-selected")).toBe("false");
            expect(item_1.target_option.getAttribute("selected")).toBeNull();
            expect(selection_container.contains(placeholder)).toBe(true);
        });

        it("when no item has been selected and there is no options in the source <select> anymore, then it should do nothing", async () => {
            source_select_box.innerHTML = "";
            await items_map_manager.refreshItemsMap();
            manager.resetAfterDependenciesUpdate();
            expect(selection_container.contains(placeholder)).toBe(true);
        });

        it("when no item has been selected, then it should select the first available option", async () => {
            source_select_box.innerHTML = "";
            const new_option_0 = document.createElement("option");
            new_option_0.value = "new option 0";
            const new_option_1 = document.createElement("option");
            new_option_1.value = "new option 1";
            source_select_box.appendChild(new_option_0);
            source_select_box.appendChild(new_option_1);

            await items_map_manager.refreshItemsMap();
            manager.resetAfterDependenciesUpdate();

            const first_item = items_map_manager.findListPickerItemInItemMap(
                "list-picker-item-new-option-0"
            );
            expect(first_item.is_selected).toBe(true);
            expect(first_item.element.getAttribute("aria-selected")).toBe("true");
            expect(first_item.target_option.getAttribute("selected")).toBe("selected");
            expect(selection_container.contains(placeholder)).toBe(false);
        });

        it("when no item has been selected and keep_none_value option is true, then it should display the placeholder", async () => {
            manager = new SingleSelectionManager(
                source_select_box,
                dropdown,
                selection_container,
                placeholder,
                dropdown_manager,
                items_map_manager,
                true
            );

            source_select_box.innerHTML = "";
            const new_option_0 = document.createElement("option");
            new_option_0.value = "new option 0";
            source_select_box.appendChild(new_option_0);

            await items_map_manager.refreshItemsMap();
            manager.resetAfterDependenciesUpdate();

            expect(selection_container.contains(placeholder)).toBe(true);
        });

        it("when an item has been selected, and is still available in the new options, then it should keep it selected", async () => {
            manager.processSelection(item_1.element);

            source_select_box.innerHTML = "";
            const new_option_0 = document.createElement("option");
            new_option_0.value = "new option 0";
            const new_option_1 = document.createElement("option");
            new_option_1.value = item_1.value;
            source_select_box.appendChild(new_option_0);
            source_select_box.appendChild(new_option_1);

            await items_map_manager.refreshItemsMap();
            manager.resetAfterDependenciesUpdate();

            const new_item_1 = items_map_manager.findListPickerItemInItemMap(item_1.id);
            expect(new_item_1.is_selected).toBe(true);
            expect(new_item_1.element.getAttribute("aria-selected")).toBe("true");
            expect(new_item_1.target_option.getAttribute("selected")).toBe("selected");
            expect(selection_container.contains(placeholder)).toBe(false);
        });

        it("when an item has been selected, but is not available in the new options, then the first available item should be selected", async () => {
            manager.processSelection(item_1.element);

            source_select_box.innerHTML = "";
            const new_option_0 = document.createElement("option");
            new_option_0.value = "new option 0";
            const new_option_1 = document.createElement("option");
            new_option_1.value = "new option 1";
            source_select_box.appendChild(new_option_0);
            source_select_box.appendChild(new_option_1);

            await items_map_manager.refreshItemsMap();
            manager.resetAfterDependenciesUpdate();

            const item_0 = items_map_manager.findListPickerItemInItemMap(
                "list-picker-item-new-option-0"
            );
            expect(item_0.is_selected).toBe(true);
            expect(item_0.element.getAttribute("aria-selected")).toBe("true");
            expect(item_0.target_option.getAttribute("selected")).toBe("selected");
            expect(selection_container.contains(placeholder)).toBe(false);
        });
    });
});
