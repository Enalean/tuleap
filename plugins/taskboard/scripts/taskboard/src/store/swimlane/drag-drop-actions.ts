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

import { ActionContext } from "vuex";
import { RootState } from "../type";
import { SwimlaneState, HandleDropPayload, ReorderCardsPayload, MoveCardsPayload } from "./type";
import {
    isDraggedOverTheSourceCell,
    isDraggedOverAnotherSwimlane,
    getCardFromSwimlane
} from "../../helpers/html-to-item";
import { getCardPosition } from "../../helpers/cards-reordering";
import { ColumnDefinition, Swimlane, Card } from "../../type";
import { isSoloCard } from "./swimlane-helpers";

export function handleDrop(
    context: ActionContext<SwimlaneState, RootState>,
    payload: HandleDropPayload
): Promise<void> {
    context.commit("unsetDropZoneRejectingDrop");

    if (isDraggedOverAnotherSwimlane(payload.target_cell, payload.source_cell)) {
        return Promise.resolve();
    }

    const { swimlane, column } = context.rootGetters.column_and_swimlane_of_cell(
        payload.target_cell
    );
    if (!swimlane || !column) {
        return Promise.resolve();
    }

    const card = isSoloCard(swimlane)
        ? swimlane.card
        : getCardFromSwimlane(swimlane, payload.dropped_card);

    if (!card) {
        return Promise.resolve();
    }

    const sibling = getCardFromSwimlane(swimlane, payload.sibling_card);
    const cards_in_cell = context.getters.cards_in_cell(swimlane, column);

    if (isDraggedOverTheSourceCell(payload.target_cell, payload.source_cell)) {
        if (isSoloCard(swimlane) || cards_in_cell.length <= 1) {
            return Promise.resolve();
        }

        const reoder_payload = getReorderCardsPayload(
            card,
            sibling,
            cards_in_cell,
            swimlane,
            column
        );
        return context.dispatch("reorderCardsInCell", reoder_payload);
    }

    const move_payload = getMoveCardsPayload(card, sibling, cards_in_cell, swimlane, column);
    return context.dispatch("moveCardToCell", move_payload);
}

function getReorderCardsPayload(
    card: Card,
    sibling: Card | null,
    cards_in_cell: Card[],
    swimlane: Swimlane,
    column: ColumnDefinition
): ReorderCardsPayload {
    const position = getCardPosition(card, sibling, cards_in_cell);

    const reoder_payload: ReorderCardsPayload = {
        swimlane,
        column,
        position
    };
    return reoder_payload;
}

function getMoveCardsPayload(
    card: Card,
    sibling: Card | null,
    cards_in_cell: Card[],
    swimlane: Swimlane,
    column: ColumnDefinition
): MoveCardsPayload {
    let position;

    if (cards_in_cell.length > 0) {
        position = getCardPosition(card, sibling, cards_in_cell);
    }

    return {
        card,
        column,
        swimlane,
        position
    };
}
