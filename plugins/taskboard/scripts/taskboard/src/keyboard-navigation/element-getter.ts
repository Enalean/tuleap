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

import { ADDFORM, CARD } from "../type";
import type { NavigationElement } from "../type";

export function getNextElement(element: HTMLElement): HTMLElement | null {
    const next_element = element.nextElementSibling;
    if (!(next_element instanceof HTMLElement)) {
        return null;
    }

    return next_element.dataset.navigation === element.dataset.navigation ? next_element : null;
}

export function getPreviousElement(element: HTMLElement): HTMLElement | null {
    const previous_element = element.previousElementSibling;
    if (!(previous_element instanceof HTMLElement)) {
        return null;
    }

    return previous_element.dataset.navigation === element.dataset.navigation
        ? previous_element
        : null;
}

export function getFirstElement(
    parent: Document | HTMLElement,
    element_navigation_datatype: NavigationElement,
): HTMLElement | null {
    const first_element = parent.querySelector(`[data-navigation=${element_navigation_datatype}]`);
    return first_element instanceof HTMLElement ? first_element : null;
}

export function getLastElement(
    parent: Document | HTMLElement,
    element_navigation_datatype: NavigationElement,
): HTMLElement | null {
    const elements = parent.querySelectorAll(`[data-navigation=${element_navigation_datatype}]`);
    if (elements.length === 0) {
        return null;
    }
    const last_element = elements[elements.length - 1];
    return last_element instanceof HTMLElement ? last_element : null;
}

export function getFirstChildElement(parent: HTMLElement): HTMLElement | null {
    const first_child_element = parent.querySelector(
        `[data-navigation=${CARD}], [data-navigation=${ADDFORM}]`,
    );
    return first_child_element instanceof HTMLElement ? first_child_element : null;
}

export function getParent(
    child: HTMLElement,
    parent_navigation_datatype: NavigationElement,
): HTMLElement {
    const parent_element = child.closest(`[data-navigation=${parent_navigation_datatype}]`);
    if (!(parent_element instanceof HTMLElement)) {
        throw new Error("current element should have a parent element");
    }
    return parent_element;
}

export function getAddForm(parent: HTMLElement): HTMLElement | null {
    const add_form = parent.querySelector(`[data-navigation=${ADDFORM}]`);
    return add_form instanceof HTMLElement ? add_form : null;
}
