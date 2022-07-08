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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { EventManager } from "./EventManager";
import { ManageDropdownStub } from "../../tests/stubs/ManageDropdownStub";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";
import type { DropdownContentRenderer } from "../renderers/DropdownContentRenderer";
import type { KeyboardNavigationManager } from "../navigation/KeyboardNavigationManager";
import type { ListItemHighlighter } from "../navigation/ListItemHighlighter";
import type { FieldFocusManager } from "../navigation/FieldFocusManager";
import { ClearSearchFieldStub } from "../../tests/stubs/ClearSearchFieldStub";
import { ManageSelectionStub } from "../../tests/stubs/ManageSelectionStub";

describe("event manager", () => {
    let doc: HTMLDocument,
        source_select_box: HTMLSelectElement,
        component_wrapper: HTMLElement,
        link_selector_input: Element,
        dropdown: Element,
        clickable_item: Element,
        search_field: HTMLInputElement,
        item_highlighter: ListItemHighlighter,
        dropdown_content_renderer: DropdownContentRenderer,
        navigation_manager: KeyboardNavigationManager,
        field_focus_manager: FieldFocusManager;

    function getSearchField(search_field_element: HTMLInputElement | null): HTMLInputElement {
        if (search_field_element === null) {
            throw new Error("search_field is null");
        }
        return search_field_element;
    }

    function getEventManager(
        dropdown_element: Element,
        search_field_element: HTMLInputElement,
        dropdown_manager: ManageDropdownStub,
        clear_search_field: ClearSearchFieldStub,
        manage_selection: ManageSelectionStub
    ): EventManager {
        return new EventManager(
            doc,
            component_wrapper,
            link_selector_input,
            dropdown_element,
            search_field_element,
            source_select_box,
            manage_selection,
            dropdown_manager,
            dropdown_content_renderer,
            navigation_manager,
            item_highlighter,
            field_focus_manager,
            clear_search_field
        );
    }

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        source_select_box = document.createElement("select");

        const {
            wrapper_element,
            link_selector_element,
            dropdown_element,
            dropdown_list_element,
            search_field_element,
        } = new BaseComponentRenderer(doc, source_select_box, "").renderBaseComponent();

        component_wrapper = wrapper_element;
        link_selector_input = link_selector_element;
        clickable_item = document.createElement("li");
        clickable_item.classList.add("link-selector-dropdown-option-value");
        dropdown_list_element.appendChild(clickable_item);

        search_field = getSearchField(search_field_element);
        dropdown = dropdown_element;

        item_highlighter = {
            resetHighlight: vi.fn(),
            highlightItem: vi.fn(),
            getHighlightedItem: vi.fn(),
        } as unknown as ListItemHighlighter;

        dropdown_content_renderer = {
            renderFilteredLinkSelectorDropdownContent: vi.fn(),
            renderAfterDependenciesUpdate: vi.fn(),
        } as unknown as DropdownContentRenderer;

        field_focus_manager = {
            doesSelectionElementHaveTheFocus: vi.fn(),
        } as unknown as FieldFocusManager;

        navigation_manager = { navigate: vi.fn() } as unknown as KeyboardNavigationManager;
    });

    it("When the source <select> is disabled, then it should not attach any event", () => {
        const manager = getEventManager(
            dropdown,
            search_field,
            ManageDropdownStub.withClosedDropdown(),
            ClearSearchFieldStub(),
            ManageSelectionStub.withNoSelection()
        );

        vi.spyOn(doc, "addEventListener");
        vi.spyOn(component_wrapper, "addEventListener");
        vi.spyOn(search_field, "addEventListener");
        vi.spyOn(clickable_item, "addEventListener");

        source_select_box.setAttribute("disabled", "disabled");

        manager.attachEvents();

        expect(doc.addEventListener).not.toHaveBeenCalled();
        expect(component_wrapper.addEventListener).not.toHaveBeenCalled();
        expect(search_field.addEventListener).not.toHaveBeenCalled();
        expect(clickable_item.addEventListener).not.toHaveBeenCalled();
    });

    describe("Dropdown opening", () => {
        let manager: EventManager, manage_dropdown: ManageDropdownStub;

        beforeEach(() => {
            manage_dropdown = ManageDropdownStub.withClosedDropdown();
            manager = getEventManager(
                dropdown,
                search_field,
                manage_dropdown,
                ClearSearchFieldStub(),
                ManageSelectionStub.withNoSelection()
            );
        });

        it("Opens the dropdown when I click on the component root, closes it when it is open", () => {
            manager.attachEvents();

            link_selector_input.dispatchEvent(new MouseEvent("pointerdown"));
            expect(manage_dropdown.getOpenLinkSelectorCallCount()).toBe(1);
            expect(item_highlighter.resetHighlight).toHaveBeenCalledTimes(1);

            link_selector_input.dispatchEvent(new MouseEvent("pointerdown"));
            expect(manage_dropdown.getCloseLinkSelectorCallCount()).toBe(1);
        });

        it("Does not open the dropdown when I click on the component root while the source <select> is disabled", () => {
            source_select_box.setAttribute("disabled", "disabled");

            manager.attachEvents();
            component_wrapper.dispatchEvent(new MouseEvent("click"));

            expect(manage_dropdown.getOpenLinkSelectorCallCount()).toBe(0);
        });

        it("When a keyboard selection has occurred, and user hits Enter, then it should reopen the dropdown", () => {
            const doesSelectionElementHaveTheFocus = vi.spyOn(
                field_focus_manager,
                "doesSelectionElementHaveTheFocus"
            );

            manager.attachEvents();

            // Keyboard selection has occurred
            vi.spyOn(item_highlighter, "getHighlightedItem").mockReturnValueOnce(clickable_item);
            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter" }));
            expect(manage_dropdown.isDropdownOpen()).toBe(false);

            // Now user hits the Enter key again
            doesSelectionElementHaveTheFocus.mockReturnValue(true);
            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter" }));
            expect(manage_dropdown.isDropdownOpen()).toBe(true);

            // Now user closes the dropdown without selecting any item
            doesSelectionElementHaveTheFocus.mockReturnValue(false);
            doc.dispatchEvent(new MouseEvent("pointerdown"));
            expect(manage_dropdown.isDropdownOpen()).toBe(false);

            // And finally, he hits enter once again
            doesSelectionElementHaveTheFocus.mockReturnValue(true);
            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter" }));
            expect(manage_dropdown.isDropdownOpen()).toBe(true);
        });
    });

    describe("Dropdown closure", () => {
        let manager: EventManager,
            manage_dropdown: ManageDropdownStub,
            clear_search_field: ClearSearchFieldStub;

        beforeEach(() => {
            manage_dropdown = ManageDropdownStub.withOpenDropdown();
            clear_search_field = ClearSearchFieldStub();
            manager = getEventManager(
                dropdown,
                search_field,
                manage_dropdown,
                clear_search_field,
                ManageSelectionStub.withNoSelection()
            );
        });

        it.each([
            ["Escape", { key: "Escape" }],
            ["Esc", { key: "Esc" }],
        ])(
            "should close the dropdown when the pressent key is %s",
            (key_name: string, event_init: KeyboardEventInit) => {
                manager.attachEvents();
                doc.dispatchEvent(new KeyboardEvent("keyup", event_init));

                expect(manage_dropdown.getCloseLinkSelectorCallCount()).toBe(1);
            }
        );

        it("should close the dropdown when the user clicks outside the link-selector while it is open", () => {
            manager.attachEvents();
            doc.dispatchEvent(new MouseEvent("pointerdown"));

            expect(manage_dropdown.getCloseLinkSelectorCallCount()).toBe(1);
        });

        it(`Given that the dropdown is open and user has not selected any value,
            When the user clicks outside the list picker,
            Then the dropdown is closed and the search field is cleared`, () => {
            manager.attachEvents();
            doc.dispatchEvent(new MouseEvent("pointerdown"));

            expect(clear_search_field.getCallsCount()).toBe(1);
        });

        it(`Given that the dropdown is open and user has selected a value,
            When the user clicks outside the list picker,
            Then the dropdown is closed BUT the search field is left untouched`, () => {
            const manager = getEventManager(
                dropdown,
                search_field,
                manage_dropdown,
                clear_search_field,
                ManageSelectionStub.withSelectedElement(doc.createElement("li"))
            );

            manager.attachEvents();
            doc.dispatchEvent(new MouseEvent("pointerdown"));

            expect(clear_search_field.getCallsCount()).toBe(0);
        });
    });

    describe("Item selection", () => {
        it("processes the selection when an item is clicked in the dropdown list", () => {
            const manage_dropdown = ManageDropdownStub.withOpenDropdown();
            const manage_selection = ManageSelectionStub.withNoSelection();
            const manager = getEventManager(
                dropdown,
                search_field,
                manage_dropdown,
                ClearSearchFieldStub(),
                manage_selection
            );
            manager.attachEvents();

            clickable_item.dispatchEvent(new MouseEvent("pointerup"));
            expect(manage_selection.getProcessSelectionCallCount()).toBe(1);
            expect(manage_dropdown.getCloseLinkSelectorCallCount()).toBe(1);
        });
    });

    describe("Search input events", () => {
        let manager: EventManager, manage_dropdown: ManageDropdownStub;

        beforeEach(() => {
            manage_dropdown = ManageDropdownStub.withOpenDropdown();
            manager = getEventManager(
                dropdown,
                search_field,
                manage_dropdown,
                ClearSearchFieldStub(),
                ManageSelectionStub.withNoSelection()
            );
            manager.attachEvents();
        });

        it("should keep the search input content and reset the highlight when the tab key has been pressed", () => {
            search_field.value = "an old query";
            search_field.dispatchEvent(new KeyboardEvent("keydown", { key: "Tab" }));

            expect(search_field.value).toBe("an old query");
            expect(item_highlighter.resetHighlight).toHaveBeenCalled();
        });

        it("should open the dropdown when the Enter key is pressed", () => {
            search_field.dispatchEvent(new KeyboardEvent("keyup", { key: "Enter" }));

            expect(manage_dropdown.getOpenLinkSelectorCallCount()).toBe(1);
        });

        it("should not reopen the dropdown when a keyboard selection has just occurred", () => {
            const highlighted_item = document.createElement("li");
            vi.spyOn(item_highlighter, "getHighlightedItem").mockReturnValue(highlighted_item);

            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter" }));
            search_field.dispatchEvent(new KeyboardEvent("keyup", { key: "Enter" }));

            expect(manage_dropdown.getOpenLinkSelectorCallCount()).toBe(0);
        });
    });

    describe("removeEventsListenersOnDocument", () => {
        let manager: EventManager, manage_dropdown: ManageDropdownStub;

        beforeEach(() => {
            manage_dropdown = ManageDropdownStub.withClosedDropdown();
            manager = getEventManager(
                dropdown,
                search_field,
                manage_dropdown,
                ClearSearchFieldStub(),
                ManageSelectionStub.withNoSelection()
            );
            manager.attachEvents();
        });

        it("should remove the keyup event on document", () => {
            manager.removeEventsListenersOnDocument();
            doc.dispatchEvent(new Event("keyup"));
            expect(manage_dropdown.getOpenLinkSelectorCallCount()).toBe(0);
        });

        it("should remove the click event on document", () => {
            manager.removeEventsListenersOnDocument();
            doc.dispatchEvent(new Event("click"));
            expect(manage_dropdown.getOpenLinkSelectorCallCount()).toBe(0);
        });

        it("should remove the keydown event on document", () => {
            manager.removeEventsListenersOnDocument();
            doc.dispatchEvent(new Event("keydown"));
            expect(navigation_manager.navigate).not.toHaveBeenCalled();
        });
    });

    describe("attachSourceSelectBoxChangeEvent", () => {
        let manager: EventManager;

        beforeEach(() => {
            manager = getEventManager(
                dropdown,
                search_field,
                ManageDropdownStub.withClosedDropdown(),
                ClearSearchFieldStub(),
                ManageSelectionStub.withNoSelection()
            );
            manager.attachEvents();
        });

        describe("Forms error handling", () => {
            it("should add an 'error' class on the component wrapper when the source <select> value has changed and is invalid", () => {
                vi.spyOn(source_select_box, "checkValidity").mockImplementation(() => false);

                source_select_box.dispatchEvent(new Event("change"));
                expect(component_wrapper.classList.contains("link-selector-error")).toBe(true);
            });

            it("should remove the 'error' class on the component wrapper when the source <select> value has changed and is valid", () => {
                vi.spyOn(source_select_box, "checkValidity").mockImplementation(() => true);

                source_select_box.dispatchEvent(new Event("change"));
                expect(component_wrapper.classList.contains("link-selector-error")).toBe(false);
            });
        });
    });

    describe("Keyboard navigation", () => {
        let manager: EventManager,
            manage_dropdown: ManageDropdownStub,
            manage_selection: ManageSelectionStub;

        beforeEach(() => {
            manage_dropdown = ManageDropdownStub.withOpenDropdown();
            manage_selection = ManageSelectionStub.withNoSelection();
            manager = getEventManager(
                dropdown,
                search_field,
                manage_dropdown,
                ClearSearchFieldStub(),
                manage_selection
            );
        });

        it("should not call the navigation manager when the dropdown is closed", () => {
            const manager = getEventManager(
                dropdown,
                search_field,
                ManageDropdownStub.withClosedDropdown(),
                ClearSearchFieldStub(),
                manage_selection
            );

            manager.attachEvents();
            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "ArrowUp" }));

            expect(navigation_manager.navigate).not.toHaveBeenCalled();
        });

        it("should call the navigation manager when the dropdown is open", () => {
            vi.spyOn(item_highlighter, "getHighlightedItem").mockReturnValue(null);

            manager.attachEvents();
            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "ArrowUp" }));

            expect(navigation_manager.navigate).toHaveBeenCalled();
        });

        it("should select the currently highlighted item when the Enter key is pressed", () => {
            const highlighted_item = document.createElement("li");
            vi.spyOn(item_highlighter, "getHighlightedItem").mockReturnValue(highlighted_item);

            manager.attachEvents();
            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter" }));

            expect(navigation_manager.navigate).not.toHaveBeenCalled();
            expect(manage_selection.getCurrentSelection()).toStrictEqual(highlighted_item);
            expect(item_highlighter.resetHighlight).toHaveBeenCalled();
            expect(manage_dropdown.getCloseLinkSelectorCallCount()).toBe(1);
        });

        it("should close the dropdown when the tab key has been pressed", () => {
            manager.attachEvents();
            doc.dispatchEvent(new KeyboardEvent("keydown", { key: "Tab" }));

            expect(manage_dropdown.getCloseLinkSelectorCallCount()).toBe(1);
        });
    });
});
