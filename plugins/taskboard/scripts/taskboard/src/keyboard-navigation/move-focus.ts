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

import type { ArrowKey } from "../type";
import { DOWN, LEFT, RIGHT, UP } from "../type";

import {
    getElementDown,
    getElementUp,
    getElementRight,
    getElementLeft,
} from "./get-element-from-direction";

export function moveFocus(doc: Document, direction: ArrowKey): void {
    const current_element = getCurrentNavigationElement(doc);
    if (!current_element) {
        return;
    }

    let target_element: HTMLElement | null = null;
    if (direction === DOWN) {
        target_element = getElementDown(doc, current_element);
    }

    if (direction === UP) {
        target_element = getElementUp(doc, current_element);
    }

    if (direction === RIGHT) {
        target_element = getElementRight(doc, current_element);
    }

    if (direction === LEFT) {
        target_element = getElementLeft(doc, current_element);
    }

    if (target_element instanceof HTMLElement) {
        target_element.focus();
    }
}

function getCurrentNavigationElement(doc: Document): HTMLElement | null {
    const current_element = doc.activeElement;
    if (!(current_element instanceof HTMLElement) || !current_element.dataset.navigation) {
        return null;
    }
    return current_element;
}
