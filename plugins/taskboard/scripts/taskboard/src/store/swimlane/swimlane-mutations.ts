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

import { Card, Direction, Swimlane } from "../../type";
import { AddChildrenToSwimlanePayload, SwimlaneState, ReorderCardsPayload } from "./type";
import { findSwimlane, replaceSwimlane } from "./swimlane-helpers";

export * from "./card/card-mutations";

function sortCardsByRank(a: Card, b: Card): number {
    return Math.sign(a.rank - b.rank);
}

function sortSwimlanesByRank(a: Swimlane, b: Swimlane): number {
    return sortCardsByRank(a.card, b.card);
}

export function addSwimlanes(state: SwimlaneState, swimlanes: Array<Swimlane>): void {
    state.swimlanes = state.swimlanes.concat(swimlanes);
    state.swimlanes.sort(sortSwimlanesByRank);
}

export function beginLoadingSwimlanes(state: SwimlaneState): void {
    state.is_loading_swimlanes = true;
}

export function endLoadingSwimlanes(state: SwimlaneState): void {
    state.is_loading_swimlanes = false;
}

export function addChildrenToSwimlane(
    state: SwimlaneState,
    payload: AddChildrenToSwimlanePayload
): void {
    const state_swimlane = findSwimlane(state, payload.swimlane);
    const new_swimlane: Swimlane = {
        ...state_swimlane,
        children_cards: state_swimlane.children_cards.concat(payload.children_cards)
    };
    new_swimlane.children_cards.sort(sortCardsByRank);
    replaceSwimlane(state, new_swimlane);
    state.swimlanes.sort(sortSwimlanesByRank);
}

export function beginLoadingChildren(state: SwimlaneState, swimlane: Swimlane): void {
    const state_swimlane = findSwimlane(state, swimlane);
    state_swimlane.is_loading_children_cards = true;
}

export function endLoadingChildren(state: SwimlaneState, swimlane: Swimlane): void {
    const state_swimlane = findSwimlane(state, swimlane);
    state_swimlane.is_loading_children_cards = false;
}

export function collapseSwimlane(state: SwimlaneState, swimlane: Swimlane): void {
    swimlane.card.is_collapsed = true;
}

export function expandSwimlane(state: SwimlaneState, swimlane: Swimlane): void {
    swimlane.card.is_collapsed = false;
}

export function changeCardPosition(state: SwimlaneState, payload: ReorderCardsPayload): void {
    const card_id = payload.position.ids[0];
    const card_index = payload.swimlane.children_cards.findIndex(child => child.id === card_id);
    const state_swimlane = findSwimlane(state, payload.swimlane);
    const card_to_move = state_swimlane.children_cards[card_index];

    state_swimlane.children_cards.splice(card_index, 1);

    const sibling_index = payload.swimlane.children_cards.findIndex(
        child => child.id === payload.position.compared_to
    );

    if (card_index === -1 || sibling_index === -1) {
        return;
    }

    const offset = payload.position.direction === Direction.AFTER ? 1 : 0;
    state_swimlane.children_cards.splice(sibling_index + offset, 0, card_to_move);
}
