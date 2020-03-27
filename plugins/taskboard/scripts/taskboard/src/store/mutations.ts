/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import { State } from "./type";

export function hideClosedItems(state: State): void {
    state.are_closed_items_displayed = false;
}

export function displayClosedItems(state: State): void {
    state.are_closed_items_displayed = true;
}

export function setIdOfCardBeingDragged(state: State, card: Element): void {
    if (!card || !(card instanceof HTMLElement)) {
        return;
    }

    state.card_being_dragged = {
        tracker_id: Number(card.dataset.trackerId),
        card_id: Number(card.dataset.cardId),
    };
}

export function resetIdOfCardBeingDragged(state: State): void {
    state.card_being_dragged = null;
}

export function setIsACellAddingInPlace(state: State): void {
    state.is_a_cell_adding_in_place = true;
}

export function clearIsACellAddingInPlace(state: State): void {
    state.is_a_cell_adding_in_place = false;
}
