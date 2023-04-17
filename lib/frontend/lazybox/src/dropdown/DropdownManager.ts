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
import type { ListItemHighlighter } from "../navigation/ListItemHighlighter";
import type { SearchInput } from "../SearchInput";

export interface ManageDropdown {
    isDropdownOpen: () => boolean;
    closeLazybox: () => void;
    openLazybox: () => void;
}

export class DropdownManager implements ManageDropdown {
    private resize_observer: ResizeObserver;
    private is_dropdown_placed_above: boolean;

    constructor(
        private readonly doc: Document,
        private readonly wrapper_element: HTMLElement,
        private readonly lazybox_element: Element,
        private readonly dropdown_element: HTMLElement,
        private readonly dropdown_list_element: Element,
        private readonly selection_element: HTMLElement,
        private readonly scrolling_manager: ScrollingManager,
        private readonly field_focus_manager: FieldFocusManager,
        private readonly highlighter: ListItemHighlighter,
        private readonly search_field: SearchInput
    ) {
        const resize_dropdown_callback = (entries: readonly ResizeObserverEntry[]): void => {
            if (!this.isDropdownOpen()) {
                return;
            }
            let is_list_being_filtered = false;
            if (entries.length === 1 && entries[0].target.classList.contains("lazybox-dropdown")) {
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
        return this.dropdown_element.classList.contains("lazybox-dropdown-shown");
    }

    public closeLazybox(): void {
        if (!this.isDropdownOpen()) {
            return;
        }

        this.scrolling_manager.unlockScrolling();
        this.highlighter.resetHighlight();

        this.dropdown_element.classList.remove("lazybox-dropdown-shown");
        this.lazybox_element.classList.remove("lazybox-with-open-dropdown");
        this.setAriaExpandedAttribute(this.dropdown_list_element, false);
        this.field_focus_manager.applyFocusOnLazybox();

        if (this.selection_element.hasAttribute("aria-expanded")) {
            this.setAriaExpandedAttribute(this.selection_element, false);
        }
    }

    public openLazybox(): void {
        if (this.isDropdownOpen()) {
            return;
        }

        this.scrolling_manager.lockScrolling();
        this.dropdown_element.classList.add("lazybox-dropdown-shown");
        this.lazybox_element.classList.add("lazybox-with-open-dropdown");
        this.resizeAndMoveDropdownUnderWrapperElement(false);
        this.setAriaExpandedAttribute(this.dropdown_list_element, true);

        if (this.selection_element.hasAttribute("aria-expanded")) {
            this.setAriaExpandedAttribute(this.selection_element, true);
        }

        this.search_field.setFocus();
    }

    public destroy(): void {
        this.scrolling_manager.unlockScrolling();
        this.resize_observer.disconnect();
    }

    private resizeAndMoveDropdownUnderWrapperElement(is_list_being_filtered: boolean): void {
        window.requestAnimationFrame(() => {
            const lazybox_boundaries = this.wrapper_element.getBoundingClientRect();
            const x_coordinate = lazybox_boundaries.left + window.scrollX;
            const y_coordinate = lazybox_boundaries.bottom + window.scrollY;
            const { height } = this.dropdown_element.getBoundingClientRect();
            const has_enough_room_below =
                lazybox_boundaries.bottom + height <= this.doc.documentElement.clientHeight;

            this.dropdown_element.style.width = lazybox_boundaries.width + "px";
            this.dropdown_element.style.left = x_coordinate + "px";

            this.dropdown_element.classList.remove("lazybox-dropdown-above");
            this.lazybox_element.classList.remove("lazybox-with-dropdown-above");

            if (
                !has_enough_room_below ||
                (this.is_dropdown_placed_above && is_list_being_filtered)
            ) {
                const pos = y_coordinate - height - lazybox_boundaries.height;
                this.dropdown_element.style.top = pos + "px";

                this.dropdown_element.classList.add("lazybox-dropdown-above");
                this.lazybox_element.classList.add("lazybox-with-dropdown-above");
                this.is_dropdown_placed_above = true;
                return;
            }

            this.dropdown_element.style.top = Math.ceil(y_coordinate) + "px";
            this.is_dropdown_placed_above = false;
        });
    }

    private setAriaExpandedAttribute(element: Element, is_expanded: boolean): void {
        element.setAttribute("aria-expanded", is_expanded.toString());
    }
}
