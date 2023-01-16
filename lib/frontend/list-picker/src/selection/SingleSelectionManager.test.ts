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
import { selectOrThrow } from "@tuleap/dom";
import { SingleSelectionManager } from "./SingleSelectionManager";
import type { DropdownManager } from "../dropdown/DropdownManager";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";
import { appendSimpleOptionsToSourceSelectBox } from "../test-helpers/select-box-options-generator";
import {
    expectChangeEventToHaveBeenFiredOnSourceSelectBox,
    expectItemNotToBeSelected,
    expectItemToBeSelected,
} from "../test-helpers/selection-manager-test-helpers";
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
        item_2: ListPickerItem,
        doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        source_select_box = doc.createElement("select");
        appendSimpleOptionsToSourceSelectBox(source_select_box);

        const { dropdown_element, selection_element, placeholder_element } =
            new BaseComponentRenderer(doc, source_select_box, {
                placeholder: "Please select a value",
            }).renderBaseComponent();

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
        items_map_manager.refreshItemsMap();
        item_1 = items_map_manager.findListPickerItemInItemMap("list-picker-item-value_1");
        item_2 = items_map_manager.findListPickerItemInItemMap("list-picker-item-value_2");
    });

    describe("initSelection", () => {
        it("When a value is already selected in the source <select>, then it selects it in the list-picker", () => {
            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            item_1.target_option.setAttribute("selected", "selected");
            manager.initSelection();

            expect(selection_container.contains(placeholder)).toBe(false);
            expect(selection_container.querySelector(".list-picker-selected-value")).not.toBeNull();
            expectItemToBeSelected(item_1);
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 0);
        });

        it("When no value is selected yet in the source <select>, then it does nothing", () => {
            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            selection_container.appendChild(placeholder);
            source_select_box.value = "";
            manager.initSelection();

            expect(selection_container.contains(placeholder)).toBe(true);
            expect(selection_container.querySelector(".list-picker-selected-value")).toBeNull();
            expectItemNotToBeSelected(item_1);
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 0);
        });
    });

    describe("processSelection", () => {
        it("does nothing if item is already selected", () => {
            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            item_1.target_option.setAttribute("selected", "selected");
            item_1.element.setAttribute("aria-selected", "true");
            item_1.is_selected = true;

            manager.processSelection(item_1.element);

            expect(item_1.element.getAttribute("aria-selected")).toBe("true");
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 0);
        });

        it("reset current value with placeholder if item is disabled", () => {
            item_1.is_disabled = true;
            manager.processSelection(item_1.element);
            const selected_value = selection_container.querySelector(".list-picker-selected-value");
            expect(selection_container.contains(placeholder)).toBe(true);
            expect(selected_value).toBeNull();
        });

        it("replaces the placeholder with the currently selected value and toggles the selected attributes on the <select> options", () => {
            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            manager.processSelection(item_1.element);

            const selected_value = selection_container.querySelector(".list-picker-selected-value");

            expect(selection_container.contains(placeholder)).toBe(false);
            expect(selected_value).not.toBeNull();
            expect(selected_value?.textContent).toContain("Value 1");
            expectItemToBeSelected(item_1);
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 1);
        });

        it("replaces the previously selected value with the current one and toggles the selected attributes on the <select> options", () => {
            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            manager.processSelection(item_1.element);
            manager.processSelection(item_2.element);

            const selected_value = selection_container.querySelector(".list-picker-selected-value");
            expect(selected_value).not.toBeNull();
            expect(selected_value?.textContent).toContain("Value 2");

            expectItemNotToBeSelected(item_1);
            expectItemToBeSelected(item_2);
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 2);
        });
    });

    describe("unselects the option and item when the user clicks on the cross in the selection container", () => {
        it(`should replace the currently selected value with the placeholder
            and remove the selected attribute on the source <option>`, () => {
            selection_container.appendChild(placeholder);

            // First select the item
            manager.processSelection(item_1.element);

            expectItemToBeSelected(item_1);
            expect(selection_container.contains(placeholder)).toBe(false);

            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            const remove_item_button = selectOrThrow(
                selection_container,
                ".list-picker-selected-value-remove-button"
            );

            // Now unselect the item
            remove_item_button.dispatchEvent(new MouseEvent("pointerdown"));

            expectItemNotToBeSelected(item_1);
            expect(selection_container.contains(placeholder)).toBe(true);
            expect(dropdown_manager.openListPicker).toHaveBeenCalled();
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 1);
        });

        it("should not remove the current selection when the source <select> is disabled", () => {
            source_select_box.disabled = true;

            manager.processSelection(item_1.element);
            const remove_item_button = selectOrThrow(
                selection_container,
                ".list-picker-selected-value-remove-button"
            );

            remove_item_button.dispatchEvent(new MouseEvent("pointerdown"));

            expectItemToBeSelected(item_1);
            expect(selection_container.contains(placeholder)).toBe(false);
            expect(dropdown_manager.openListPicker).not.toHaveBeenCalled();
        });
    });

    describe("resetAfterDependenciesUpdate", () => {
        it(`when an item is selected but there is no option in the source <select> anymore,
            then it should display the placeholder`, () => {
            manager.processSelection(item_1.element);
            source_select_box.innerHTML = "";
            items_map_manager.refreshItemsMap();
            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            manager.resetAfterDependenciesUpdate();

            expectItemNotToBeSelected(item_1);
            expect(selection_container.contains(placeholder)).toBe(true);
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 0);
        });

        it(`when no item has been selected and there is no option in the source <select> anymore,
            then it should do nothing`, () => {
            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            source_select_box.innerHTML = "";
            items_map_manager.refreshItemsMap();
            manager.resetAfterDependenciesUpdate();

            expect(selection_container.contains(placeholder)).toBe(true);
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 0);
        });

        it(`when no item has been selected, and there are new options in the source <select>,
            then it should display the placeholder`, () => {
            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            const new_option_0 = doc.createElement("option");
            new_option_0.value = "new option 0";
            const new_option_1 = doc.createElement("option");
            new_option_1.value = "new option 1";
            source_select_box.replaceChildren(new_option_0, new_option_1);

            items_map_manager.refreshItemsMap();
            manager.resetAfterDependenciesUpdate();

            expect(selection_container.contains(placeholder)).toBe(true);
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 0);
        });

        it(`when an item has been selected, and is still available in the new options,
            then it should keep it selected`, () => {
            manager.processSelection(item_1.element);
            const new_option_0 = doc.createElement("option");
            new_option_0.value = "new option 0";
            const new_option_1 = doc.createElement("option");
            new_option_1.value = item_1.value;
            source_select_box.replaceChildren(new_option_0, new_option_1);
            items_map_manager.refreshItemsMap();

            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            manager.resetAfterDependenciesUpdate();

            const new_item_1 = items_map_manager.findListPickerItemInItemMap(item_1.id);
            expectItemToBeSelected(new_item_1);
            expect(selection_container.contains(placeholder)).toBe(false);
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 0);
        });

        it(`when an item has been selected, but is not available in the new options,
            then it should display the placeholder`, () => {
            manager.processSelection(item_1.element);

            const new_option_0 = doc.createElement("option");
            new_option_0.value = "new option 0";
            const new_option_1 = doc.createElement("option");
            new_option_1.value = "new option 1";
            source_select_box.replaceChildren(new_option_0, new_option_1);
            items_map_manager.refreshItemsMap();

            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            manager.resetAfterDependenciesUpdate();

            expectItemNotToBeSelected(item_1);
            expect(selection_container.contains(placeholder)).toBe(true);
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 0);
        });
    });
});
