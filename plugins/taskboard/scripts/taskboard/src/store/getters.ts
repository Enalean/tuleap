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

import type { RootState } from "./type";
import type { ColumnDefinition, Swimlane, Card, Tracker } from "../type";

export const column_of_cell =
    (root_state: RootState) =>
    (cell: HTMLElement): ColumnDefinition | undefined => {
        return root_state.column.columns.find(
            (column) => column.id === Number(cell.dataset.columnId),
        );
    };

export const column_and_swimlane_of_cell =
    (root_state: RootState) =>
    (
        cell: HTMLElement,
    ): {
        swimlane: Swimlane | undefined;
        column: ColumnDefinition | undefined;
    } => {
        const swimlane = root_state.swimlane.swimlanes.find(
            (swimlane) => swimlane.card.id === Number(cell.dataset.swimlaneId),
        );

        const column = column_of_cell(root_state)(cell);

        return {
            swimlane,
            column,
        };
    };

function findTracker(root_state: RootState, tracker_id: number): Tracker {
    const tracker = root_state.trackers.find((tracker) => tracker.id === tracker_id);

    if (!tracker) {
        throw new Error(`Tracker ${tracker_id} has not been found in the store.`);
    }

    return tracker;
}

export const tracker_of_card =
    (root_state: RootState) =>
    (card: Card): Tracker => {
        return findTracker(root_state, card.tracker_id);
    };

export const can_add_in_place =
    (root_state: RootState) =>
    (swimlane: Swimlane): boolean => {
        const tracker = findTracker(root_state, swimlane.card.tracker_id);

        return tracker.add_in_place !== null;
    };

export const has_at_least_one_cell_in_add_mode = (state: RootState): boolean => {
    return state.is_a_cell_adding_in_place;
};
