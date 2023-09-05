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

import { describe, it, beforeEach, vi, expect } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import type { DropdownManager } from "../dropdown/DropdownManager";
import type { ListPickerItem } from "../type";
import { appendSimpleOptionsToSourceSelectBox } from "../test-helpers/select-box-options-generator";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";
import { MultipleSelectionManager } from "./MultipleSelectionManager";
import type { GettextProvider } from "@tuleap/gettext";
import {
    expectChangeEventToHaveBeenFiredOnSourceSelectBox,
    expectItemNotToBeSelected,
    expectItemToBeSelected,
} from "../test-helpers/selection-manager-test-helpers";
import { ItemsMapManager } from "../items/ItemsMapManager";
import { ListItemMapBuilder } from "../items/ListItemMapBuilder";

describe("MultipleSelectionManager", () => {
    let source_select_box: HTMLSelectElement,
        manager: MultipleSelectionManager,
        manager_without_none: MultipleSelectionManager,
        item_map_manager: ItemsMapManager,
        selection_container: Element,
        search_input: HTMLInputElement,
        gettext_provider: GettextProvider,
        item_1: ListPickerItem,
        item_2: ListPickerItem,
        openListPicker: () => void,
        doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        source_select_box = doc.createElement("select");
        source_select_box.setAttribute("multiple", "multiple");
        appendSimpleOptionsToSourceSelectBox(source_select_box);

        const { selection_element, search_field_element } = new BaseComponentRenderer(
            doc,
            source_select_box,
            {
                placeholder: "Please select some values",
            },
        ).renderBaseComponent();

        gettext_provider = {
            gettext: (english: string) => english,
        } as GettextProvider;
        search_input = search_field_element;
        selection_container = selection_element;
        openListPicker = vi.fn();

        item_map_manager = new ItemsMapManager(new ListItemMapBuilder(source_select_box));

        item_map_manager.refreshItemsMap();

        const item_none = item_map_manager.findListPickerItemInItemMap("list-picker-item-100");

        manager = new MultipleSelectionManager(
            source_select_box,
            selection_element,
            search_field_element,
            "Please select some values",
            { openListPicker } as DropdownManager,
            item_map_manager,
            gettext_provider,
            item_none,
        );

        manager_without_none = new MultipleSelectionManager(
            source_select_box,
            selection_element,
            search_field_element,
            "Please select some values",
            { openListPicker } as DropdownManager,
            item_map_manager,
            gettext_provider,
        );

        item_1 = item_map_manager.findListPickerItemInItemMap("list-picker-item-value_1");
        item_2 = item_map_manager.findListPickerItemInItemMap("list-picker-item-value_2");
    });

    describe("initSelection", () => {
        it("should select items bound to selected <option> as selected", () => {
            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            item_1.target_option.setAttribute("selected", "selected");
            item_2.target_option.setAttribute("selected", "selected");

            manager.initSelection();

            [item_1, item_2].forEach((item) => {
                expect(item.is_selected).toBe(true);
                expect(item.element.getAttribute("aria-selected")).toBe("true");
            });

            expect(search_input.hasAttribute("placeholder")).toBe(false);
            expect(
                selection_container.querySelector(".list-picker-selected-value-remove-button"),
            ).not.toBeNull();
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 0);
        });
    });

    describe("processSelection", () => {
        it("when an item is already selected, then it unselects it", () => {
            item_1.target_option.setAttribute("selected", "selected");
            manager.initSelection();

            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            manager.processSelection(item_1.element);

            expectItemNotToBeSelected(item_1);
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 1);
        });

        it("when an item is unselected and was the only selected item, then the none value is selected", () => {
            item_1.target_option.setAttribute("selected", "selected");
            const item_none = item_map_manager.findListPickerItemInItemMap("list-picker-item-100");
            manager.initSelection();

            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            manager.processSelection(item_1.element);

            expectItemToBeSelected(item_none);
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 1);
        });

        it("when the first item is selected, the placeholder on the search input is removed, and the 'clear all values' button is added", () => {
            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            expect(search_input.getAttribute("placeholder")).toBe("Please select some values");
            expect(
                selection_container.querySelector(".list-picker-selected-value-remove-button"),
            ).toBeNull();

            manager.processSelection(item_1.element);

            expect(search_input.hasAttribute("placeholder")).toBe(false);
            expect(
                selection_container.querySelector(".list-picker-selected-value-remove-button"),
            ).not.toBeNull();
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 1);
        });

        it("when item is disabled, then it does not displayed", () => {
            item_1.target_option.setAttribute("disabled", "disabled");
            const item_none = item_map_manager.findListPickerItemInItemMap("list-picker-item-100");

            manager.initSelection();
            manager.processSelection(item_1.element);
            expectItemNotToBeSelected(item_none);
        });

        it("selects items", () => {
            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            const colored_item = item_map_manager.findListPickerItemInItemMap(
                "list-picker-item-value_colored",
            );

            manager.processSelection(item_1.element);
            manager.processSelection(item_2.element);
            manager.processSelection(colored_item.element);

            expectItemToBeSelected(item_1);
            expectItemToBeSelected(item_2);
            expectItemToBeSelected(colored_item);

            const items_badges = selection_container.querySelectorAll("[class*=list-picker-badge]");

            expect(items_badges).toHaveLength(3);
            expect(items_badges[0].textContent).toContain("Value 1");
            expect(items_badges[1].textContent).toContain("Value 2");
            expect(items_badges[2].textContent).toContain("Value Colored");
            expect(
                items_badges[0].querySelector(".list-picker-value-remove-button"),
            ).not.toBeNull();
            expect(
                items_badges[1].querySelector(".list-picker-value-remove-button"),
            ).not.toBeNull();
            expect(
                items_badges[2].querySelector(".list-picker-value-remove-button"),
            ).not.toBeNull();

            expect(items_badges[0].className).toContain("list-picker-badge");
            expect(items_badges[1].className).toContain("list-picker-badge");
            expect(items_badges[2].className).toContain("list-picker-badge-acid-green");

            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 3);
        });
    });

    describe("unselecting items", () => {
        describe(`with "none" value`, () => {
            let item_none: ListPickerItem;

            beforeEach(() => {
                item_none = item_map_manager.findListPickerItemInItemMap("list-picker-item-100");
            });

            describe(`when I click on the X button in the badge of a selected item`, () => {
                let x_button: HTMLElement;

                beforeEach(() => {
                    manager.processSelection(item_1.element);
                    x_button = selectOrThrow(
                        selection_container,
                        ".list-picker-badge[title='Value 1'] > .list-picker-value-remove-button",
                    );
                });

                it(`then the item should be unselected and "none" value should be selected`, () => {
                    const dispatch = vi.spyOn(source_select_box, "dispatchEvent");

                    x_button.dispatchEvent(new Event("pointerup"));

                    expectItemNotToBeSelected(item_1);
                    expectItemToBeSelected(item_none);
                    expect(openListPicker).toHaveBeenCalled();
                    expect(
                        selection_container.querySelector(
                            ".list-picker-badge[title='Value 1'] > .list-picker-value-remove-button",
                        ),
                    ).toBeNull();
                    expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 1);
                });

                it(`and when there is still another value selected
                then the item should be unselected
                and "none" value should not be selected`, () => {
                    manager.processSelection(item_2.element);
                    const dispatch = vi.spyOn(source_select_box, "dispatchEvent");

                    x_button.dispatchEvent(new Event("pointerup"));

                    expectItemNotToBeSelected(item_none);
                    expectItemNotToBeSelected(item_1);
                    expectItemToBeSelected(item_2);
                    expect(openListPicker).toHaveBeenCalled();
                    expect(
                        selection_container.querySelector(
                            ".list-picker-badge[title='Value 1'] > .list-picker-value-remove-button",
                        ),
                    ).toBeNull();
                    expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 1);
                });
            });

            it(`When the 'remove all value' button is clicked, then "none" value should be selected`, () => {
                manager.processSelection(item_1.element);
                manager.processSelection(item_2.element);
                const dispatch = vi.spyOn(source_select_box, "dispatchEvent");

                const clear_values_button = selectOrThrow(
                    selection_container,
                    ".list-picker-selected-value-remove-button",
                );
                clear_values_button.dispatchEvent(new Event("pointerdown"));

                expectItemToBeSelected(item_none);
                expectItemNotToBeSelected(item_1);
                expectItemNotToBeSelected(item_2);

                expect(selection_container.querySelectorAll(".list-picker-badge")).toHaveLength(1);
                expect(openListPicker).toHaveBeenCalled();
                expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 2);
            });

            it(`should unselect "None" value if an other value is selected`, () => {
                manager.processSelection(item_none.element);
                const dispatch = vi.spyOn(source_select_box, "dispatchEvent");

                expectItemToBeSelected(item_none);
                expectItemNotToBeSelected(item_1);
                expectItemNotToBeSelected(item_2);

                manager.processSelection(item_2.element);

                expectItemNotToBeSelected(item_none);
                expectItemNotToBeSelected(item_1);
                expectItemToBeSelected(item_2);
                expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 1);
            });

            it(`should unselect previously selected values value if "None" value is selected`, () => {
                manager.processSelection(item_1.element);
                manager.processSelection(item_2.element);
                const dispatch = vi.spyOn(source_select_box, "dispatchEvent");

                expectItemNotToBeSelected(item_none);
                expectItemToBeSelected(item_1);
                expectItemToBeSelected(item_2);

                manager.processSelection(item_none.element);

                expectItemToBeSelected(item_none);
                expectItemNotToBeSelected(item_1);
                expectItemNotToBeSelected(item_2);
                expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 1);
            });
        });

        describe(`without "none" value`, () => {
            describe(`when I click on the X button in the badge of a selected item`, () => {
                let x_button: HTMLElement;

                beforeEach(() => {
                    manager_without_none.processSelection(item_1.element);
                    x_button = selectOrThrow(
                        selection_container,
                        ".list-picker-badge[title='Value 1'] > .list-picker-value-remove-button",
                    );
                });

                it(`then the item should be unselected and the placeholder should be shown`, () => {
                    const dispatch = vi.spyOn(source_select_box, "dispatchEvent");

                    x_button.dispatchEvent(new Event("pointerup"));

                    expectItemNotToBeSelected(item_1);
                    expect(search_input.getAttribute("placeholder")).toBe(
                        "Please select some values",
                    );
                    expect(openListPicker).toHaveBeenCalled();
                    expect(
                        selection_container.querySelector(
                            ".list-picker-badge[title='Value 1'] > .list-picker-value-remove-button",
                        ),
                    ).toBeNull();
                    expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 1);
                });

                it(`and when there is still another value selected
                then the item should be unselected`, () => {
                    manager_without_none.processSelection(item_2.element);
                    const dispatch = vi.spyOn(source_select_box, "dispatchEvent");

                    x_button.dispatchEvent(new Event("pointerup"));

                    expectItemNotToBeSelected(item_1);
                    expectItemToBeSelected(item_2);
                    expect(openListPicker).toHaveBeenCalled();
                    expect(
                        selection_container.querySelector(
                            ".list-picker-badge[title='Value 1'] > .list-picker-value-remove-button",
                        ),
                    ).toBeNull();
                    expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 1);
                });

                it(`When the 'remove all value' button is clicked,
                then all values should be unselected and placeholder displayed`, () => {
                    manager_without_none.processSelection(item_2.element);
                    const dispatch = vi.spyOn(source_select_box, "dispatchEvent");

                    const clear_values_button = selectOrThrow(
                        selection_container,
                        ".list-picker-selected-value-remove-button",
                    );
                    clear_values_button.dispatchEvent(new Event("pointerdown"));

                    expectItemNotToBeSelected(item_1);
                    expectItemNotToBeSelected(item_2);

                    expect(search_input.getAttribute("placeholder")).toBe(
                        "Please select some values",
                    );
                    expect(
                        selection_container.querySelector(
                            ".list-picker-selected-value-remove-button",
                        ),
                    ).toBeNull();

                    expect(openListPicker).toHaveBeenCalled();
                    expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 1);
                });
            });
        });

        it("should not unselect the item if the source <select> is disabled", () => {
            manager.processSelection(item_1.element);
            const x_button = selectOrThrow(
                selection_container,
                ".list-picker-badge[title='Value 1'] > .list-picker-value-remove-button",
            );
            source_select_box.setAttribute("disabled", "disabled");

            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            x_button.dispatchEvent(new Event("click"));

            expectItemToBeSelected(item_1);
            expect(openListPicker).not.toHaveBeenCalled();
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 0);
        });
    });

    describe("handleBackSpaceKey", () => {
        it("should remove the last selected item and set the value of the search input with its template", () => {
            manager.processSelection(item_1.element);

            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            const backspace_down_event = new KeyboardEvent("keydown");
            manager.handleBackspaceKey(backspace_down_event);

            expectItemNotToBeSelected(item_1);
            expect(search_input.value).toBe(item_1.label);
            expect(backspace_down_event.cancelBubble).toBe(true);
            expect(
                selection_container.querySelector(".list-picker-selected-value-remove-button"),
            ).toBeNull();
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 1);
        });

        it("should let the user delete the content of the search input", () => {
            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            const backspace_down_event = new KeyboardEvent("keydown");
            search_input.value = item_1.label;

            manager.handleBackspaceKey(backspace_down_event);

            expect(backspace_down_event.cancelBubble).toBe(false);
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 0);
        });

        it("when no item is selected and the user deletes the last letter of the input content, then it should put the placeholder back", () => {
            const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
            const backspace_down_event = new KeyboardEvent("keydown");
            search_input.value = "V";

            manager.handleBackspaceKey(backspace_down_event);

            expect(backspace_down_event.cancelBubble).toBe(false);
            expect(search_input.getAttribute("placeholder")).toBe("Please select some values");
            expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 0);
        });
    });

    describe("resetAfterChangeInOptions", () => {
        it.each([
            [
                `no item has been selected, and there is no option in the select anymore`,
                (): void => {
                    source_select_box.innerHTML = "";
                },
            ],
            [
                `items were selected but there is no option in the select anymore`,
                (): void => {
                    manager.processSelection(item_1.element);
                    manager.processSelection(item_2.element);
                    source_select_box.innerHTML = "";
                },
            ],
            [
                `no item has been selected, and there are new options but none of them is selected`,
                (): void => {
                    const new_option_0 = doc.createElement("option");
                    new_option_0.value = "new option 0";
                    const new_option_1 = doc.createElement("option");
                    new_option_1.value = "new option 1";
                    source_select_box.replaceChildren(new_option_0, new_option_1);
                    source_select_box.value = "";
                },
            ],
            [
                `items were selected, but don't exist in the new options,
                and no new option is selected`,
                (): void => {
                    manager.processSelection(item_1.element);
                    manager.processSelection(item_2.element);
                    const new_option_0 = doc.createElement("option");
                    new_option_0.value = "new option 0";
                    const new_option_1 = doc.createElement("option");
                    new_option_1.value = "new option 1";
                    source_select_box.replaceChildren(new_option_0, new_option_1);
                    source_select_box.value = "";
                },
            ],
        ])(
            `when %s, then it should display the placeholder and remove the [remove all values] button`,
            (_conditions_description, setup) => {
                setup();
                const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
                item_map_manager.refreshItemsMap();
                manager.resetAfterChangeInOptions();

                expect(
                    selection_container.querySelector(".list-picker-selected-value-remove-button"),
                ).toBeNull();
                expect(search_input.getAttribute("placeholder")).toBe("Please select some values");
                expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 0);
            },
        );

        it.each([
            [
                `no item has been selected`,
                `it should mark the new options as selected`,
                (): void => {
                    // No setup
                },
            ],
            [
                `items were selected`,
                `it should mark the new options as selected`,
                (): void => {
                    manager.processSelection(item_1.element);
                    manager.processSelection(item_2.element);
                },
            ],
            [
                `an item was selected and is still selected in the new options`,
                `it should keep it selected`,
                (): void => {
                    manager.processSelection(item_1.element);
                },
            ],
        ])(
            `when %s, and there are new options including some that are selected,
            then %s`,
            (_conditions_description, _expectation_description, setup) => {
                setup();
                const new_option_1 = doc.createElement("option");
                new_option_1.value = item_1.value;
                new_option_1.selected = true;
                const new_option_2 = doc.createElement("option");
                new_option_2.value = "new option 2";
                const new_option_3 = doc.createElement("option");
                new_option_3.value = "new option 3";
                new_option_3.selected = true;
                source_select_box.replaceChildren(new_option_1, new_option_2, new_option_3);

                const dispatch = vi.spyOn(source_select_box, "dispatchEvent");
                item_map_manager.refreshItemsMap();
                manager.resetAfterChangeInOptions();

                const new_item_with_item_1_value = item_map_manager.getItemWithValue(item_1.value);
                if (!new_item_with_item_1_value) {
                    throw Error(
                        "an item matching item_1's value should have been found in the items map",
                    );
                }
                expectItemToBeSelected(new_item_with_item_1_value);
                const new_item_with_item_2_value = item_map_manager.getItemWithValue(item_2.value);
                expect(new_item_with_item_2_value).toBeNull();
                expectItemNotToBeSelected(item_2);
                const new_item_3 = item_map_manager.getItemWithValue("new option 3");
                if (!new_item_3) {
                    throw Error("Expected to find an item for option 3");
                }
                expectItemToBeSelected(new_item_3);
                expectChangeEventToHaveBeenFiredOnSourceSelectBox(dispatch, 0);
            },
        );
    });
});
