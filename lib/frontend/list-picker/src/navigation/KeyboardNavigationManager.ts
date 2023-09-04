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

import type { ListItemHighlighter } from "./ListItemHighlighter";
import { getNextItem, getPreviousItem } from "../helpers/list-item-finder";
import { isArrowDown, isArrowUp } from "../helpers/keys-helper";

export class KeyboardNavigationManager {
    constructor(
        private readonly dropdown_list_element: Element,
        private readonly list_item_highlighter: ListItemHighlighter,
    ) {}

    public navigate(event: KeyboardEvent): void {
        const has_been_processed = this.handleKey(event);
        if (has_been_processed) {
            // Only prevent when the event has been processed, so we won't block search fields.
            event.stopPropagation();
            // Prevent page to scroll when browsing the items
            event.preventDefault();
        }
    }

    private handleKey(event: KeyboardEvent): boolean {
        const highlighted_item = this.list_item_highlighter.getHighlightedItem();
        if (!highlighted_item) {
            return false;
        }

        if (isArrowUp(event)) {
            this.highlightPreviousItem(highlighted_item);
            return true;
        } else if (isArrowDown(event)) {
            this.highlightNextItem(highlighted_item);
            return true;
        }

        return false;
    }

    private highlightNextItem(highlighted_item: Element): void {
        const next_item = getNextItem(highlighted_item);
        if (next_item === null) {
            return;
        }

        this.list_item_highlighter.highlightItem(next_item);
        this.scrollToItemIfNeeded(next_item);
    }

    private highlightPreviousItem(highlighted_item: Element): void {
        const previous_item = getPreviousItem(highlighted_item);
        if (previous_item === null) {
            return;
        }

        this.list_item_highlighter.highlightItem(previous_item);
        this.scrollToItemIfNeeded(previous_item);
    }

    private scrollToItemIfNeeded(item: Element): void {
        if (
            !(item instanceof HTMLElement) ||
            !(this.dropdown_list_element instanceof HTMLElement)
        ) {
            return;
        }

        const item_bottom = item.getBoundingClientRect().bottom;
        const list_bottom = this.dropdown_list_element.getBoundingClientRect().bottom;
        if (item_bottom > list_bottom) {
            const offset = item_bottom - list_bottom;
            this.dropdown_list_element.scrollTop += offset;
            return;
        }

        const item_top = item.getBoundingClientRect().top;
        const list_top = this.dropdown_list_element.getBoundingClientRect().top;
        if (item_top < list_top) {
            const new_top = item.offsetTop;
            const list_top = this.dropdown_list_element.offsetTop;

            const closest_group = item.closest(".list-picker-item-group");
            if (
                item.previousElementSibling === null &&
                closest_group instanceof HTMLElement &&
                closest_group.previousElementSibling === null
            ) {
                // we have reached the first item of the first group
                this.dropdown_list_element.scrollTop = 0;
                return;
            }
            this.dropdown_list_element.scrollTop = new_top - list_top;
        }
    }
}
