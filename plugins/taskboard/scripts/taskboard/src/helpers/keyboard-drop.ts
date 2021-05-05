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
import { LEFT, RIGHT } from "../type";
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
    direction: ArrowKey
): SuccessfulDropCallbackParameter | null {
    const card = getDraggedCard(doc, state);
    if (!card) {
        return null;
    }

    const cell = card.closest("[data-navigation='cell']");
    if (!(cell instanceof HTMLElement)) {
        throw new Error("Dragged card should have a parent cell");
    }

    const target_cell = getTargetCell(cell, direction);
    if (!target_cell) {
        return null;
    }

    return {
        dropped_element: card,
        source_dropzone: cell,
        target_dropzone: target_cell,
        next_sibling: null,
    };
}

export function getTargetCell(cell: HTMLElement, direction: ArrowKey): HTMLElement | null {
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

export function getDraggedCard(doc: Document, state: State): HTMLElement | null {
    if (!state.card_being_dragged) {
        return null;
    }

    const dragged_card = doc.querySelector(`[data-card-id="${state.card_being_dragged.card_id}"]`);
    return dragged_card instanceof HTMLElement ? dragged_card : null;
}
