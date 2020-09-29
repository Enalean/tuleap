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
import { DropdownToggler } from "./DropdownToggler";
import { SelectionManager } from "./SelectionManager";
import { DropdownContentRenderer } from "./DropdownContentRenderer";

export class EventManager {
    constructor(
        private readonly doc: HTMLDocument,
        private readonly wrapper_element: Element,
        private readonly dropdown_element: Element,
        private readonly search_field_element: HTMLInputElement | null,
        private readonly source_select_box: HTMLSelectElement,
        private readonly selection_manager: SelectionManager,
        private readonly dropdown_toggler: DropdownToggler,
        private readonly dropdown_content_renderer: DropdownContentRenderer
    ) {}

    public attachEvents(): void {
        this.attachClickEvent();
        this.attachEscapeKeyPressedEvent();
        this.attachItemListEvent();
        if (this.search_field_element !== null) {
            this.attachSearchEvent(this.search_field_element);
        }
    }

    private attachEscapeKeyPressedEvent(): void {
        this.doc.addEventListener("keyup", (event: Event): void => {
            if (
                event instanceof KeyboardEvent &&
                (event.key === "Escape" || event.key === "Esc" || event.keyCode === 27)
            ) {
                this.dropdown_toggler.closeListPicker();
            }
        });
    }

    private attachClickEvent(): void {
        this.wrapper_element.addEventListener("click", (event: Event) => {
            if (this.source_select_box.disabled) {
                return;
            }
            if (
                event.target instanceof Element &&
                this.isElementOnWhichClickShouldNotCloseListPicker(event.target)
            ) {
                return;
            }

            if (this.dropdown_element.classList.contains("list-picker-dropdown-shown")) {
                this.dropdown_toggler.closeListPicker();
            } else {
                this.dropdown_toggler.openListPicker();
            }
        });

        this.doc.addEventListener("click", (event: Event): void => {
            const target_element = event.target;

            if (!(target_element instanceof Element)) {
                return this.dropdown_toggler.closeListPicker();
            }

            if (!this.wrapper_element.contains(target_element)) {
                return this.dropdown_toggler.closeListPicker();
            }
        });
    }

    private isElementOnWhichClickShouldNotCloseListPicker(element: Element): boolean {
        return (
            element.classList.contains("list-picker-dropdown-option-value-disabled") ||
            element.classList.contains("list-picker-group-label") ||
            element.classList.contains("list-picker-item-group") ||
            element.classList.contains("list-picker-search-field") ||
            element.classList.contains("list-picker-dropdown-search-section")
        );
    }

    private attachItemListEvent(): void {
        const items = this.dropdown_element.querySelectorAll(".list-picker-dropdown-option-value");
        items.forEach((item) => {
            item.addEventListener("click", () => {
                this.selection_manager.processSingleSelection(item);
            });
        });
    }

    private attachSearchEvent(search_field_element: HTMLInputElement): void {
        search_field_element.addEventListener("keyup", () => {
            const filter_query = search_field_element.value;

            this.dropdown_content_renderer.renderFilteredListPickerDropdownContent(filter_query);
        });
    }
}
