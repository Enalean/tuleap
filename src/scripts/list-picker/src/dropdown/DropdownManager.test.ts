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

import { DropdownManager } from "./DropdownManager";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";
import { ScrollingManager } from "../events/ScrollingManager";

describe("dropdown-manager", () => {
    let doc: HTMLDocument,
        wrapper: HTMLElement,
        list_picker: Element,
        dropdown: HTMLElement,
        list: Element,
        search_field: HTMLInputElement,
        selection_container: Element,
        dropdown_manager: DropdownManager,
        scroll_manager: ScrollingManager,
        ResizeObserverSpy: jest.SpyInstance,
        disconnect: jest.SpyInstance;

    function getSearchField(search_field_element: HTMLInputElement | null): HTMLInputElement {
        if (search_field_element === null) {
            throw new Error("search_field is null");
        }
        return search_field_element;
    }

    beforeEach(() => {
        disconnect = jest.fn();
        window.ResizeObserver = ResizeObserverSpy = jest.fn().mockImplementation(() => {
            return {
                observe: jest.fn(),
                disconnect,
            };
        });
    });

    beforeEach(() => {
        const {
            wrapper_element,
            list_picker_element,
            dropdown_element,
            dropdown_list_element,
            search_field_element,
            selection_element,
        } = new BaseComponentRenderer(
            document.implementation.createHTMLDocument(),
            document.createElement("select"),
            {
                is_filterable: true,
            }
        ).renderBaseComponent();

        scroll_manager = ({
            lockScrolling: jest.fn(),
            unlockScrolling: jest.fn(),
        } as unknown) as ScrollingManager;

        doc = document.implementation.createHTMLDocument();
        wrapper = wrapper_element;
        list_picker = list_picker_element;
        dropdown = dropdown_element;
        list = dropdown_list_element;
        selection_container = selection_element;
        search_field = getSearchField(search_field_element);
        dropdown_manager = new DropdownManager(
            doc,
            wrapper_element,
            list_picker,
            dropdown,
            list,
            search_field_element,
            selection_element,
            scroll_manager
        );
    });

    afterEach(() => {
        dropdown_manager.destroy();
    });

    it("opens the dropdown by appending a 'shown' class to the dropdown element, focuses the search input and moves it under the list-picker", () => {
        expect(ResizeObserverSpy).toHaveBeenCalled();
        jest.spyOn(search_field, "focus");
        dropdown_manager.openListPicker();

        expect(list_picker.classList).toContain("list-picker-with-open-dropdown");
        expect(dropdown.classList).toContain("list-picker-dropdown-shown");
        expect(list.getAttribute("aria-expanded")).toBe("true");
        expect(search_field.focus).toHaveBeenCalled();
        expect(scroll_manager.lockScrolling).toHaveBeenCalled();
        expect(dropdown.style).toContain("top");
        expect(dropdown.style).toContain("left");
        expect(dropdown.style).toContain("width");
    });

    it("closes the dropdown by removing the 'shown' class to the dropdown element", () => {
        expect(ResizeObserverSpy).toHaveBeenCalled();
        dropdown_manager.openListPicker();
        dropdown_manager.closeListPicker();

        expect(list_picker.classList).not.toContain("list-picker-with-open-dropdown");
        expect(dropdown.classList).not.toContain("list-picker-dropdown-shown");
        expect(list.getAttribute("aria-expanded")).toBe("false");
        expect(scroll_manager.unlockScrolling).toHaveBeenCalled();
    });

    it("should not open the list picker if it's already open", () => {
        expect(ResizeObserverSpy).toHaveBeenCalled();
        dropdown.classList.add("list-picker-dropdown-shown");

        jest.spyOn(dropdown.classList, "add");
        dropdown_manager.openListPicker();

        expect(dropdown.classList.add).not.toHaveBeenCalled();
        expect(scroll_manager.lockScrolling).not.toHaveBeenCalled();
    });

    it("should not close the list picker if it's already closed", () => {
        expect(ResizeObserverSpy).toHaveBeenCalled();
        jest.spyOn(dropdown.classList, "remove");
        dropdown_manager.closeListPicker();

        expect(dropdown.classList.remove).not.toHaveBeenCalled();
        expect(scroll_manager.unlockScrolling).not.toHaveBeenCalled();
    });

    it("sets the aria-expanded attribute on the selection element when needed", () => {
        expect(ResizeObserverSpy).toHaveBeenCalled();
        selection_container.setAttribute("aria-expanded", "false");
        dropdown_manager.openListPicker();
        expect(selection_container.getAttribute("aria-expanded")).toEqual("true");
        dropdown_manager.closeListPicker();
        expect(selection_container.getAttribute("aria-expanded")).toEqual("false");
    });

    it("should unlock scrolling and stop observing items resize", () => {
        expect(ResizeObserverSpy).toHaveBeenCalled();
        dropdown_manager.destroy();

        expect(disconnect).toHaveBeenCalled();
        expect(scroll_manager.unlockScrolling).toHaveBeenCalled();
    });

    describe("dropdown positioning", () => {
        function getDropdownManagerWithSizedElements(
            document_client_height: number
        ): DropdownManager {
            const mocked_doc = ({
                documentElement: {
                    clientHeight: document_client_height,
                },
                body: document.createElement("body"),
            } as unknown) as HTMLDocument;

            return new DropdownManager(
                mocked_doc,
                wrapper,
                list_picker,
                dropdown,
                list,
                search_field,
                selection_container,
                scroll_manager
            );
        }

        beforeEach(() => {
            jest.spyOn(dropdown, "getBoundingClientRect").mockReturnValue({
                height: 250,
            } as DOMRect);
            jest.spyOn(wrapper, "getBoundingClientRect").mockReturnValue({
                left: 60,
                bottom: 900,
                width: 250,
                height: 40,
            } as DOMRect);
        });

        it("should place the dropdown below the list picker", () => {
            const dropdown_manager = getDropdownManagerWithSizedElements(1200);

            dropdown_manager.openListPicker();

            expect(list_picker.classList).not.toContain("list-picker-with-dropdown-above");
            expect(dropdown.classList).not.toContain("list-picker-dropdown-above");
            expect(dropdown.style.left).toEqual("60px");
            expect(dropdown.style.width).toEqual("250px");
            expect(dropdown.style.top).toEqual("900px"); // Below the wrapper

            dropdown_manager.destroy();
        });

        it("should place the dropdown on top of the list picker when there is not enough room below it", () => {
            const dropdown_manager = getDropdownManagerWithSizedElements(1000);

            dropdown_manager.openListPicker();

            expect(list_picker.classList).toContain("list-picker-with-dropdown-above");
            expect(dropdown.classList).toContain("list-picker-dropdown-above");
            expect(dropdown.style.left).toEqual("60px");
            expect(dropdown.style.width).toEqual("250px");
            expect(dropdown.style.top).toEqual("610px"); // Above the wrapper

            dropdown_manager.destroy();
        });
    });
});
