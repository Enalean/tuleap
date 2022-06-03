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
import type { DropdownContentRenderer } from "../renderers/DropdownContentRenderer";
import type { ManageSelection } from "../selection/SelectionManager";
import type { KeyboardNavigationManager } from "../navigation/KeyboardNavigationManager";
import type { FieldFocusManager } from "../navigation/FieldFocusManager";
import type { ClearSearchField } from "./SearchFieldClearer";
import type { ListItemHighlighter } from "../navigation/ListItemHighlighter";
import {
    isArrowDown,
    isArrowUp,
    isEnterKey,
    isEscapeKey,
    isShiftKey,
    isTabKey,
} from "../helpers/keys-helper";

export class EventManager {
    private escape_key_handler!: (event: KeyboardEvent) => void;
    private click_outside_handler!: (event: Event) => void;
    private keyboard_events_handler!: (event: KeyboardEvent) => void;
    private prevent_form_submit_on_enter_handler!: (event: KeyboardEvent) => void;
    private has_keyboard_selection_occurred = false;

    constructor(
        private readonly doc: HTMLDocument,
        private readonly wrapper_element: Element,
        private readonly link_selector_element: Element,
        private readonly dropdown_element: Element,
        private readonly search_field_element: HTMLInputElement,
        private readonly source_select_box: HTMLSelectElement,
        private readonly selection_manager: ManageSelection,
        private readonly dropdown_manager: ManageDropdown,
        private readonly dropdown_content_renderer: DropdownContentRenderer,
        private readonly keyboard_navigation_manager: KeyboardNavigationManager,
        private readonly list_item_highlighter: ListItemHighlighter,
        private readonly field_focus_manager: FieldFocusManager,
        private readonly search_field_clearer: ClearSearchField
    ) {}

    public attachEvents(): void {
        if (this.source_select_box.disabled) {
            return;
        }

        this.attachOpenCloseEvent();
        this.attachItemListEvent();
        this.attachSourceSelectBoxChangeEvent();
        this.escape_key_handler = this.attachEscapeKeyPressedEvent();
        this.click_outside_handler = this.attachClickOutsideEvent();
        this.keyboard_events_handler = this.attachKeyboardNavigationEvents();
        this.prevent_form_submit_on_enter_handler = this.preventEnterKeyInSearchFieldToSubmitForm();
        this.attachSearchEvent(this.search_field_element);
    }

    public removeEventsListenersOnDocument(): void {
        this.doc.removeEventListener("keyup", this.escape_key_handler);
        this.doc.removeEventListener("pointerdown", this.click_outside_handler);
        this.doc.removeEventListener("keydown", this.keyboard_events_handler);
        this.doc.removeEventListener("keypress", this.prevent_form_submit_on_enter_handler);
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
        this.doc.addEventListener("pointerdown", handler);

        return handler;
    }

    private attachOpenCloseEvent(): void {
        this.link_selector_element.addEventListener("pointerdown", (event: Event) => {
            event.preventDefault();
            if (
                event.target instanceof Element &&
                this.isElementOnWhichClickShouldNotCloseListPicker(event.target)
            ) {
                return;
            }

            if (
                event.target instanceof Element &&
                event.target.classList.contains("link-selector-search-field")
            ) {
                this.dropdown_manager.openLinkSelector();
                return;
            }

            if (this.dropdown_manager.isDropdownOpen()) {
                this.resetHighlight();
                this.dropdown_manager.closeLinkSelector();
            } else {
                this.list_item_highlighter.resetHighlight();
                this.dropdown_manager.openLinkSelector();
            }
        });
    }

    private isElementOnWhichClickShouldNotCloseListPicker(element: Element): boolean {
        return (
            element.classList.contains("link-selector-dropdown-option-value-disabled") ||
            element.classList.contains("link-selector-group-label") ||
            element.classList.contains("link-selector-item-group")
        );
    }

