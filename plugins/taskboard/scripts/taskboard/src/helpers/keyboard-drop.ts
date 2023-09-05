/*
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

import type { State } from "../store/type";
import type { ArrowKey } from "../type";
import { LEFT, RIGHT, DOWN, UP, CELL, CARD } from "../type";
import type { SuccessfulDropCallbackParameter } from "@tuleap/drag-and-drop";
import { isContainer } from "./drag-drop";

export function focusDraggedCard(doc: Document, state: State): void {
    const dragged_card = getDraggedCard(doc, state);
    if (!dragged_card) {
        return;
    }
    dragged_card.focus();
}

export function getContext(
    doc: Document,
    state: State,
    direction: ArrowKey,
): SuccessfulDropCallbackParameter | null {
    const card = getDraggedCard(doc, state);
    if (!card) {
        return null;
    }

    const cell = card.closest(`[data-navigation=${CELL}]`);
    if (!(cell instanceof HTMLElement)) {
        throw new Error("Dragged card should have a parent cell");
    }

    const target_cell = getTargetCell(cell, direction);
    if (!target_cell) {
        return null;
    }

    const next_sibling = getNextSiblingAfterMove(card, cell, direction);

    return {
        dropped_element: card,
        source_dropzone: cell,
        target_dropzone: target_cell,
        next_sibling,
    };
}

export function getTargetCell(cell: HTMLElement, direction: ArrowKey): HTMLElement | null {
    if (direction === UP || direction === DOWN) {
        return cell;
    }

    let target_cell;

    if (direction === LEFT) {
        target_cell = cell.previousElementSibling;
    }

    if (direction === RIGHT) {
        target_cell = cell.nextElementSibling;
    }

    if (!(target_cell instanceof HTMLElement) || !isContainer(target_cell)) {
        return null;
    }
    return target_cell;
}

export function getNextSiblingAfterMove(
    card: HTMLElement,
    cell: HTMLElement,
    direction: ArrowKey,
): HTMLElement | null {
    if (direction === LEFT || direction === RIGHT) {
        return null;
    }

    if (direction === UP) {
        const previous_element = card.previousElementSibling;
        if (!(previous_element instanceof HTMLElement) || !isCard(previous_element)) {
            return null;
        }
        return previous_element;
    }

    if (direction === DOWN) {
        return getAfterNextCard(card, cell);
    }

    throw new Error("Incorrect Direction");
}

function getAfterNextCard(card: HTMLElement, cell: HTMLElement): HTMLElement | null {
    const next_element = card.nextElementSibling;

    if (!(next_element instanceof HTMLElement) || !isCard(next_element)) {
        const first_element = cell.firstElementChild;
        if (!(first_element instanceof HTMLElement) || !isCard(first_element)) {
            throw new Error("First element in cell should be a card");
        }
        return first_element;
    }

    const after_next_element = next_element.nextElementSibling;
    if (!(after_next_element instanceof HTMLElement) || !isCard(after_next_element)) {
        return null;
    }
    return after_next_element;
}

function isCard(element: HTMLElement): boolean {
    return element.dataset.navigation === CARD;
}

export function getDraggedCard(doc: Document, state: State): HTMLElement | null {
    if (!state.card_being_dragged) {
        return null;
    }

    const dragged_card = doc.querySelector(`[data-card-id="${state.card_being_dragged.card_id}"]`);
    return dragged_card instanceof HTMLElement ? dragged_card : null;
}
