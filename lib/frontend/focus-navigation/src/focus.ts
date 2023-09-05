/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

type Direction = "up" | "down";
type Queryable = Pick<ParentNode, "querySelector" | "querySelectorAll">;
type GetParent = (child: HTMLElement) => Queryable | null;

/**
 * Cycles up or down the navigable elements before or after the currently focused element of the given document,
 * limited to children given by `getParent` function.
 *
 * "navigable elements" are HTMLElements that are descendants of the element given by `getParent` function
 * and that have the same value for the [data-navigation] attribute.
 *
 * When direction is "down", it focuses the next navigable element. If the current focus is the last navigable element,
 * it cycles back to the first navigable element.
 * When direction is "up", it focuses the previous navigable element. If the current focus is the first navigable element,
 * it cycles back to the last navigable element.
 *
 * This function does not register keyboard event handlers, it is up to the caller.
 */
export function moveFocus(doc: Document, direction: Direction, getParent: GetParent): void {
    const current_element = doc.activeElement;
    if (!(current_element instanceof HTMLElement)) {
        return;
    }
    const next_element = cycleUpOrDown(current_element, direction, getParent);
    if (next_element) {
        next_element.focus();
    }
}

const getHTMLElement = (element: Element): HTMLElement | null =>
    element instanceof HTMLElement ? element : null;

function cycleUpOrDown(
    current_element: HTMLElement,
    direction: Direction,
    getParent: GetParent,
): HTMLElement | null {
    const parent = getParent(current_element);
    if (!parent) {
        return null;
    }
    const navigation_marker = current_element.dataset.navigation;
    if (!navigation_marker) {
        return null;
    }
    const navigable_elements = parent.querySelectorAll(`[data-navigation=${navigation_marker}]`);
    if (navigable_elements.length === 1) {
        return null;
    }
    const current_index = Array.from(navigable_elements).indexOf(current_element);
    if (direction === "down") {
        if (current_index === navigable_elements.length - 1) {
            return getHTMLElement(navigable_elements[0]);
        }
        return getHTMLElement(navigable_elements[current_index + 1]);
    }
    if (current_index === 0) {
        return getHTMLElement(navigable_elements[navigable_elements.length - 1]);
    }
    return getHTMLElement(navigable_elements[current_index - 1]);
}
