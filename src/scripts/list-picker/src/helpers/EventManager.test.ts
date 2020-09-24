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
import { SelectionManager } from "./SelectionManager";
import { DropdownToggler } from "./DropdownToggler";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";

describe("event manager", () => {
    let doc: HTMLDocument,
        source_select_box: HTMLSelectElement,
        component_wrapper: Element,
        manager: EventManager,
        toggler: DropdownToggler,
        clickable_item: Element,
        processSingleSelection: () => void;

    beforeEach(() => {
        source_select_box = document.createElement("select");

        const {
            wrapper_element,
            dropdown_element,
            dropdown_list_element,
        } = new BaseComponentRenderer(source_select_box).renderBaseComponent();

        component_wrapper = wrapper_element;
        clickable_item = document.createElement("li");

        doc = document.implementation.createHTMLDocument();
        clickable_item.classList.add("list-picker-dropdown-option-value");
        dropdown_list_element.appendChild(clickable_item);

        processSingleSelection = jest.fn();

        toggler = new DropdownToggler(component_wrapper, dropdown_element, dropdown_list_element);
        manager = new EventManager(
            doc,
            component_wrapper,
            dropdown_element,
            source_select_box,
            ({ processSingleSelection } as unknown) as SelectionManager,
            toggler
        );
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
            expect(processSingleSelection).toHaveBeenCalled();
        });
    });
});
