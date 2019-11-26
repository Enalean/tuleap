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

import { RootState } from "./type";
import { ColumnDefinition, Swimlane } from "../type";

export const column_of_cell = (root_state: RootState) => (
    cell: HTMLElement
): ColumnDefinition | undefined => {
    return root_state.column.columns.find(column => column.id === Number(cell.dataset.columnId));
};

export const column_and_swimlane_of_cell = (root_state: RootState) => (
    cell: HTMLElement
): {
    swimlane?: Swimlane;
    column?: ColumnDefinition;
} => {
    const swimlane = root_state.swimlane.swimlanes.find(
        swimlane => swimlane.card.id === Number(cell.dataset.swimlaneId)
    );

    const column = column_of_cell(root_state)(cell);

    return {
        swimlane,
        column
    };
};
