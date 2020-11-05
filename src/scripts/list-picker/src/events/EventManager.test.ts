/*
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
import { DropdownToggler } from "../dropdown/DropdownToggler";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";
import { DropdownContentRenderer } from "../renderers/DropdownContentRenderer";
import { KeyboardNavigationManager } from "../navigation/KeyboardNavigationManager";
import { ListItemHighlighter } from "../navigation/ListItemHighlighter";
import { SelectionManager } from "../type";

describe("event manager", () => {
    let doc: HTMLDocument,
        source_select_box: HTMLSelectElement,
        component_wrapper: Element,
        dropdown: Element,
        manager: EventManager,
        toggler: DropdownToggler,
        clickable_item: Element,
        search_field: HTMLInputElement,
        item_highlighter: ListItemHighlighter,
        dropdown_content_renderer: DropdownContentRenderer,
        selection_manager: SelectionManager,
        navigation_manager: KeyboardNavigationManager;

    function getSearchField(search_field_element: HTMLInputElement | null): HTMLInputElement {
        if (search_field_element === null) {
            throw new Error("search_field is null");
        }
        return search_field_element;
    }

    beforeEach(() => {
        source_select_box = document.createElement("select");
        source_select_box.setAttribute("multiple", "multiple");

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
        dropdown = dropdown_element;
        toggler = new DropdownToggler(
            component_wrapper,
            dropdown_element,
            dropdown_list_element,
            search_field_element,
            selection_element
        );

        item_highlighter = ({
            resetHighlight: jest.fn(),
            highlightItem: jest.fn(),
            getHighlightedItem: jest.fn(),
        } as unknown) as ListItemHighlighter;

        dropdown_content_renderer = ({
            renderFilteredListPickerDropdownContent: jest.fn(),
            renderAfterDependenciesUpdate: jest.fn(),
        } as unknown) as DropdownContentRenderer;

        selection_manager = ({
            processSelection: jest.fn(),
            handleBackspaceKey: jest.fn(),
            resetAfterDependenciesUpdate: jest.fn(),
        } as unknown) as SingleSelectionManager;

        navigation_manager = ({ navigate: jest.fn() } as unknown) as KeyboardNavigationManager;

        manager = new EventManager(
            doc,
            component_wrapper,
            dropdown_element,
            search_field_element,
            source_select_box,
            selection_manager,
            toggler,
            dropdown_content_renderer,
            navigation_manager,
            item_highlighter
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
            expect(item_highlighter.resetHighlight).toHaveBeenCalledTimes(1);

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

        it("In a single list-picker, when a keyboard selection has occurred, and user hits Enter, then it should reopen the dropdown", () => {
            const single_select = document.createElement("select");
            const manager = new EventManager(
                doc,
                component_wrapper,
                dropdown,
                null,
                single_select,
                selection_manager,
                toggler,
                dropdown_content_renderer,
                navigation_manager,
                item_highlighter
            );

            jest.spyOn(toggler, "openListPicker");
            jest.spyOn(toggler, "closeListPicker");
            dropdown.classList.add("list-picker-dropdown-shown");
            manager.attachEvents();

            // Keyboard selection has occurred
            jest.spyOn(item_highlighter, "getHighlightedItem").mockReturnValueOnce(clickable_item);
            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter" }));
            expect(toggler.closeListPicker).toHaveBeenCalled();

            // Now user hits the Enter key again
            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter" }));
            expect(toggler.openListPicker).toHaveBeenCalled();

            // Now user closes the dropdown without selecting any item
            doc.dispatchEvent(new Event("click"));
            expect(toggler.closeListPicker).toHaveBeenCalledTimes(2);

            // And finally, he hits enter once again
            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter" }));
            expect(toggler.openListPicker).toHaveBeenCalledTimes(1);
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
            expect(selection_manager.processSelection).toHaveBeenCalled();
        });
    });

    describe("Search input events", () => {
        it("should filter the items when user types in the search input", () => {
            manager.attachEvents();
            search_field.value = "query";
            search_field.dispatchEvent(new KeyboardEvent("keyup"));

            expect(
                dropdown_content_renderer.renderFilteredListPickerDropdownContent
            ).toHaveBeenCalledWith("query");
            expect(item_highlighter.resetHighlight).toHaveBeenCalledTimes(1);
        });

        it("should open the multiple list picker when the search input has the focus", () => {
            const openListPicker = jest.spyOn(toggler, "openListPicker");

            manager.attachEvents();
            search_field.dispatchEvent(new Event("focus"));

            expect(openListPicker).toHaveBeenCalled();
        });

        it("should handle the backspace key when the user presses it", () => {
            manager.attachEvents();

            [{ key: "Backspace" }, { keyCode: 8 }].forEach((event_init: KeyboardEventInit) => {
                search_field.dispatchEvent(new KeyboardEvent("keydown", event_init));

                expect(selection_manager.handleBackspaceKey).toHaveBeenCalled();
            });
        });

        it("should open the dropdown when the Enter key is pressed", () => {
            jest.spyOn(toggler, "openListPicker");

            manager.attachEvents();
            search_field.dispatchEvent(new KeyboardEvent("keyup", { key: "Enter" }));

            expect(toggler.openListPicker).toHaveBeenCalled();
        });

        it("should not reopen the dropdown when a keyboard selection has just occurred", () => {
            const highlighted_item = document.createElement("li");
            jest.spyOn(item_highlighter, "getHighlightedItem").mockReturnValue(highlighted_item);
            jest.spyOn(toggler, "openListPicker");
            dropdown.classList.add("list-picker-dropdown-shown");

            manager.attachEvents();

            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter" }));
            search_field.dispatchEvent(new KeyboardEvent("keyup", { key: "Enter" }));

            expect(toggler.openListPicker).not.toHaveBeenCalled();
        });
    });

    describe("removeEventsListenersOnDocument", () => {
        it("should remove the keyup event on document", () => {
            const closeListPicker = jest.spyOn(toggler, "closeListPicker");
            manager.attachEvents();
            manager.removeEventsListenersOnDocument();
            doc.dispatchEvent(new Event("keyup"));
            expect(closeListPicker).not.toHaveBeenCalled();
        });

        it("should remove the click event on document", () => {
            const closeListPicker = jest.spyOn(toggler, "closeListPicker");
            manager.attachEvents();
            manager.removeEventsListenersOnDocument();
            doc.dispatchEvent(new Event("click"));
            expect(closeListPicker).not.toHaveBeenCalled();
        });

        it("should remove the keydown event on document", () => {
            manager.attachEvents();
            manager.removeEventsListenersOnDocument();
            doc.dispatchEvent(new Event("keydown"));
            expect(navigation_manager.navigate).not.toHaveBeenCalled();
        });
    });

    describe("attachSourceSelectBoxChangeEvent", () => {
        describe("Forms error handling", () => {
            it("should add an 'error' class on the component wrapper when the source <select> value has changed and is invalid", () => {
                jest.spyOn(source_select_box, "checkValidity").mockImplementation(() => false);
                manager.attachEvents();

                source_select_box.dispatchEvent(new Event("change"));
                expect(component_wrapper.classList).toContain("list-picker-error");
            });

            it("should remove the 'error' class on the component wrapper when the source <select> value has changed and is valid", () => {
                jest.spyOn(source_select_box, "checkValidity").mockImplementation(() => true);
                manager.attachEvents();

                source_select_box.dispatchEvent(new Event("change"));
                expect(component_wrapper.classList).not.toContain("list-picker-error");
            });
        });
    });

    describe("Keyboard navigation", () => {
        it("should not call the navigation manager when the dropdown is closed", () => {
            manager.attachEvents();

            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "ArrowUp" }));

            expect(navigation_manager.navigate).not.toHaveBeenCalled();
        });

        it("should call the navigation manager when the dropdown is open", () => {
            jest.spyOn(item_highlighter, "getHighlightedItem").mockReturnValue(null);
            dropdown.classList.add("list-picker-dropdown-shown");

            manager.attachEvents();
            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "ArrowUp" }));

            expect(navigation_manager.navigate).toHaveBeenCalled();
        });

        it("should select the currently highlighted item when the Enter key is pressed", () => {
            const highlighted_item = document.createElement("li");
            jest.spyOn(item_highlighter, "getHighlightedItem").mockReturnValue(highlighted_item);
            jest.spyOn(toggler, "closeListPicker");
            dropdown.classList.add("list-picker-dropdown-shown");

            manager.attachEvents();
            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter" }));

            expect(navigation_manager.navigate).not.toHaveBeenCalled();
            expect(selection_manager.processSelection).toHaveBeenCalledWith(highlighted_item);
            expect(item_highlighter.resetHighlight).toHaveBeenCalled();
            expect(toggler.closeListPicker).toHaveBeenCalled();
        });
    });
});
