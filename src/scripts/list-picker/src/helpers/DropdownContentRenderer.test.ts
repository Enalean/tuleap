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

import {
    appendGroupedOptionsToSourceSelectBox,
    appendSimpleOptionsToSourceSelectBox,
} from "../test-helpers/select-box-options-generator";
import { DropdownContentRenderer } from "./DropdownContentRenderer";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";
import { generateItemMapBasedOnSourceSelectOptions } from "./static-list-helper";
import { GetText } from "../../../tuleap/gettext/gettext-init";

describe("dropdown-content-renderer", () => {
    let select: HTMLSelectElement,
        dropdown: Element,
        dropdown_list: Element,
        gettext_provider: GetText;

    beforeEach(() => {
        select = document.createElement("select");
    });

    describe("without search input", () => {
        beforeEach(() => {
            const { dropdown_element, dropdown_list_element } = new BaseComponentRenderer(
                select
            ).renderBaseComponent();

            dropdown = dropdown_element;
            dropdown_list = dropdown_list_element;

            gettext_provider = {
                gettext: (english: string) => english,
            } as GetText;
        });

        it("renders grouped list items", () => {
            appendGroupedOptionsToSourceSelectBox(select);

            new DropdownContentRenderer(
                select,
                dropdown_list,
                generateItemMapBasedOnSourceSelectOptions(select),
                gettext_provider
            ).renderListPickerDropdownContent();

            expect(dropdown.innerHTML).toMatchSnapshot();
        });

        it("renders simple list items", () => {
            appendSimpleOptionsToSourceSelectBox(select);
            new DropdownContentRenderer(
                select,
                dropdown_list,
                generateItemMapBasedOnSourceSelectOptions(select),
                gettext_provider
            ).renderListPickerDropdownContent();

            expect(dropdown.innerHTML).toMatchSnapshot();
        });

        it("when the source option is disabled, then the list item should be disabled", () => {
            const disabled_option = document.createElement("option");
            disabled_option.setAttribute("disabled", "disabled");
            disabled_option.setAttribute("value", "You can't select me");

            select.appendChild(disabled_option);

            new DropdownContentRenderer(
                select,
                dropdown_list,
                generateItemMapBasedOnSourceSelectOptions(select),
                gettext_provider
            ).renderListPickerDropdownContent();

            const disabled_list_item = dropdown.querySelector(
                ".list-picker-dropdown-option-value-disabled"
            );

            expect(disabled_list_item).not.toBeNull();
        });
    });

    describe("with search input", () => {
        beforeEach(() => {
            const { dropdown_element, dropdown_list_element } = new BaseComponentRenderer(select, {
                is_filterable: true,
            }).renderBaseComponent();

            dropdown = dropdown_element;
            dropdown_list = dropdown_list_element;
        });

        it("renders only items matching the query", () => {
            appendSimpleOptionsToSourceSelectBox(select);
            const renderer = new DropdownContentRenderer(
                select,
                dropdown_list,
                generateItemMapBasedOnSourceSelectOptions(select),
                gettext_provider
            );

            renderer.renderListPickerDropdownContent();
            renderer.renderFilteredListPickerDropdownContent("1");

            expect(dropdown_list.childElementCount).toEqual(1);

            if (!dropdown_list.firstElementChild) {
                throw new Error("List should not be empty, it should contains the item 'Value 1'");
            }
            expect(dropdown_list.firstElementChild.textContent).toEqual("Value 1");
        });

        it("renders an empty state if no items are matching the query", () => {
            appendSimpleOptionsToSourceSelectBox(select);
            const renderer = new DropdownContentRenderer(
                select,
                dropdown_list,
                generateItemMapBasedOnSourceSelectOptions(select),
                gettext_provider
            );

            renderer.renderListPickerDropdownContent();
            renderer.renderFilteredListPickerDropdownContent("This query will match no item");

            expect(dropdown_list.querySelector(".list-picker-dropdown-option-value")).toBeNull();

            const empty_state = dropdown_list.querySelector(".list-picker-empty-dropdown-state");
            if (!empty_state) {
                throw new Error("Empty state not found");
            }
        });

        it("renders groups containing matching items", () => {
            appendGroupedOptionsToSourceSelectBox(select);

            const renderer = new DropdownContentRenderer(
                select,
                dropdown_list,
                generateItemMapBasedOnSourceSelectOptions(select),
                gettext_provider
            );

            renderer.renderListPickerDropdownContent();
            renderer.renderFilteredListPickerDropdownContent("Value 1");

            const items = dropdown_list.querySelectorAll(".list-picker-dropdown-option-value");
            const groups = dropdown_list.querySelectorAll(".list-picker-item-group");

            if (items.length === 0 || groups.length === 0) {
                throw new Error("Item or group not found in the filtered list");
            }

            expect(items.length).toEqual(1);
            expect(groups.length).toEqual(1);

            const group = groups[0];
            const item = items[0];

            expect(group.textContent).toContain("Group 1");
            expect(group.contains(item)).toBe(true);
            expect(item.textContent).toEqual("Value 1");
        });
    });
});
