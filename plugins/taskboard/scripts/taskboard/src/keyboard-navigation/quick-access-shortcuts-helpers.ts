/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import { CARD, SWIMLANE } from "../type";

export function editRemainingEffort(event: KeyboardEvent): void {
    if (!(event.target instanceof HTMLElement)) {
        return;
    }

    const parent_swimlane = event.target.closest(`[data-navigation=${SWIMLANE}]`);
    if (!parent_swimlane) {
        return;
    }

    const remaining_effort = parent_swimlane.querySelector(`[data-shortcut=edit-remaining-effort]`);
    if (remaining_effort instanceof HTMLElement) {
        remaining_effort.click();
    }
}

export function toggleClosedItems(doc: Document): void {
    const toggle_button = doc.querySelector(`[data-shortcut=toggle-closed-items]:not(:checked)`);
    if (!(toggle_button instanceof HTMLElement)) {
        throw new Error("Unchecked toggle button could not be found");
    }
    toggle_button.click();
}

export function returnToParent(event: KeyboardEvent): void {
    const active_element = event.target;
    if (!(active_element instanceof HTMLElement)) {
        return;
    }

    const parent_swimlane = active_element.closest(`[data-navigation=${SWIMLANE}]`);
    if (!(parent_swimlane instanceof HTMLElement)) {
        throw new Error("Active element should have a SWIMLANE parent");
    }

    const parent_card = parent_swimlane.querySelector("[data-shortcut=parent-card]");

    if (active_element === parent_card) {
        parent_swimlane.focus();
        return;
    }

    if (active_element.dataset.navigation && parent_card instanceof HTMLElement) {
        parent_card.focus();
        return;
    }

    const card = active_element.closest(`[data-navigation=${CARD}]`);
    if (card instanceof HTMLElement) {
        card.focus();
    }
}

export function editCard(event: KeyboardEvent): void {
    if (!(event.target instanceof HTMLElement) || !(event.target.dataset.navigation === CARD)) {
        return;
    }
    const child = event.target.querySelector(`[data-shortcut=edit-card]`);
    if (child instanceof HTMLElement) {
        child.click();
    }
}
