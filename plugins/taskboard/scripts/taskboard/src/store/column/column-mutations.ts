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

import type { ColumnDefinition } from "../../type";
import type { ColumnState } from "./type";

export function collapseColumn(state: ColumnState, column: ColumnDefinition): void {
    findColumn(state, column).is_collapsed = true;
}
export function expandColumn(state: ColumnState, column: ColumnDefinition): void {
    findColumn(state, column).is_collapsed = false;
}
export function pointerEntersColumn(state: ColumnState, column: ColumnDefinition): void {
    findColumn(state, column).has_hover = true;
}
export function pointerLeavesColumn(state: ColumnState, column: ColumnDefinition): void {
    findColumn(state, column).has_hover = false;
}

function findColumn(state: ColumnState, column: ColumnDefinition): ColumnDefinition {
    const column_state: ColumnDefinition | undefined = state.columns.find(
        (col) => col.id === column.id,
    );
    if (!column_state) {
        throw new Error("Could not find column with id=" + column.id);
    }

    return column_state;
}
