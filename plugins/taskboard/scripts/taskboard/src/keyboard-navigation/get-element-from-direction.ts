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

import { CARD, SWIMLANE, ADDFORM, CELL } from "../type";
import type { NavigationElement } from "../type";
import {
    getNextElement,
    getPreviousElement,
    getFirstElement,
    getLastElement,
    getParent,
    getAddForm,
    getFirstChildElement,
} from "./element-getter";

export const getElementDown = (doc: Document, current_element: HTMLElement): HTMLElement | null =>
    getDownOrUpElement(doc, current_element, getNextElement, getFirstElement);

export const getElementUp = (doc: Document, current_element: HTMLElement): HTMLElement | null =>
    getDownOrUpElement(doc, current_element, getPreviousElement, getLastElement);

export const getElementRight = (doc: Document, current_element: HTMLElement): HTMLElement | null =>
    getRightOrLeftElement(doc, current_element, getNextElement, getFirstElement);

export const getElementLeft = (doc: Document, current_element: HTMLElement): HTMLElement | null =>
    getRightOrLeftElement(doc, current_element, getPreviousElement, getLastElement);

type GetTargetElement = (element: HTMLElement) => HTMLElement | null;
type GetFallbackElement = (
    element: HTMLElement | Document,
    navigation_type: NavigationElement,
) => HTMLElement | null;

function getDownOrUpElement(
    doc: Document,
    current_element: HTMLElement,
    getTargetElement: GetTargetElement,
    getFallbackElement: GetFallbackElement,
): HTMLElement | null {
    const navigation_datatype = current_element.dataset.navigation;

    if (navigation_datatype === SWIMLANE) {
        const target_element = getTargetElement(current_element);
        return target_element ?? getFallbackElement(doc, SWIMLANE);
    }

    const cell = getParent(current_element, CELL);

    if (navigation_datatype === ADDFORM) {
        return getFallbackElement(cell, CARD);
    }

    if (navigation_datatype === CARD) {
        const target_element = getTargetElement(current_element);
        if (target_element) {
            return target_element;
        }

        const add_form = getAddForm(cell);
        return add_form ?? getFallbackElement(cell, CARD);
    }

    throw new Error("Navigation datatype is incorrect");
}

function getRightOrLeftElement(
    doc: Document,
    current_element: HTMLElement,
    getTargetElement: GetTargetElement,
    getFallbackElement: GetFallbackElement,
): HTMLElement | null {
    const navigation_datatype = current_element.dataset.navigation;
    if (navigation_datatype !== CARD && navigation_datatype !== ADDFORM) {
        return null;
    }

    let cell = getParent(current_element, CELL);
    let target_cell: HTMLElement | null;
    let target_cell_first_child: HTMLElement | null;

    do {
        target_cell = getTargetElement(cell);
        if (!target_cell) {
            const swimlane = getParent(cell, SWIMLANE);
            target_cell = getFallbackElement(swimlane, CELL);

            if (!target_cell) {
                throw new Error("Swimlane should have at least one cell");
            }
        }
        target_cell_first_child = getFirstChildElement(target_cell);
        cell = target_cell;
    } while (!target_cell_first_child);

    return target_cell_first_child;
}
