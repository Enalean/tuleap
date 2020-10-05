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

import { EventManager } from "./EventManager";
import { SingleSelectionManager } from "../selection/SingleSelectionManager";
import { DropdownToggler } from "./DropdownToggler";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";
import { DropdownContentRenderer } from "./DropdownContentRenderer";

describe("event manager", () => {
    let doc: HTMLDocument,
        source_select_box: HTMLSelectElement,
        component_wrapper: Element,
        manager: EventManager,
        toggler: DropdownToggler,
        clickable_item: Element,
        search_field: HTMLInputElement,
        renderFilteredListPickerDropdownContent: (filter_query: string) => void,
        processSelection: () => void,
        handleBackspaceKey: () => void;

    function getSearchField(search_field_element: HTMLInputElement | null): HTMLInputElement {
        if (search_field_element === null) {
            throw new Error("search_field is null");
        }
        return search_field_element;
    }

    beforeEach(() => {
        source_select_box = document.createElement("select");

        const {
            wrapper_element,
            dropdown_element,
            dropdown_list_element,
            search_field_element,
            selection_element,
        } = new BaseComponentRenderer(source_select_box, {
            is_filterable: true,
        }).renderBaseComponent();

        component_wrapper = wrapper_element;
        clickable_item = document.createElement("li");

        doc = document.implementation.createHTMLDocument();
        clickable_item.classList.add("list-picker-dropdown-option-value");
        dropdown_list_element.appendChild(clickable_item);

        search_field = getSearchField(search_field_element);
        renderFilteredListPickerDropdownContent = jest.fn();
        processSelection = jest.fn();
        handleBackspaceKey = jest.fn();

        toggler = new DropdownToggler(
            component_wrapper,
            dropdown_element,
            dropdown_list_element,
            search_field_element,
            selection_element
        );

        manager = new EventManager(
            doc,
            component_wrapper,
            dropdown_element,
            search_field_element,
            source_select_box,
            ({ processSelection, handleBackspaceKey } as unknown) as SingleSelectionManager,
            toggler,
            ({ renderFilteredListPickerDropdownContent } as unknown) as DropdownContentRenderer
        );
    });

    it("When the source <select> is disabled, then it should not attach any event", () => {
        jest.spyOn(doc, "addEventListener");
        jest.spyOn(component_wrapper, "addEventListener");
        jest.spyOn(search_field, "addEventListener");
        jest.spyOn(clickable_item, "addEventListener");

        source_select_box.setAttribute("disabled", "disabled");

        manager.attachEvents();

        expect(doc.addEventListener).not.toHaveBeenCalled();
        expect(component_wrapper.addEventListener).not.toHaveBeenCalled();
        expect(search_field.addEventListener).not.toHaveBeenCalled();
        expect(clickable_item.addEventListener).not.toHaveBeenCalled();
    });

    describe("Dropdown opening", () => {
        it("Opens the dropdown when I click on the component root, closes it when it is open", () => {
            const openListPicker = jest.spyOn(toggler, "openListPicker");
            const closeListPicker = jest.spyOn(toggler, "closeListPicker");

            manager.attachEvents();

            component_wrapper.dispatchEvent(new MouseEvent("click"));
            expect(openListPicker).toHaveBeenCalled();

            component_wrapper.dispatchEvent(new MouseEvent("click"));
            expect(closeListPicker).toHaveBeenCalled();
        });

        it("Does not open the dropdown when I click on the component root while the source <select> is disabled", () => {
            const openListPicker = jest.spyOn(toggler, "openListPicker");
            source_select_box.setAttribute("disabled", "disabled");

            manager.attachEvents();
            component_wrapper.dispatchEvent(new MouseEvent("click"));

            expect(openListPicker).not.toHaveBeenCalled();
        });
    });

    describe("Dropdown closure", () => {
        it("should close the dropdown when the escape key has been pressed", () => {
            const closeListPicker = jest.spyOn(toggler, "closeListPicker");
            manager.attachEvents();

            [{ key: "Escape" }, { key: "Esc" }, { keyCode: 27 }].forEach(
                (event_init: KeyboardEventInit) => {
                    doc.dispatchEvent(new KeyboardEvent("keyup", event_init));

                    expect(closeListPicker).toHaveBeenCalled();
                }
            );
        });

        it("should close the dropdown when the user clicks outside the list-picker", () => {
            const closeListPicker = jest.spyOn(toggler, "closeListPicker");
            manager.attachEvents();

            doc.dispatchEvent(new MouseEvent("click"));

            expect(closeListPicker).toHaveBeenCalled();
        });
    });

    describe("Item selection", () => {
        it("processes the selection when an item is clicked in the dropdown list", () => {
            manager.attachEvents();
            clickable_item.dispatchEvent(new MouseEvent("click"));
            expect(processSelection).toHaveBeenCalled();
        });
    });

    describe("Search input events", () => {
        it("should filter the items when user types in the search input", () => {
            manager.attachEvents();
            search_field.value = "query";
            search_field.dispatchEvent(new Event("keyup"));

            expect(renderFilteredListPickerDropdownContent).toHaveBeenCalledWith("query");
        });

        it("should open the list picker when the search input has the focus", () => {
            const openListPicker = jest.spyOn(toggler, "openListPicker");

            manager.attachEvents();
            search_field.dispatchEvent(new Event("focus"));

            expect(openListPicker).toHaveBeenCalled();
        });

        it("should handle the backspace key when the user presses it", () => {
            manager.attachEvents();

            [{ key: "Backspace" }, { keyCode: 8 }].forEach((event_init: KeyboardEventInit) => {
                search_field.dispatchEvent(new KeyboardEvent("keydown", event_init));

                expect(handleBackspaceKey).toHaveBeenCalled();
            });
        });
    });
});
