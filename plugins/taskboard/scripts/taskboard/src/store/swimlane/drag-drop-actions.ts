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
import { SwimlaneState, HandleDropPayload, ReorderCardsPayload } from "./type";
import { hasCardBeenDroppedInTheSameCell, getCardFromSwimlane } from "../../helpers/html-to-item";
import { getCardPosition } from "../../helpers/cards-reordering";

export async function handleDrop(
    context: ActionContext<SwimlaneState, RootState>,
    payload: HandleDropPayload
): Promise<void> {
    if (!hasCardBeenDroppedInTheSameCell(payload.target_cell, payload.source_cell)) {
        return;
    }

    const { swimlane, column } = context.getters.column_and_swimlane_of_cell(payload.target_cell);
    if (!swimlane || !column) {
        return;
    }

    const card = getCardFromSwimlane(swimlane, payload.dropped_card);
    if (!card) {
        return;
    }

    const sibling = getCardFromSwimlane(swimlane, payload.sibling_card);
    const position = getCardPosition(
        card,
        sibling,
        context.getters.cards_in_cell(swimlane, column)
    );

    const reoder_payload: ReorderCardsPayload = {
        swimlane,
        column,
        position
    };
    await context.dispatch("reorderCardsInCell", reoder_payload);
}
