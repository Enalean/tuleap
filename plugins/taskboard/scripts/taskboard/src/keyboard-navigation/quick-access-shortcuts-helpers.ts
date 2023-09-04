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
import { getFirstElement } from "./element-getter";
import type { ShortcutHandleOptions } from "@tuleap/keyboard-shortcuts";

export const DO_NOT_PREVENT_DEFAULT: ShortcutHandleOptions = { preventDefault: false };

export function editRemainingEffort(event: KeyboardEvent): ShortcutHandleOptions | void {
    if (!(event.target instanceof HTMLElement)) {
        return DO_NOT_PREVENT_DEFAULT;
    }

    const parent_swimlane = event.target.closest(`[data-navigation=${SWIMLANE}]`);
    if (!parent_swimlane) {
        return DO_NOT_PREVENT_DEFAULT;
    }

    const remaining_effort = parent_swimlane.querySelector(`[data-shortcut=edit-remaining-effort]`);
    if (!(remaining_effort instanceof HTMLElement)) {
        return DO_NOT_PREVENT_DEFAULT;
    }

    remaining_effort.click();
}

export function toggleClosedItems(doc: Document): void {
    const toggle_button = doc.querySelector(`[data-shortcut=toggle-closed-items]:not(:checked)`);
    if (!(toggle_button instanceof HTMLElement)) {
        throw new Error("Unchecked toggle button could not be found");
    }
    toggle_button.click();
}

export function returnToParent(event: KeyboardEvent): ShortcutHandleOptions | void {
    const active_element = event.target;
    if (!(active_element instanceof HTMLElement)) {
        return DO_NOT_PREVENT_DEFAULT;
    }

    const parent_swimlane = active_element.closest(`[data-navigation=${SWIMLANE}]`);
    if (!(parent_swimlane instanceof HTMLElement)) {
        return DO_NOT_PREVENT_DEFAULT;
    }

    const parent_card = parent_swimlane.querySelector("[data-shortcut=parent-card]");

    if (active_element === parent_card) {
        return parent_swimlane.focus();
    }

    if (active_element.dataset.navigation && parent_card instanceof HTMLElement) {
        return parent_card.focus();
    }

    const card = active_element.closest(`[data-navigation=${CARD}]`);
    if (card instanceof HTMLElement) {
        return card.focus();
    }

    return DO_NOT_PREVENT_DEFAULT;
}

export function editCard(event: KeyboardEvent): ShortcutHandleOptions | void {
    if (!(event.target instanceof HTMLElement) || !(event.target.dataset.navigation === CARD)) {
        return DO_NOT_PREVENT_DEFAULT;
    }

    const child = event.target.querySelector(`[data-shortcut=edit-card]`);
    if (child instanceof HTMLElement) {
        return child.click();
    }

    return DO_NOT_PREVENT_DEFAULT;
}

export function handleFocusFirstSwimlane(
    doc: Document,
    event: KeyboardEvent,
): ShortcutHandleOptions | void {
    const first_swimlane = getFirstElement(doc, SWIMLANE);
    if (first_swimlane instanceof HTMLElement) {
        first_swimlane.focus();
    }

    const is_first_swimlane = first_swimlane === event.target;
    if (is_first_swimlane) {
        return DO_NOT_PREVENT_DEFAULT;
    }
}

export function focusSwimlaneFirstCard(event: KeyboardEvent): ShortcutHandleOptions | void {
    const swimlane = event.target;
    if (!(swimlane instanceof HTMLElement) || swimlane.dataset.navigation !== SWIMLANE) {
        return DO_NOT_PREVENT_DEFAULT;
    }

    const first_swimlane_card = getFirstElement(swimlane, CARD);
    if (!(first_swimlane_card instanceof HTMLElement)) {
        throw new Error("Swimlane shoud have at least one card");
    }

    return first_swimlane_card.focus();
}
