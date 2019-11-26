/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { MoveCardsPayload, SwimlaneState } from "./type";
import { Swimlane, Card } from "../../type";

const swimlaneHasSameId = (a: Swimlane) => (b: Swimlane): boolean => a.card.id === b.card.id;

/**
 * @throws Error
 */
export function findSwimlane(state: SwimlaneState, swimlane: Swimlane): Swimlane {
    const state_swimlane = state.swimlanes.find(swimlaneHasSameId(swimlane));
    if (!state_swimlane) {
        throw new Error("Could not find swimlane with id=" + swimlane.card.id);
    }
    return state_swimlane;
}

export const findSwimlaneIndex = (state: SwimlaneState, swimlane: Swimlane): number =>
    state.swimlanes.findIndex(swimlaneHasSameId(swimlane));

export function replaceSwimlane(state: SwimlaneState, swimlane: Swimlane): void {
    const index = findSwimlaneIndex(state, swimlane);
    if (index !== -1) {
        state.swimlanes.splice(index, 1, swimlane);
    }
}

export function findDroppedCard(state: SwimlaneState, payload: MoveCardsPayload): Card {
    const swimlane_state = findSwimlane(state, payload.swimlane);
    const card_state = isSoloCard(swimlane_state)
        ? swimlane_state.card
        : swimlane_state.children_cards.find(child => child.id === payload.card.id);

    if (!card_state) {
        throw new Error("Dropped card has not been found in the swimlane.");
    }

    return card_state;
}

export function isSoloCard(swimlane: Swimlane): boolean {
    return swimlane.card.has_children === false;
}
