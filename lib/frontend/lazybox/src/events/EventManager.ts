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
import type { ManageDropdown } from "../dropdown/DropdownManager";
import type { ManageSelection } from "../type";
import type { KeyboardNavigationManager } from "../navigation/KeyboardNavigationManager";
import type { ListItemHighlighter } from "../navigation/ListItemHighlighter";
import { isEnterKey, isEscapeKey } from "../helpers/keys-helper";
import type { SearchInput } from "../SearchInput";
import type { KeyboardSelector } from "../selection/KeyboardSelector";

export class EventManager {
    private escape_key_handler!: (event: KeyboardEvent) => void;
    private click_outside_handler!: (event: Event) => void;
    private keyboard_events_handler!: (event: KeyboardEvent) => void;

    constructor(
        private readonly doc: Document,
        private readonly wrapper_element: Element,
        private readonly lazybox_element: Element,
        private readonly dropdown_element: Element,
        private readonly search_field: SearchInput,
        private readonly source_select_box: HTMLSelectElement,
        private readonly selection_manager: ManageSelection,
        private readonly dropdown_manager: ManageDropdown,
        private readonly keyboard_navigation_manager: KeyboardNavigationManager,
        private readonly list_item_highlighter: ListItemHighlighter,
        private readonly keyboard_selector: KeyboardSelector
    ) {}

    public attachEvents(): void {
        if (this.source_select_box.disabled) {
            return;
        }

        this.attachOpenCloseEvent();
        this.attachItemListEvent();
        this.escape_key_handler = this.attachEscapeKeyPressedEvent();
        this.click_outside_handler = this.attachClickOutsideEvent();
        this.keyboard_events_handler = this.attachKeyboardNavigationEvents();
    }

    public removeEventsListenersOnDocument(): void {
        this.doc.removeEventListener("keyup", this.escape_key_handler);
        this.doc.removeEventListener("pointerup", this.click_outside_handler);
        this.doc.removeEventListener("keyup", this.keyboard_events_handler);
    }

    private attachEscapeKeyPressedEvent(): (event: KeyboardEvent) => void {
        const handler = (event: KeyboardEvent): void => {
            this.handleEscapeKey(event);
        };

        this.doc.addEventListener("keyup", handler);

        return handler;
    }

    private attachClickOutsideEvent(): (event: Event) => void {
        const handler = (event: Event): void => {
            this.handleClicksOutsideListPicker(event);
        };
        this.doc.addEventListener("pointerup", handler);

        return handler;
    }

    private attachOpenCloseEvent(): void {
        this.lazybox_element.addEventListener("pointerup", (event: Event) => {
            event.preventDefault();
            if (
                event.target instanceof Element &&
                this.isElementOnWhichClickShouldNotCloseListPicker(event.target)
            ) {
                return;
            }

            if (this.dropdown_manager.isDropdownOpen()) {
                this.dropdown_manager.closeLazybox();
            } else {
                this.dropdown_manager.openLazybox();
            }
        });
    }

    private isElementOnWhichClickShouldNotCloseListPicker(element: Element): boolean {
        return (
            element.classList.contains("lazybox-dropdown-option-value-disabled") ||
            element.classList.contains("lazybox-group-label") ||
            element.classList.contains("lazybox-item-group")
        );
    }

    public attachItemListEvent(): void {
        const items = this.dropdown_element.querySelectorAll(".lazybox-dropdown-option-value");
        let mouse_target_id: string | null = null;

        items.forEach((item) => {
            item.addEventListener("pointerup", () => {
                this.selection_manager.processSelection(item);
                this.dropdown_manager.closeLazybox();
                this.search_field.clear();
            });

            item.addEventListener("pointerenter", () => {
                if (!(item instanceof HTMLElement) || !item.dataset.itemId) {
                    throw new Error("item is not an highlightable item");
                }
                if (mouse_target_id === item.dataset.itemId) {
                    // keyboard navigation occurring, let's not mess things up.
                    return;
                }

                mouse_target_id = item.dataset.itemId;
                this.list_item_highlighter.highlightItem(item);
            });
        });
    }

    private handleClicksOutsideListPicker(event: Event): void {
        const target_element = event.target;

        if (
            !(target_element instanceof Element) ||
            (!this.wrapper_element.contains(target_element) &&
                !this.dropdown_element.contains(target_element))
        ) {
            this.dropdown_manager.closeLazybox();
            if (!this.selection_manager.hasSelection()) {
                this.search_field.clear();
            }
        }
    }

    private handleEscapeKey(event: KeyboardEvent): void {
        if (isEscapeKey(event)) {
            this.dropdown_manager.closeLazybox();
            event.stopPropagation();
        }
    }

    private attachKeyboardNavigationEvents(): (event: KeyboardEvent) => void {
        const handler = (event: KeyboardEvent): void => {
            if (!this.dropdown_manager.isDropdownOpen()) {
                return;
            }
            if (isEnterKey(event)) {
                this.keyboard_selector.handleEnter();
                return;
            }
            this.keyboard_navigation_manager.navigate(event);
        };
        this.doc.addEventListener("keyup", handler);
        return handler;
    }
}
