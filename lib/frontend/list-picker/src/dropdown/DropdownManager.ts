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
import type { ScrollingManager } from "../events/ScrollingManager";
import type { FieldFocusManager } from "../navigation/FieldFocusManager";

export class DropdownManager {
    private resize_observer: ResizeObserver;
    private is_dropdown_placed_above: boolean;

    constructor(
        private readonly doc: HTMLDocument,
        private readonly wrapper_element: HTMLElement,
        private readonly list_picker_element: Element,
        private readonly dropdown_element: HTMLElement,
        private readonly dropdown_list_element: Element,
        private readonly selection_element: HTMLElement,
        private readonly scrolling_manager: ScrollingManager,
        private readonly field_focus_manager: FieldFocusManager,
    ) {
        const resize_dropdown_callback = (entries: readonly ResizeObserverEntry[]): void => {
            if (!this.isDropdownOpen()) {
                return;
            }
            let is_list_being_filtered = false;
            if (
                entries.length === 1 &&
                entries[0].target.classList.contains("list-picker-dropdown")
            ) {
                is_list_being_filtered = true;
            }

            this.resizeAndMoveDropdownUnderWrapperElement(is_list_being_filtered);
        };
        this.resize_observer = new ResizeObserver(resize_dropdown_callback);

        this.resize_observer.observe(wrapper_element);
        this.resize_observer.observe(this.doc.body);
        this.resize_observer.observe(dropdown_element);

        this.is_dropdown_placed_above = false;
    }

    public isDropdownOpen(): boolean {
        return this.dropdown_element.classList.contains("list-picker-dropdown-shown");
    }

    public closeListPicker(): void {
        if (!this.isDropdownOpen()) {
            return;
        }

        this.scrolling_manager.unlockScrolling();

        this.dropdown_element.classList.remove("list-picker-dropdown-shown");
        this.dropdown_element.removeAttribute("data-test-list-picker-dropdown-open");
        this.list_picker_element.classList.remove("list-picker-with-open-dropdown");
        this.setAriaExpandedAttribute(this.dropdown_list_element, false);
        this.field_focus_manager.applyFocusOnListPicker();

        if (this.selection_element.hasAttribute("aria-expanded")) {
            this.setAriaExpandedAttribute(this.selection_element, false);
        }
    }

    public openListPicker(): void {
        if (this.isDropdownOpen()) {
            return;
        }

        this.scrolling_manager.lockScrolling();
        this.dropdown_element.classList.add("list-picker-dropdown-shown");
        this.dropdown_element.setAttribute("data-test-list-picker-dropdown-open", "");
        this.list_picker_element.classList.add("list-picker-with-open-dropdown");
        this.resizeAndMoveDropdownUnderWrapperElement(false);
        this.setAriaExpandedAttribute(this.dropdown_list_element, true);

        if (this.selection_element.hasAttribute("aria-expanded")) {
            this.setAriaExpandedAttribute(this.selection_element, true);
        }

        this.field_focus_manager.applyFocusOnSearchField();
    }

    public destroy(): void {
        this.scrolling_manager.unlockScrolling();
        this.resize_observer.disconnect();
    }

    private resizeAndMoveDropdownUnderWrapperElement(is_list_being_filtered: boolean): void {
        const list_picker_boundaries = this.wrapper_element.getBoundingClientRect();
        const x_coordinate = list_picker_boundaries.left + window.scrollX;
        const y_coordinate = list_picker_boundaries.bottom + window.scrollY;
        const { height } = this.dropdown_element.getBoundingClientRect();
        const has_enough_room_below =
            list_picker_boundaries.bottom + height <= this.doc.documentElement.clientHeight;

        this.dropdown_element.style.width = list_picker_boundaries.width + "px";
        this.dropdown_element.style.left = x_coordinate + "px";

        this.dropdown_element.classList.remove("list-picker-dropdown-above");
        this.list_picker_element.classList.remove("list-picker-with-dropdown-above");

        if (!has_enough_room_below || (this.is_dropdown_placed_above && is_list_being_filtered)) {
            const pos = y_coordinate - height - list_picker_boundaries.height;
            this.dropdown_element.style.top = pos + "px";

            this.dropdown_element.classList.add("list-picker-dropdown-above");
            this.list_picker_element.classList.add("list-picker-with-dropdown-above");
            this.is_dropdown_placed_above = true;
            return;
        }

        this.dropdown_element.style.top = Math.ceil(y_coordinate) + "px";
        this.is_dropdown_placed_above = false;
    }

    private setAriaExpandedAttribute(element: Element, is_expanded: boolean): void {
        element.setAttribute("aria-expanded", is_expanded.toString());
    }
}
