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

import { describe, beforeEach, expect, it } from "vitest";
import {
    appendGroupedOptionsToSourceSelectBox,
    appendSimpleOptionsToSourceSelectBox,
} from "../test-helpers/select-box-options-generator";
import { DropdownContentRenderer } from "./DropdownContentRenderer";
import { BaseComponentRenderer } from "./BaseComponentRenderer";
import { ItemsMapManager } from "../items/ItemsMapManager";
import type { GettextProvider } from "@tuleap/gettext";
import { ListItemMapBuilder } from "../items/ListItemMapBuilder";

describe("DropDownContentRenderer", () => {
    let select: HTMLSelectElement,
        dropdown: Element,
        dropdown_list: Element,
        gettext_provider: GettextProvider,
        items_map_manager: ItemsMapManager,
        doc: Document;

    function getDropdownContentRenderer(): DropdownContentRenderer {
        return new DropdownContentRenderer(
            select,
            dropdown_list,
            items_map_manager,
            gettext_provider,
        );
    }

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        select = doc.createElement("select");
        gettext_provider = {
            gettext: (english: string) => english,
        } as GettextProvider;
        items_map_manager = new ItemsMapManager(new ListItemMapBuilder(select));
    });

    describe("without search input", () => {
        beforeEach(() => {
            const { dropdown_element, dropdown_list_element } = new BaseComponentRenderer(
                doc,
                select,
            ).renderBaseComponent();

            dropdown = dropdown_element;
            dropdown_list = dropdown_list_element;
        });

        it("renders grouped list items", async () => {
            appendGroupedOptionsToSourceSelectBox(select);
            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();
            renderer.renderListPickerDropdownContent();

            expect(stripExpressionComments(dropdown.innerHTML)).toMatchSnapshot();
        });

        it("renders simple list items", async () => {
            appendSimpleOptionsToSourceSelectBox(select);
            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();
            renderer.renderListPickerDropdownContent();

            expect(stripExpressionComments(dropdown.innerHTML)).toMatchSnapshot();
        });

        it("when the source option is disabled, then the list item should be disabled", async () => {
            const disabled_option = doc.createElement("option");
            disabled_option.setAttribute("disabled", "disabled");
            disabled_option.setAttribute("value", "You can't select me");

            select.appendChild(disabled_option);

            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();
            renderer.renderListPickerDropdownContent();

            const disabled_list_item = dropdown.querySelector(
                ".list-picker-dropdown-option-value-disabled",
            );

            expect(disabled_list_item).not.toBeNull();
        });
    });

    describe("with search input", () => {
        beforeEach(() => {
            const { dropdown_element, dropdown_list_element } = new BaseComponentRenderer(
                doc,
                select,
                {
                    is_filterable: true,
                },
            ).renderBaseComponent();

            dropdown = dropdown_element;
            dropdown_list = dropdown_list_element;
        });

        it("renders only items matching the query", async () => {
            appendSimpleOptionsToSourceSelectBox(select);
            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();

            renderer.renderListPickerDropdownContent();
            renderer.renderFilteredListPickerDropdownContent("1");

            expect(dropdown_list.childElementCount).toBe(1);

            if (!dropdown_list.firstElementChild) {
                throw new Error("List should not be empty, it should contains the item 'Value 1'");
            }
            expect(dropdown_list.firstElementChild.textContent?.trim()).toBe("Value 1");
        });

        it("renders an empty state if no items are matching the query", async () => {
            appendSimpleOptionsToSourceSelectBox(select);
            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();

            renderer.renderListPickerDropdownContent();
            renderer.renderFilteredListPickerDropdownContent("This query will match no item");

            expect(dropdown_list.querySelector(".list-picker-dropdown-option-value")).toBeNull();

            const empty_state = dropdown_list.querySelector(".list-picker-empty-dropdown-state");
            if (!empty_state) {
                throw new Error("Empty state not found");
            }
        });

        it("renders groups containing matching items", async () => {
            appendGroupedOptionsToSourceSelectBox(select);
            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();

            renderer.renderListPickerDropdownContent();
            renderer.renderFilteredListPickerDropdownContent("Value 1");

            const items = dropdown_list.querySelectorAll(".list-picker-dropdown-option-value");
            const groups = dropdown_list.querySelectorAll(".list-picker-item-group");

            if (items.length === 0 || groups.length === 0) {
                throw new Error("Item or group not found in the filtered list");
            }

            expect(items).toHaveLength(1);
            expect(groups).toHaveLength(1);

            const group = groups[0];
            const item = items[0];

            expect(group.textContent).toContain("Group 1");
            expect(group.contains(item)).toBe(true);
            expect(item.textContent?.trim()).toBe("Value 1");
        });
    });

    describe("renderAfterDependenciesUpdate", () => {
        beforeEach(() => {
            const { dropdown_list_element } = new BaseComponentRenderer(
                doc,
                select,
            ).renderBaseComponent();

            dropdown_list = dropdown_list_element;
        });

        it("should re-render the list", async () => {
            const option_1 = doc.createElement("option");
            option_1.innerText = "Item 1";
            option_1.value = "item_1";
            const option_2 = doc.createElement("option");
            option_2.innerText = "Item 2";
            option_2.value = "item_2";

            select.appendChild(option_1);

            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();
            renderer.renderListPickerDropdownContent();

            const list_item_1 = dropdown_list.querySelector(".list-picker-dropdown-option-value");
            if (!list_item_1) {
                throw new Error("List item not found in the list");
            }
            expect(stripExpressionComments(list_item_1.innerHTML).trim()).toBe("Item 1");

            select.innerHTML = "";
            select.appendChild(option_2);
            await items_map_manager.refreshItemsMap();
            renderer.renderAfterDependenciesUpdate();

            const list_item_2 = dropdown_list.querySelector(".list-picker-dropdown-option-value");
            if (!list_item_2) {
                throw new Error("List item not found in the list");
            }
            expect(stripExpressionComments(list_item_2.innerHTML).trim()).toBe("Item 2");
        });

        it("should render an empty state when the source <select> has no options", async () => {
            const option_1 = doc.createElement("option");
            option_1.innerText = "Item 1";
            option_1.value = "item_1";
            select.appendChild(option_1);

            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();
            renderer.renderListPickerDropdownContent();

            const list_item_1 = dropdown_list.querySelector(".list-picker-dropdown-option-value");
            if (!list_item_1) {
                throw new Error("List item not found in the list");
            }
            expect(stripExpressionComments(list_item_1.innerHTML).trim()).toBe("Item 1");

            select.innerHTML = "";
            await items_map_manager.refreshItemsMap();
            renderer.renderAfterDependenciesUpdate();

            expect(dropdown_list.querySelector("#list-picker-item-item_1")).toBeNull();
            const empty_state = dropdown_list.querySelector(".list-picker-empty-dropdown-state");
            if (!empty_state) {
                throw new Error("Empty state not found");
            }
        });
    });
});

/**
 * See https://github.com/lit/lit/blob/lit%402.0.2/packages/lit-html/src/test/test-utils/strip-markers.ts
 */
function stripExpressionComments(html: string): string {
    return html.replace(/<!--\?lit\$[0-9]+\$-->|<!--\??-->/g, "");
}
