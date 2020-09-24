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

describe("selection-manager", () => {
    let source_select_box: HTMLSelectElement,
        component_root: Element,
        component_dropdown: Element,
        selection_container: Element,
        placeholder_element: Element,
        manager: SelectionManager,
        toggler: DropdownToggler;

    function createItem(id: string, label: string, is_selected: boolean): Element {
        const item = document.createElement("li");
        item.id = id;
        item.setAttribute("aria-selected", is_selected.toString());
        item.appendChild(document.createTextNode(label));
        return item;
    }

    function createSelection(label: string, item_id: string): Element {
        const selected_value = document.createElement("span");
        selected_value.classList.add("list-picker-selected-value");
        selected_value.setAttribute("data-item-id", item_id);
        selected_value.appendChild(document.createTextNode(label));

        return selected_value;
    }

    function createOption(label: string, is_selected: boolean, source_item_id: string): Element {
        const option = document.createElement("option");
        option.appendChild(document.createTextNode(label));
        option.setAttribute("data-item-id", source_item_id);
        if (is_selected) {
            option.setAttribute("selected", "selected");
        }

        return option;
    }

    beforeEach(() => {
        source_select_box = document.createElement("select");
        component_root = document.createElement("span");
        component_dropdown = document.createElement("span");
        selection_container = document.createElement("span");
        placeholder_element = document.createElement("span");
        placeholder_element.classList.add("list-picker-placeholder");
        placeholder_element.appendChild(document.createTextNode("Please select a value"));

        toggler = new DropdownToggler(component_root, component_dropdown);

        manager = new SelectionManager(
            source_select_box,
            component_dropdown,
            selection_container,
            placeholder_element,
            toggler
        );
    });

    describe("initSelection", () => {
        it("When a value is already selected in the source <select>, then it selects it in the list-picker", () => {
            const option = createOption("Value 1", true, "item-1");
            const item = createItem("item-1", "Value 1", true);

            source_select_box.appendChild(option);
            selection_container.appendChild(placeholder_element);
            component_dropdown.appendChild(item);

            manager.initSelection(placeholder_element);

            expect(selection_container.contains(placeholder_element)).toBe(false);
            expect(selection_container.querySelector(".list-picker-selected-value")).not.toBeNull();
            expect(item.getAttribute("aria-selected")).toEqual("true");
            expect(option.hasAttribute("selected")).toBe(true);
        });

        it("When no value is selected yet in the source <select>, then it does nothing", () => {
            const option = createOption("Value 1", false, "item-1");
            const item = createItem("item-1", "Value 1", false);

            source_select_box.appendChild(option);
            component_dropdown.appendChild(item);
            selection_container.appendChild(placeholder_element);

            manager.initSelection(placeholder_element);

            expect(selection_container.contains(placeholder_element)).toBe(true);
            expect(selection_container.querySelector(".list-picker-selected-value")).toBeNull();
            expect(item.getAttribute("aria-selected")).toEqual("false");
            expect(option.hasAttribute("selected")).toBe(false);
        });
    });

    describe("processSingleSelection", () => {
        it("does nothing if item is already selected", () => {
            const item = createItem("item-1", "Value 1", true);

            manager.processSingleSelection(item);

            expect(item.getAttribute("aria-selected")).toEqual("true");
        });

        it("replaces the placeholder with the currently selected value and toggles the selected attributes on the <select> options", () => {
            const new_option = createOption("new value", true, "item-1");
            const selected_item = createItem("item-1", "Value 1", false);

            source_select_box.appendChild(new_option);
            selection_container.appendChild(placeholder_element);

            manager.processSingleSelection(selected_item);

            const selected_value = selection_container.querySelector(".list-picker-selected-value");

            expect(selection_container.contains(placeholder_element)).toBe(false);
            expect(selected_value).not.toBeNull();
            expect(selected_value?.textContent).toContain("Value 1");
            expect(selected_item.getAttribute("aria-selected")).toEqual("true");
            expect(new_option.hasAttribute("selected")).toBe(true);
        });

        it("replaces the previously selected value with the current one and toggles the selected attributes on the <select> options", () => {
            const old_value = createItem("old-value", "Old value", true);
            const old_selection = createSelection("Old value", "old-value");
            const selected_item = createItem("new-value", "New value", false);
            const old_option = createOption("Old value", true, "old-value");
            const new_option = createOption("new value", true, "new-value");

            source_select_box.appendChild(old_option);
            source_select_box.appendChild(new_option);

            component_dropdown.appendChild(old_value);
            selection_container.appendChild(old_selection);

            manager.processSingleSelection(selected_item);

            const selected_value = selection_container.querySelector(".list-picker-selected-value");
            expect(selected_value).not.toBeNull();
            expect(selected_value?.textContent).toContain("New value");
            expect(old_value.getAttribute("aria-selected")).toEqual("false");
            expect(selected_item.getAttribute("aria-selected")).toEqual("true");
            expect(old_option.hasAttribute("selected")).toBe(false);
            expect(new_option.hasAttribute("selected")).toBe(true);
        });
    });

    describe("unselects the option and item when the user clicks on the cross in the selection container", () => {
        it("should replace the currently selected value with the placeholder and remove the selected attribute on the source <option>", () => {
            const openListPicker = jest.spyOn(toggler, "openListPicker");
            const option_to_select = createOption("Selected option", false, "selected-item");
            const item_to_select = createItem("selected-item", "Selected option", false);

            component_dropdown.appendChild(item_to_select);
            source_select_box.appendChild(option_to_select);
            selection_container.appendChild(placeholder_element);

            // First select the item
            manager.processSingleSelection(item_to_select);

            expect(item_to_select.getAttribute("aria-selected")).toEqual("true");
            expect(selection_container.contains(placeholder_element)).toBe(false);

            expect(option_to_select.hasAttribute("selected")).toBe(true);
            const remove_item_button = selection_container.querySelector(
                ".list-picker-selected-value-remove-button"
            );
            if (!(remove_item_button instanceof Element)) {
                throw new Error("No remove button found, something has gone wrong");
            }

            // Now unselect the item
            remove_item_button.dispatchEvent(new MouseEvent("click"));
            expect(item_to_select.getAttribute("aria-selected")).toEqual("false");
            expect(option_to_select.hasAttribute("selected")).toBe(false);
            expect(selection_container.contains(placeholder_element)).toBe(true);
            expect(openListPicker).toHaveBeenCalled();
        });
    });
});
