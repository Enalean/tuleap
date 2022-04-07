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

import { ListItemHighlighter } from "./ListItemHighlighter";

describe("ListItemHighlighter", () => {
    let highlighter: ListItemHighlighter, item_1: Element, item_2: Element, item_3: Element;

    beforeEach(() => {
        const dropdown_list_element = document.createElement("ul");
        item_1 = document.createElement("li");
        item_1.setAttribute("class", "list-picker-dropdown-option-value");
        item_2 = document.createElement("li");
        item_2.setAttribute("class", "list-picker-dropdown-option-value");
        item_3 = document.createElement("li");
        item_3.setAttribute("class", "list-picker-dropdown-option-value");

        dropdown_list_element.appendChild(item_1);
        dropdown_list_element.appendChild(item_2);
        dropdown_list_element.appendChild(item_3);

        highlighter = new ListItemHighlighter(dropdown_list_element);
    });

    describe("resetHighlight", () => {
        it("when there are selected items, then it should highlight the first one", () => {
            item_1.setAttribute("aria-selected", "true");
            item_2.setAttribute("aria-selected", "true");

            highlighter.resetHighlight();

            expect(item_1.classList).toContain("list-picker-item-highlighted");
            expect(item_2.classList).not.toContain("list-picker-item-highlighted");
        });

        it("when there are no selected items, then it highlights the first item in the list", () => {
            highlighter.resetHighlight();

            expect(item_1.classList).toContain("list-picker-item-highlighted");
            expect(item_2.classList).not.toContain("list-picker-item-highlighted");
            expect(item_3.classList).not.toContain("list-picker-item-highlighted");
        });
    });

    describe("highlightItem", () => {
        it("should remove the highlight on highlighted items and highlight the requested one", () => {
            highlighter.highlightItem(item_2);

            expect(item_1.classList).not.toContain("list-picker-item-highlighted");
            expect(item_2.classList).toContain("list-picker-item-highlighted");
            expect(item_3.classList).not.toContain("list-picker-item-highlighted");

            highlighter.highlightItem(item_3);

            expect(item_1.classList).not.toContain("list-picker-item-highlighted");
            expect(item_2.classList).not.toContain("list-picker-item-highlighted");
            expect(item_3.classList).toContain("list-picker-item-highlighted");
        });
    });
});
