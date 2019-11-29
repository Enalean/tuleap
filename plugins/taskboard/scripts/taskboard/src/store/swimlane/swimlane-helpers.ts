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

import { SwimlaneState } from "./type";
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

/**
 * @throws Error
 */
export function findCard(state: SwimlaneState, card: Card): Card {
    for (const swimlane of state.swimlanes) {
        if (swimlane.card.id === card.id) {
            return swimlane.card;
        }

        for (const child of swimlane.children_cards) {
            if (child.id === card.id) {
                return child;
            }
        }
    }

    throw new Error("Could not find card with id=" + card.id);
}

export function isSoloCard(swimlane: Swimlane): boolean {
    return swimlane.card.has_children === false;
}
