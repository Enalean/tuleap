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

import { getNextItem, getPreviousItem } from "./list-item-finder";

describe("list-item-finder", () => {
    /* Group 1
     * - Item 1
     * - Item 2 [disabled]
     * - Item 3
     * Group 2
     * - Item 4 [disabled]
     * - Item 5
     */
    function generateList(): {
        item_1: Element;
        item_3: Element;
        item_5: Element;
    } {
        const list = document.createElement("ul");
        const group_1 = document.createElement("li");
        const group_1_items = document.createElement("ul");
        group_1.setAttribute("class", "list-picker-item-group");
        group_1.appendChild(group_1_items);
        const group_2 = document.createElement("li");
        const group_2_items = document.createElement("ul");
        group_2.setAttribute("class", "list-picker-item-group");
        group_2.appendChild(group_2_items);
        list.appendChild(group_1);
        list.appendChild(group_2);

        const item_1 = document.createElement("li");
        item_1.setAttribute("class", "list-picker-dropdown-option-value");
        const item_2 = document.createElement("li");
        item_2.setAttribute("class", "list-picker-dropdown-option-value-disabled");
        const item_3 = document.createElement("li");
        item_3.setAttribute("class", "list-picker-dropdown-option-value");
        const item_4 = document.createElement("li");
        item_4.setAttribute("class", "list-picker-dropdown-option-value-disabled");
        const item_5 = document.createElement("li");
        item_5.setAttribute("class", "list-picker-dropdown-option-value");

        group_1_items.appendChild(item_1);
        group_1_items.appendChild(item_2);
        group_1_items.appendChild(item_3);
        group_2_items.appendChild(item_4);
        group_2_items.appendChild(item_5);

        return {
            item_1,
            item_3,
            item_5,
        };
    }

    it("should return the next valid item in the list", () => {
        const { item_1, item_3, item_5 } = generateList();

        expect(getNextItem(item_1)).toEqual(item_3);
        expect(getNextItem(item_3)).toEqual(item_5);
        expect(getNextItem(item_5)).toBeNull();
    });

    it("should return the previous valid item in the list", () => {
        const { item_1, item_3, item_5 } = generateList();

        expect(getPreviousItem(item_5)).toEqual(item_3);
        expect(getPreviousItem(item_3)).toEqual(item_5);
        expect(getPreviousItem(item_1)).toBeNull();
    });
});
