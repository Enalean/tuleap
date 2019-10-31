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
import { getColumnOfCard } from "../../helpers/list-value-to-column-mapper";
import { RootState } from "../type";

export const cards_in_cell = (state: SwimlaneState, getters: [], root_state: RootState) => (
    current_swimlane: Swimlane,
    current_column: ColumnDefinition
): Card[] => {
    return current_swimlane.children_cards.filter((card: Card) => {
        const column_of_card = getColumnOfCard(root_state.columns, card);

        if (!column_of_card) {
            return false;
        }

        return column_of_card.id === current_column.id;
    });
};

export const column_and_swimlane_of_cell = (
    state: SwimlaneState,
    getters: [],
    root_state: RootState
) => (
    cell: HTMLElement
): {
    swimlane?: Swimlane;
    column?: ColumnDefinition;
} => {
    const swimlane = state.swimlanes.find(
        swimlane => swimlane.card.id === Number(cell.dataset.swimlaneId)
    );
    const column = root_state.columns.find(column => column.id === Number(cell.dataset.columnId));

    return {
        swimlane,
        column
    };
};

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
