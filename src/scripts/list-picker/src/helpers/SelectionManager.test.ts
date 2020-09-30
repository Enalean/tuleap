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

import { SelectionManager } from "./SelectionManager";
import { DropdownToggler } from "./DropdownToggler";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";
import { generateItemMapBasedOnSourceSelectOptions } from "./static-list-helper";
import { appendSimpleOptionsToSourceSelectBox } from "../test-helpers/select-box-options-generator";
import { ListPickerItem } from "../type";

describe("selection-manager", () => {
    let source_select_box: HTMLSelectElement,
        selection_container: Element,
        placeholder: Element,
        manager: SelectionManager,
        toggler: DropdownToggler,
        item_map: Map<string, ListPickerItem>;

    function getItem(item_id: string): ListPickerItem {
        const item = item_map.get(item_id);

        if (!item) {
            throw new Error(`item with id ${item_id} not found`);
        }

        return item;
    }

    beforeEach(() => {
        source_select_box = document.createElement("select");
        appendSimpleOptionsToSourceSelectBox(source_select_box);

        const {
            list_picker_element,
            dropdown_element,
            selection_element,
            placeholder_element,
            dropdown_list_element,
            search_field_element,
        } = new BaseComponentRenderer(source_select_box, {
            placeholder: "Please select a value",
        }).renderBaseComponent();

        selection_container = selection_element;
        placeholder = placeholder_element;

        toggler = new DropdownToggler(
            list_picker_element,
            dropdown_element,
            dropdown_list_element,
            search_field_element
        );
        item_map = generateItemMapBasedOnSourceSelectOptions(source_select_box);
        manager = new SelectionManager(
            source_select_box,
            dropdown_element,
            selection_container,
            placeholder,
            toggler,
            item_map
        );
    });

    describe("initSelection", () => {
        it("When a value is already selected in the source <select>, then it selects it in the list-picker", () => {
            const item = getItem("item-1");
            const option = item.target_option;

            option.setAttribute("selected", "selected");
            manager.initSelection(placeholder);

            expect(selection_container.contains(placeholder)).toBe(false);
            expect(selection_container.querySelector(".list-picker-selected-value")).not.toBeNull();
            expect(item.element.getAttribute("aria-selected")).toEqual("true");
            expect(option.hasAttribute("selected")).toBe(true);
        });

        it("When no value is selected yet in the source <select>, then it does nothing", () => {
            const item = getItem("item-1");
            const option = item.target_option;

            selection_container.appendChild(placeholder);

            manager.initSelection(placeholder);

            expect(selection_container.contains(placeholder)).toBe(true);
            expect(selection_container.querySelector(".list-picker-selected-value")).toBeNull();
            expect(item.element.getAttribute("aria-selected")).toEqual("false");
            expect(option.hasAttribute("selected")).toBe(false);
        });
    });

    describe("processSingleSelection", () => {
        it("does nothing if item is already selected", () => {
            const item = getItem("item-1");
            const option = item.target_option;

            option.setAttribute("selected", "selected");
            item.element.setAttribute("aria-selected", "true");
            item.is_selected = true;

            manager.processSingleSelection(item.element);

            expect(item.element.getAttribute("aria-selected")).toEqual("true");
        });

        it("replaces the placeholder with the currently selected value and toggles the selected attributes on the <select> options", () => {
            const item = getItem("item-1");
            const option = item.target_option;

            manager.processSingleSelection(item.element);

            const selected_value = selection_container.querySelector(".list-picker-selected-value");

            expect(selection_container.contains(placeholder)).toBe(false);
            expect(selected_value).not.toBeNull();
            expect(selected_value?.textContent).toContain("Value 1");
            expect(item.is_selected).toBe(true);
            expect(item.element.getAttribute("aria-selected")).toEqual("true");
            expect(option.hasAttribute("selected")).toBe(true);
        });

        it("replaces the previously selected value with the current one and toggles the selected attributes on the <select> options", () => {
            const old_item = getItem("item-1");
            const old_option = old_item.target_option;

            const new_item = getItem("item-2");
            const new_option = new_item.target_option;

            manager.processSingleSelection(old_item.element);
            manager.processSingleSelection(new_item.element);

            const selected_value = selection_container.querySelector(".list-picker-selected-value");
            expect(selected_value).not.toBeNull();
            expect(selected_value?.textContent).toContain("Value 2");

            expect(old_item.element.getAttribute("aria-selected")).toEqual("false");
            expect(old_item.is_selected).toBe(false);
            expect(old_option.hasAttribute("selected")).toBe(false);

            expect(new_item.element.getAttribute("aria-selected")).toEqual("true");
            expect(new_item.is_selected).toBe(true);
            expect(new_option.hasAttribute("selected")).toBe(true);
        });
    });

    describe("unselects the option and item when the user clicks on the cross in the selection container", () => {
        it("should replace the currently selected value with the placeholder and remove the selected attribute on the source <option>", () => {
            const openListPicker = jest.spyOn(toggler, "openListPicker");
            const item = getItem("item-1");
            const option = item.target_option;

            selection_container.appendChild(placeholder);

            // First select the item
            manager.processSingleSelection(item.element);

            expect(item.is_selected).toBe(true);
            expect(item.element.getAttribute("aria-selected")).toEqual("true");
            expect(selection_container.contains(placeholder)).toBe(false);

            expect(option.hasAttribute("selected")).toBe(true);
            const remove_item_button = selection_container.querySelector(
                ".list-picker-selected-value-remove-button"
            );
            if (!(remove_item_button instanceof Element)) {
                throw new Error("No remove button found, something has gone wrong");
            }

            // Now unselect the item
            remove_item_button.dispatchEvent(new MouseEvent("click"));
            expect(item.is_selected).toBe(false);
            expect(item.element.getAttribute("aria-selected")).toEqual("false");
            expect(option.hasAttribute("selected")).toBe(false);
            expect(selection_container.contains(placeholder)).toBe(true);
            expect(openListPicker).toHaveBeenCalled();
        });
    });
});
