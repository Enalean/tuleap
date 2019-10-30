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

import { SwimlaneState } from "./type";
import { Card, ColumnDefinition, Swimlane } from "../../type";
import { isStatusAcceptedByColumn } from "../../helpers/list-value-to-column-mapper";

export const is_loading_cards = (state: SwimlaneState): boolean => {
    return (
        state.is_loading_swimlanes ||
        state.swimlanes.some(swimlane => swimlane.is_loading_children_cards)
    );
};

export const nb_cards_in_column = (state: SwimlaneState) => (column: ColumnDefinition): number => {
    return state.swimlanes.reduce(
        (sum: number, swimlane) => nbCardsInColumnForSwimlane(swimlane, column) + sum,
        0
    );
};

function nbCardsInColumnForSwimlane(swimlane: Swimlane, column: ColumnDefinition): number {
    if (!swimlane.card.has_children) {
        return isStatusAcceptedByColumn(swimlane.card, column) ? 1 : 0;
    }

    return swimlane.children_cards.reduce((sum: number, card: Card) => {
        if (!isStatusAcceptedByColumn(card, column)) {
            return sum;
        }

        return sum + 1;
    }, 0);
}
