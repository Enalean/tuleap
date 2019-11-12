/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { Card, RemainingEffort, Swimlane } from "../../../type";
import { SwimlaneState } from "../type";
import { NewRemainingEffortPayload } from "./type";

export function startSavingRemainingEffort(state: SwimlaneState, card: Card): void {
    const state_card = findCard(state, card);
    if (state_card.remaining_effort) {
        state_card.remaining_effort.is_being_saved = true;
    }
}
export function resetSavingRemainingEffort(state: SwimlaneState, card: Card): void {
    const state_card = findCard(state, card);
    if (state_card.remaining_effort) {
        switchRemainingEffortToReadOnlyMode(state_card.remaining_effort);
    }
}

export function finishSavingRemainingEffort(
    state: SwimlaneState,
    payload: NewRemainingEffortPayload
): void {
    const state_card = findCard(state, payload.card);
    if (state_card.remaining_effort) {
        state_card.remaining_effort.value = payload.value;
        switchRemainingEffortToReadOnlyMode(state_card.remaining_effort);
    }
}

function switchRemainingEffortToReadOnlyMode(remaining_effort: RemainingEffort): void {
    remaining_effort.is_being_saved = false;
    remaining_effort.is_in_edit_mode = false;
}

function findCard(state: SwimlaneState, card: Card): Card {
    // We only search for parent card because this is the only one that can have its remaining effort updated
    // If one day we need to find a child (eg: edit the title) then we will have to search against
    // children cards as well.
    const swimlane: Swimlane | undefined = state.swimlanes.find(
        swimlane => swimlane.card.id === card.id
    );
    if (!swimlane) {
        throw new Error("Could not find card with id=" + card.id);
    }

    return swimlane.card;
}
