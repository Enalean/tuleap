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

import type { SwimlaneState } from "./type";
import type { Card, ColumnDefinition, Swimlane } from "../../type";
import {
    isStatusAcceptedByColumn,
    getColumnOfCard,
} from "../../helpers/list-value-to-column-mapper";
import type { RootState } from "../type";
import { findSwimlane } from "./swimlane-helpers";

export * from "./card/card-getters";

export const cards_in_cell =
    (state: SwimlaneState, getters: [], root_state: RootState) =>
    (current_swimlane: Swimlane, current_column: ColumnDefinition): Card[] => {
        const swimlane_in_state = findSwimlane(state, current_swimlane);

        return swimlane_in_state.children_cards.filter((card: Card) => {
            const column_of_card = getColumnOfCard(root_state.column.columns, card);

            if (!column_of_card) {
                return false;
            }

            return column_of_card.id === current_column.id;
        });
    };

export const is_there_at_least_one_children_to_display =
    (state: SwimlaneState, getters: [], root_state: RootState) =>
    (current_swimlane: Swimlane): boolean => {
        if (!current_swimlane.card.has_children) {
            return false;
        }

        return current_swimlane.children_cards.some((card: Card): boolean => {
            const column_of_card = getColumnOfCard(root_state.column.columns, card);

            return column_of_card !== undefined;
        });
    };

export const has_at_least_one_card_in_edit_mode = (state: SwimlaneState): boolean => {
    return state.swimlanes.some(doesSwimlaneContainACardInEditMode);
};

export const is_loading_cards = (state: SwimlaneState): boolean => {
    return (
        state.is_loading_swimlanes ||
        state.swimlanes.some((swimlane) => swimlane.is_loading_children_cards)
    );
};

export const nb_cards_in_column =
    (state: SwimlaneState) =>
    (column: ColumnDefinition): number => {
        return state.swimlanes.reduce(
            (sum: number, swimlane) => nbCardsInColumnForSwimlane(swimlane, column) + sum,
            0,
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

function doesSwimlaneContainACardInEditMode(swimlane: Swimlane): boolean {
    return isCardInEditMode(swimlane.card) || swimlane.children_cards.some(isCardInEditMode);
}

function isCardInEditMode(card: Card): boolean {
    if (card.is_in_edit_mode) {
        return true;
    }

    if (!card.remaining_effort) {
        return false;
    }

    return card.remaining_effort.is_in_edit_mode;
}

export function taskboard_cell_swimlane_header_classes(
    state: SwimlaneState,
    getters: [],
    root_state: RootState,
): string[] {
    const fullscreen_class = root_state.fullscreen.is_taskboard_in_fullscreen_mode
        ? "taskboard-fullscreen"
        : "";

    if (is_a_parent_card_in_edit_mode(state)) {
        return [fullscreen_class, "taskboard-cell-swimlane-header-edit-mode"];
    }

    return [fullscreen_class];
}

export function is_a_parent_card_in_edit_mode(state: SwimlaneState): boolean {
    return state.swimlanes.some((swimlane) => swimlane.card.is_in_edit_mode);
}
