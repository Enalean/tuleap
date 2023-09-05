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

import type { Card, Mapping, Swimlane } from "../../type";
import { AFTER } from "../../type";
import type {
    AddChildrenToSwimlanePayload,
    MoveCardsPayload,
    RefreshCardMutationPayload,
    ReorderCardsPayload,
    SwimlaneState,
} from "./type";
import { findCard, findSwimlane, replaceSwimlane } from "./swimlane-helpers";

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

export function refreshCard(state: SwimlaneState, payload: RefreshCardMutationPayload): void {
    const state_card = findCard(state, payload.refreshed_card);
    let remaining_effort = null;
    if (state_card.remaining_effort !== null) {
        remaining_effort = Object.assign(
            state_card.remaining_effort,
            payload.refreshed_card.remaining_effort,
        );
    }
    Object.assign(state_card, payload.refreshed_card, { remaining_effort });
}

export function beginLoadingSwimlanes(state: SwimlaneState): void {
    state.is_loading_swimlanes = true;
}

export function endLoadingSwimlanes(state: SwimlaneState): void {
    state.is_loading_swimlanes = false;
}

export function addChildrenToSwimlane(
    state: SwimlaneState,
    payload: AddChildrenToSwimlanePayload,
): void {
    const state_swimlane = findSwimlane(state, payload.swimlane);
    const new_swimlane: Swimlane = {
        ...state_swimlane,
        children_cards: state_swimlane.children_cards.concat(payload.children_cards),
    };
    if (new_swimlane.children_cards.length > 0) {
        new_swimlane.card.has_children = true;
    }
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

function getCardIndex(swimlane: Swimlane, card_id: number): number {
    return swimlane.children_cards.findIndex((child) => child.id === card_id);
}

export function changeCardPosition(state: SwimlaneState, payload: ReorderCardsPayload): void {
    const card_id = payload.position.ids[0];
    const state_swimlane = findSwimlane(state, payload.swimlane);
    const card_index = getCardIndex(state_swimlane, card_id);
    const card_to_move = state_swimlane.children_cards[card_index];

    state_swimlane.children_cards.splice(card_index, 1);

    const sibling_index = getCardIndex(payload.swimlane, payload.position.compared_to);

    if (card_index === -1 || sibling_index === -1) {
        return;
    }

    const offset = payload.position.direction === AFTER ? 1 : 0;
    state_swimlane.children_cards.splice(sibling_index + offset, 0, card_to_move);
}

export function moveCardToColumn(state: SwimlaneState, payload: MoveCardsPayload): void {
    setColumnOfCard(state, payload);

    if (payload.position) {
        const reorder_payload: ReorderCardsPayload = {
            swimlane: payload.swimlane,
            column: payload.column,
            position: payload.position,
        };

        changeCardPosition(state, reorder_payload);
    }
}

export function setColumnOfCard(state: SwimlaneState, payload: MoveCardsPayload): void {
    const mapping = payload.column.mappings.find((mapping) => {
        return mapping.tracker_id === payload.card.tracker_id;
    });

    if (!mapping || mapping.accepts.length === 0) {
        return;
    }

    const id = getFirstListValueId(mapping);
    const card_state = findCard(state, payload.card);

    card_state.mapped_list_value = {
        id,
        label: payload.column.label,
    };
}

function getFirstListValueId(mapping: Mapping): number {
    return mapping.accepts[0].id;
}

export function unsetDropZoneRejectingDrop(state: SwimlaneState): void {
    if (state.dropzone_rejecting_drop) {
        state.dropzone_rejecting_drop.classList.remove("taskboard-drop-not-accepted");
        state.dropzone_rejecting_drop = undefined;
    }
}

function isSameDropZone(dropzone: HTMLElement | undefined, target: HTMLElement): boolean {
    return (
        dropzone !== undefined &&
        dropzone.dataset.swimlaneId === target.dataset.swimlaneId &&
        dropzone.dataset.columnId === target.dataset.columnId
    );
}

export function setDropZoneRejectingDrop(state: SwimlaneState, target: HTMLElement): void {
    const dropzone = state.dropzone_rejecting_drop;

    if (isSameDropZone(dropzone, target)) {
        return;
    }

    target.classList.add("taskboard-drop-not-accepted");

    unsetDropZoneRejectingDrop(state);
    state.dropzone_rejecting_drop = target;
}
