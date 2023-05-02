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

import { isEscapeKey } from "../helpers/keys-helper";
import type { DropdownElement } from "../dropdown/DropdownElement";

export class EventManager {
    private escape_key_handler!: (event: KeyboardEvent) => void;
    private click_outside_handler!: (event: Event) => void;

    constructor(
        private readonly doc: Document,
        private readonly wrapper_element: Element,
        private readonly lazybox_element: Element,
        private readonly dropdown: DropdownElement & HTMLElement,
        private readonly source_select_box: HTMLSelectElement
    ) {}

    public attachEvents(): void {
        if (this.source_select_box.disabled) {
            return;
        }

        this.attachOpenCloseEvent();
        this.escape_key_handler = this.attachEscapeKeyPressedEvent();
        this.click_outside_handler = this.attachClickOutsideEvent();
    }

    public removeEventsListenersOnDocument(): void {
        this.doc.removeEventListener("keyup", this.escape_key_handler);
        this.doc.removeEventListener("pointerup", this.click_outside_handler);
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
            if (
                event.target instanceof Element &&
                this.isElementOnWhichClickShouldNotCloseListPicker(event.target)
            ) {
                return;
            }
            event.stopPropagation();
            this.dropdown.open = !this.dropdown.open;
        });
    }

    private isElementOnWhichClickShouldNotCloseListPicker(element: Element): boolean {
        return (
            element.classList.contains("lazybox-dropdown-option-value-disabled") ||
            element.classList.contains("lazybox-group-label") ||
            element.classList.contains("lazybox-item-group")
        );
    }

    private handleClicksOutsideListPicker(event: Event): void {
        const target_element = event.target;

        if (
            !(target_element instanceof Element) ||
            (!this.wrapper_element.contains(target_element) &&
                !this.dropdown.contains(target_element))
        ) {
            this.dropdown.open = false;
        }
    }

    private handleEscapeKey(event: KeyboardEvent): void {
        if (isEscapeKey(event)) {
            this.dropdown.open = false;
            event.stopPropagation();
        }
    }
}
