/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import type { DrekkenovInitOptions } from "./types";
import { GHOST_CSS_CLASS, HIDE_CSS_CLASS } from "./constants";

export function createGhostFromElement(element: HTMLElement): HTMLElement {
    const ghost = document.createElement(element.tagName);
    const { height } = element.getBoundingClientRect();

    ghost.setAttribute("style", `height: ${height}px;`);

    for (const attribute of Array.from(element.attributes)) {
        if (attribute.name === "id") {
            // Two elements in the page cannot share the same id
            continue;
        }

        ghost.setAttribute(attribute.name, attribute.value);
    }

    ghost.classList.add(GHOST_CSS_CLASS);
    ghost.classList.remove(HIDE_CSS_CLASS);

    return ghost;
}

export function findClosestDraggable(
    options: DrekkenovInitOptions,
    element: Node,
): HTMLElement | null {
    let current_element: Node | null = element;
    do {
        if (current_element instanceof HTMLElement && options.isDraggable(current_element)) {
            return current_element;
        }
        current_element = current_element.parentNode;
    } while (current_element !== null);

    return null;
}

export function findClosestDropzone(
    options: DrekkenovInitOptions,
    element: Node,
): HTMLElement | null {
    let current_element: Node | null = element;
    do {
        if (current_element instanceof HTMLElement && options.isDropZone(current_element)) {
            return current_element;
        }
        current_element = current_element.parentNode;
    } while (current_element !== null);

    return null;
}

export function findNextGhostSibling(y_coordinate: number, children: Element[]): Element | null {
    if (children.length === 0) {
        return null;
    }
    for (const child of children) {
        const { top, bottom } = child.getBoundingClientRect();
        const middle = top + (bottom - top) / 2;
        if (middle > y_coordinate) {
            return child;
        }
    }
    return null;
}

export function insertAfter(
    dropzone_element: Element,
    drop_ghost: Element,
    reference_element: Element,
): void {
    dropzone_element.insertBefore(drop_ghost, reference_element.nextElementSibling);
}