    public attachItemListEvent(): void {
        const items = this.dropdown_element.querySelectorAll(
            ".link-selector-dropdown-option-value"
        );
        let mouse_target_id: string | null = null;

        items.forEach((item) => {
            item.addEventListener("pointerup", () => {
                this.selection_manager.processSelection(item);
                this.resetHighlight();
                this.dropdown_manager.closeLinkSelector();
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

    private attachSearchEvent(search_field_element: HTMLInputElement): void {
        search_field_element.addEventListener("keyup", (event: KeyboardEvent) => {
            if (isArrowUp(event) || isArrowDown(event) || isTabKey(event) || isShiftKey(event)) {
                return;
            }

            if (isEnterKey(event)) {
                if (this.has_keyboard_selection_occurred) {
                    this.has_keyboard_selection_occurred = false;
                } else {
                    this.list_item_highlighter.resetHighlight();
                    this.dropdown_manager.openLinkSelector();
                }
                return;
            }

            this.dropdown_manager.openLinkSelector();
        });

        search_field_element.addEventListener("keydown", (event: KeyboardEvent) => {
            if (isTabKey(event)) {
                this.resetHighlight();
            }
        });
    }

    private handleClicksOutsideListPicker(event: Event): void {
        const target_element = event.target;

        if (
            !(target_element instanceof Element) ||
            (!this.wrapper_element.contains(target_element) &&
                !this.dropdown_element.contains(target_element))
        ) {
            this.resetHighlight();
            this.has_keyboard_selection_occurred = false;
            this.dropdown_manager.closeLinkSelector();
            this.clearSearchFieldIfNeeded();
        }
    }

    private clearSearchFieldIfNeeded(): void {
        if (!this.dropdown_manager.isDropdownOpen() && this.selection_manager.hasSelection()) {
            return;
        }
        this.search_field_clearer.clearSearchField();
    }

    private resetHighlight(): void {
        if (!this.dropdown_manager.isDropdownOpen()) {
            return;
        }

        this.list_item_highlighter.resetHighlight();
    }

    private handleEscapeKey(event: KeyboardEvent): void {
        if (isEscapeKey(event)) {
            this.resetHighlight();
            this.dropdown_manager.closeLinkSelector();
            this.has_keyboard_selection_occurred = false;
            event.stopPropagation();
        }
    }

    private attachSourceSelectBoxChangeEvent(): void {
        this.source_select_box.addEventListener("change", () => {
            const is_valid = this.source_select_box.checkValidity();
            if (!is_valid) {
                this.wrapper_element.classList.add("link-selector-error");
            } else {
                this.wrapper_element.classList.remove("link-selector-error");
            }
        });
    }

    private attachKeyboardNavigationEvents(): (event: KeyboardEvent) => void {
        const handler = (event: KeyboardEvent): void => {
            const is_dropdown_open = this.dropdown_manager.isDropdownOpen();
            if (isTabKey(event) && is_dropdown_open) {
                this.dropdown_manager.closeLinkSelector();
                return;
            }

            if (
                !is_dropdown_open &&
                isEnterKey(event) &&
                this.field_focus_manager.doesSelectionElementHaveTheFocus()
            ) {
                this.list_item_highlighter.resetHighlight();
                this.dropdown_manager.openLinkSelector();
                this.has_keyboard_selection_occurred = false;
                return;
            }

            if (!(event instanceof KeyboardEvent) || !is_dropdown_open) {
                return;
            }

            const highlighted_item = this.list_item_highlighter.getHighlightedItem();
            if (isEnterKey(event) && highlighted_item) {
                this.selection_manager.processSelection(highlighted_item);
                this.resetHighlight();
                this.dropdown_manager.closeLinkSelector();
                this.has_keyboard_selection_occurred = true;
            } else {
                this.keyboard_navigation_manager.navigate(event);
            }
        };
        this.doc.addEventListener("keydown", handler);
        return handler;
    }

    private preventEnterKeyInSearchFieldToSubmitForm(): (event: KeyboardEvent) => void {
        const handler = (event: KeyboardEvent): void => {
            if (
                event.target &&
                event.target instanceof HTMLElement &&
                event.target.classList.contains("link-selector-search-field") &&
                isEnterKey(event)
            ) {
                event.preventDefault();
            }
        };
        this.doc.addEventListener("keypress", handler);
        return handler;
    }
}
