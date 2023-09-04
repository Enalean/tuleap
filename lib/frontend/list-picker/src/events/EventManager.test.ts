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

import { describe, it, expect, vi, beforeEach } from "vitest";
import { EventManager } from "./EventManager";
import type { SingleSelectionManager } from "../selection/SingleSelectionManager";
import type { DropdownManager } from "../dropdown/DropdownManager";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";
import type { DropdownContentRenderer } from "../renderers/DropdownContentRenderer";
import type { KeyboardNavigationManager } from "../navigation/KeyboardNavigationManager";
import type { ListItemHighlighter } from "../navigation/ListItemHighlighter";
import type { SelectionManager } from "../type";
import type { FieldFocusManager } from "../navigation/FieldFocusManager";

describe("event manager", () => {
    let doc: HTMLDocument,
        source_select_box: HTMLSelectElement,
        component_wrapper: HTMLElement,
        list_picker_input: Element,
        dropdown: Element,
        manager: EventManager,
        dropdown_manager: DropdownManager,
        clickable_item: Element,
        search_field: HTMLInputElement,
        item_highlighter: ListItemHighlighter,
        dropdown_content_renderer: DropdownContentRenderer,
        selection_manager: SelectionManager,
        navigation_manager: KeyboardNavigationManager,
        field_focus_manager: FieldFocusManager;

    function getSearchField(search_field_element: HTMLInputElement | null): HTMLInputElement {
        if (search_field_element === null) {
            throw new Error("search_field is null");
        }
        return search_field_element;
    }

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        source_select_box = document.createElement("select");
        source_select_box.setAttribute("multiple", "multiple");

        const {
            wrapper_element,
            list_picker_element,
            dropdown_element,
            dropdown_list_element,
            search_field_element,
        } = new BaseComponentRenderer(doc, source_select_box, {
            is_filterable: true,
        }).renderBaseComponent();

        component_wrapper = wrapper_element;
        list_picker_input = list_picker_element;
        clickable_item = document.createElement("li");
        clickable_item.classList.add("list-picker-dropdown-option-value");
        dropdown_list_element.appendChild(clickable_item);

        search_field = getSearchField(search_field_element);
        dropdown = dropdown_element;
        dropdown_manager = {
            openListPicker: vi.fn(),
            closeListPicker: vi.fn(),
            isDropdownOpen: vi.fn(),
        } as unknown as DropdownManager;

        item_highlighter = {
            resetHighlight: vi.fn(),
            highlightItem: vi.fn(),
            getHighlightedItem: vi.fn(),
        } as unknown as ListItemHighlighter;

        dropdown_content_renderer = {
            renderFilteredListPickerDropdownContent: vi.fn(),
            renderAfterDependenciesUpdate: vi.fn(),
        } as unknown as DropdownContentRenderer;

        selection_manager = {
            processSelection: vi.fn(),
            handleBackspaceKey: vi.fn(),
            resetAfterDependenciesUpdate: vi.fn(),
        } as unknown as SingleSelectionManager;

        field_focus_manager = {
            doesSelectionElementHaveTheFocus: vi.fn(),
        } as unknown as FieldFocusManager;

        navigation_manager = { navigate: vi.fn() } as unknown as KeyboardNavigationManager;

        manager = new EventManager(
            doc,
            component_wrapper,
            list_picker_input,
            dropdown_element,
            search_field_element,
            source_select_box,
            selection_manager,
            dropdown_manager,
            dropdown_content_renderer,
            navigation_manager,
            item_highlighter,
            field_focus_manager,
        );
    });

    describe("Dropdown opening", () => {
        it("Opens the dropdown when I click on the component root, closes it when it is open", () => {
            const isDropdownOpen = vi.spyOn(dropdown_manager, "isDropdownOpen");
            isDropdownOpen.mockReturnValueOnce(false);

            manager.attachEvents();

            list_picker_input.dispatchEvent(new MouseEvent("pointerdown"));
            expect(dropdown_manager.openListPicker).toHaveBeenCalled();
            expect(item_highlighter.resetHighlight).toHaveBeenCalledTimes(1);

            isDropdownOpen.mockReturnValueOnce(true);

            list_picker_input.dispatchEvent(new MouseEvent("pointerdown"));
            expect(dropdown_manager.closeListPicker).toHaveBeenCalled();
        });

        it("Does not open the dropdown when I click on the component root while the source <select> is disabled", () => {
            manager.attachEvents();

            source_select_box.setAttribute("disabled", "disabled");

            component_wrapper.dispatchEvent(new MouseEvent("click"));

            expect(dropdown_manager.openListPicker).not.toHaveBeenCalled();
        });

        it("In a single list-picker, when a keyboard selection has occurred, and user hits Enter, then it should reopen the dropdown", () => {
            const single_select = document.createElement("select");
            const manager = new EventManager(
                doc,
                component_wrapper,
                list_picker_input,
                dropdown,
                null,
                single_select,
                selection_manager,
                dropdown_manager,
                dropdown_content_renderer,
                navigation_manager,
                item_highlighter,
                field_focus_manager,
            );

            const isDropdownOpen = vi.spyOn(dropdown_manager, "isDropdownOpen");
            const doesSelectionElementHaveTheFocus = vi.spyOn(
                field_focus_manager,
                "doesSelectionElementHaveTheFocus",
            );

            manager.attachEvents();

            // Keyboard selection has occurred
            isDropdownOpen.mockReturnValueOnce(true);
            vi.spyOn(item_highlighter, "getHighlightedItem").mockReturnValueOnce(clickable_item);
            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter" }));
            expect(dropdown_manager.closeListPicker).toHaveBeenCalled();

            // Now user hits the Enter key again
            isDropdownOpen.mockReturnValueOnce(false);
            doesSelectionElementHaveTheFocus.mockReturnValue(true);
            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter" }));
            expect(dropdown_manager.openListPicker).toHaveBeenCalled();

            // Now user closes the dropdown without selecting any item
            isDropdownOpen.mockReturnValueOnce(true);
            doesSelectionElementHaveTheFocus.mockReturnValue(false);
            doc.dispatchEvent(new Event("click"));

            expect(dropdown_manager.closeListPicker).toHaveBeenCalledTimes(1);

            // And finally, he hits enter once again
            isDropdownOpen.mockReturnValueOnce(false);
            doesSelectionElementHaveTheFocus.mockReturnValue(true);
            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter" }));
            expect(dropdown_manager.openListPicker).toHaveBeenCalledTimes(1);
        });
    });

    describe("Dropdown closure", () => {
        it("should close the dropdown when the escape key has been pressed", () => {
            manager.attachEvents();

            [{ key: "Escape" }, { key: "Esc" }, { keyCode: 27 }].forEach(
                (event_init: KeyboardEventInit) => {
                    doc.dispatchEvent(new KeyboardEvent("keyup", event_init));

                    expect(dropdown_manager.closeListPicker).toHaveBeenCalled();
                },
            );
        });

        it("should close the dropdown when the user mouse downs outside the list-picker", () => {
            manager.attachEvents();
            doc.dispatchEvent(new MouseEvent("pointerdown"));

            expect(dropdown_manager.closeListPicker).toHaveBeenCalled();
        });
    });

    describe("Item selection", () => {
        it("processes the selection when an item is clicked in the dropdown list", () => {
            manager.attachEvents();
            clickable_item.dispatchEvent(new MouseEvent("pointerup"));
            expect(selection_manager.processSelection).toHaveBeenCalled();
            expect(dropdown_manager.closeListPicker).toHaveBeenCalled();
        });
    });

    describe("Search input events", () => {
        it("should filter the items when user types in the search input", () => {
            manager.attachEvents();
            search_field.value = "query";
            search_field.dispatchEvent(new KeyboardEvent("keyup"));

            expect(
                dropdown_content_renderer.renderFilteredListPickerDropdownContent,
            ).toHaveBeenCalledWith("query");
            expect(item_highlighter.resetHighlight).toHaveBeenCalledTimes(1);
        });

        it("should open the multiple list picker when the search input has the focus", () => {
            manager.attachEvents();
            search_field.dispatchEvent(new Event("pointerdown"));

            expect(dropdown_manager.openListPicker).toHaveBeenCalled();
        });

        it("should handle the backspace key when the user presses it", () => {
            manager.attachEvents();

            [{ key: "Backspace" }, { keyCode: 8 }].forEach((event_init: KeyboardEventInit) => {
                search_field.dispatchEvent(new KeyboardEvent("keydown", event_init));

                expect(selection_manager.handleBackspaceKey).toHaveBeenCalled();
            });
        });

        it("should erase the search input content and reset the dropdown content when the tab key has been pressed", () => {
            vi.spyOn(dropdown_manager, "isDropdownOpen").mockReturnValue(true);

            manager.attachEvents();

            [{ key: "Tab" }, { keyCode: 9 }].forEach((event_init: KeyboardEventInit) => {
                search_field.value = "an old query";

                search_field.dispatchEvent(new KeyboardEvent("keydown", event_init));

                expect(search_field.value).toBe("");
                expect(
                    dropdown_content_renderer.renderFilteredListPickerDropdownContent,
                ).toHaveBeenCalledWith("");
                expect(item_highlighter.resetHighlight).toHaveBeenCalled();
            });
        });

        it("should open the dropdown when the Enter key is pressed", () => {
            manager.attachEvents();
            search_field.dispatchEvent(new KeyboardEvent("keyup", { key: "Enter" }));

            expect(dropdown_manager.openListPicker).toHaveBeenCalled();
        });

        it("should not reopen the dropdown when a keyboard selection has just occurred", () => {
            const highlighted_item = document.createElement("li");
            vi.spyOn(item_highlighter, "getHighlightedItem").mockReturnValue(highlighted_item);
            vi.spyOn(dropdown_manager, "isDropdownOpen").mockReturnValueOnce(true);

            manager.attachEvents();

            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter" }));
            search_field.dispatchEvent(new KeyboardEvent("keyup", { key: "Enter" }));

            expect(dropdown_manager.openListPicker).not.toHaveBeenCalled();
        });
    });

    describe("removeEventsListenersOnDocument", () => {
        it("should remove the keyup event on document", () => {
            manager.attachEvents();
            manager.removeEventsListenersOnDocument();
            doc.dispatchEvent(new Event("keyup"));
            expect(dropdown_manager.closeListPicker).not.toHaveBeenCalled();
        });

        it("should remove the click event on document", () => {
            manager.attachEvents();
            manager.removeEventsListenersOnDocument();
            doc.dispatchEvent(new Event("click"));
            expect(dropdown_manager.closeListPicker).not.toHaveBeenCalled();
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
                vi.spyOn(source_select_box, "checkValidity").mockImplementation(() => false);
                manager.attachEvents();

                source_select_box.dispatchEvent(new Event("change"));
                expect(component_wrapper.classList.contains("list-picker-error")).toBe(true);
            });

            it("should remove the 'error' class on the component wrapper when the source <select> value has changed and is valid", () => {
                vi.spyOn(source_select_box, "checkValidity").mockImplementation(() => true);
                manager.attachEvents();

                source_select_box.dispatchEvent(new Event("change"));
                expect(component_wrapper.classList.contains("list-picker-error")).toBe(false);
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
            vi.spyOn(item_highlighter, "getHighlightedItem").mockReturnValue(null);
            vi.spyOn(dropdown_manager, "isDropdownOpen").mockReturnValueOnce(true);

            manager.attachEvents();
            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "ArrowUp" }));

            expect(navigation_manager.navigate).toHaveBeenCalled();
        });

        it("should select the currently highlighted item when the Enter key is pressed", () => {
            const highlighted_item = document.createElement("li");
            vi.spyOn(item_highlighter, "getHighlightedItem").mockReturnValue(highlighted_item);
            vi.spyOn(dropdown_manager, "isDropdownOpen").mockReturnValue(true);

            manager.attachEvents();
            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter" }));

            expect(navigation_manager.navigate).not.toHaveBeenCalled();
            expect(selection_manager.processSelection).toHaveBeenCalledWith(highlighted_item);
            expect(item_highlighter.resetHighlight).toHaveBeenCalled();
            expect(dropdown_manager.closeListPicker).toHaveBeenCalled();
        });

        it("should close the dropdown when the tab key has been pressed", () => {
            vi.spyOn(dropdown_manager, "isDropdownOpen").mockReturnValue(true);

            manager.attachEvents();
            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "Tab" }));

            expect(dropdown_manager.closeListPicker).toHaveBeenCalled();
        });
    });
});
