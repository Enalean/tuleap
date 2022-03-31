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

export class ListItemHighlighter {
    constructor(private readonly dropdown_list_element: Element) {}

    public resetHighlight(): void {
        const selected_item = this.getSelectedItem();
        if (selected_item) {
            this.highlightItem(selected_item);
            return;
        }

        const first_item = this.dropdown_list_element.querySelector(
            ".link-selector-dropdown-option-value"
        );

        if (!first_item) {
            return;
        }

        this.highlightItem(first_item);
    }

    public getHighlightedItem(): Element | null {
        return this.dropdown_list_element.querySelector(".link-selector-item-highlighted");
    }

    public highlightItem(item_to_highlight: Element): void {
        this.removeHighlights();
        item_to_highlight.classList.add("link-selector-item-highlighted");
    }

    private removeHighlights(): void {
        this.dropdown_list_element
            .querySelectorAll(".link-selector-item-highlighted")
            .forEach((item) => item.classList.remove("link-selector-item-highlighted"));
    }

    private getSelectedItem(): Element | null {
        return this.dropdown_list_element.querySelector("[aria-selected=true]");
    }
}
