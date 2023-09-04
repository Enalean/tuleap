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

import { patch } from "@tuleap/tlp-fetch";
import type { ActionContext } from "vuex";
import type { RootState } from "../type";
import type {
    HandleDropPayload,
    MoveCardsPayload,
    ReorderCardsPayload,
    SwimlaneState,
} from "./type";
import {
    getCardFromSwimlane,
    isDraggedOverAnotherSwimlane,
    isDraggedOverTheSourceCell,
} from "../../helpers/html-to-item";
import { getCardPosition } from "../../helpers/cards-reordering";
import type { Card, ColumnDefinition, Swimlane } from "../../type";

export function handleDrop(
    context: ActionContext<SwimlaneState, RootState>,
    payload: HandleDropPayload,
): Promise<void> {
    context.commit("unsetDropZoneRejectingDrop");

    if (isDraggedOverAnotherSwimlane(payload.target_cell, payload.source_cell)) {
        return Promise.resolve();
    }

    const { swimlane, column } = context.rootGetters.column_and_swimlane_of_cell(
        payload.target_cell,
    );
    if (!swimlane || !column) {
        return Promise.resolve();
    }

    const is_solo_card = !context.getters.is_there_at_least_one_children_to_display(swimlane);
    const card = is_solo_card ? swimlane.card : getCardFromSwimlane(swimlane, payload.dropped_card);

    if (!card) {
        return Promise.resolve();
    }

    const sibling = getCardFromSwimlane(swimlane, payload.sibling_card);
    const cards_in_cell = context.getters.cards_in_cell(swimlane, column);

    if (isDraggedOverTheSourceCell(payload.target_cell, payload.source_cell)) {
        if (is_solo_card || cards_in_cell.length <= 1) {
            return Promise.resolve();
        }

        const reoder_payload = getReorderCardsPayload(
            card,
            sibling,
            cards_in_cell,
            swimlane,
            column,
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
    column: ColumnDefinition,
): ReorderCardsPayload {
    const position = getCardPosition(card, sibling, cards_in_cell);

    const reoder_payload: ReorderCardsPayload = {
        swimlane,
        column,
        position,
    };
    return reoder_payload;
}

function getMoveCardsPayload(
    card: Card,
    sibling: Card | null,
    cards_in_cell: Card[],
    swimlane: Swimlane,
    column: ColumnDefinition,
): MoveCardsPayload {
    let position;

    if (cards_in_cell.length > 0) {
        position = getCardPosition(card, sibling, cards_in_cell);
    }

    return {
        card,
        column,
        swimlane,
        position,
    };
}

export async function reorderCardsInCell(
    context: ActionContext<SwimlaneState, RootState>,
    payload: ReorderCardsPayload,
): Promise<void> {
    context.commit("changeCardPosition", payload);
    try {
        const url = getPATCHCellUrl(payload.swimlane, payload.column);
        await patch(url, {
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ order: payload.position }),
        });
    } catch (error) {
        await context.dispatch("error/handleModalError", error, { root: true });
    }
}

export async function moveCardToCell(
    context: ActionContext<SwimlaneState, RootState>,
    payload: MoveCardsPayload,
): Promise<void> {
    context.commit("moveCardToColumn", payload);
    try {
        const url = getPATCHCellUrl(payload.swimlane, payload.column);
        const body = getMoveCardBody(payload);
        await patch(url, {
            headers: { "Content-Type": "application/json" },
            body,
        });
        await context.dispatch("refreshCardAndParent", {
            swimlane: payload.swimlane,
            card: payload.card,
        });
    } catch (error) {
        await context.dispatch("error/handleModalError", error, { root: true });
    }
}

function getMoveCardBody(payload: MoveCardsPayload): string {
    const body = {
        add: payload.card.id,
    };

    if (payload.position) {
        Object.assign(body, { order: payload.position });
    }

    return JSON.stringify(body);
}

function getPATCHCellUrl(swimlane: Swimlane, column: ColumnDefinition): string {
    const swimlane_id = swimlane.card.id;
    const column_id = column.id;

    return `/api/v1/taskboard_cells/${encodeURIComponent(swimlane_id)}/column/${encodeURIComponent(
        column_id,
    )}`;
}
