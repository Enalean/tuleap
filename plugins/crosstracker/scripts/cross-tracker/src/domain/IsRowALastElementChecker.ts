/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import type { RowEntry } from "./TableDataStore";

export function isLastChildForDirection(row_collection: RowEntry[], row_entry: RowEntry): boolean {
    const parent = getParentOfRow(row_collection, row_entry);
    const last_child_for_direction = row_collection.findLast(
        (entry) =>
            entry.parent_row_uuid === parent.row.row_uuid &&
            entry.row.direction === row_entry.row.direction,
    );

    return last_child_for_direction
        ? last_child_for_direction.row.row_uuid === row_entry.row.row_uuid
        : false;
}

function getParentOfRow(row_collection: RowEntry[], row: RowEntry): RowEntry {
    const parent = row_collection.find((entry) => entry.row.row_uuid === row.parent_row_uuid);

    if (parent === undefined) {
        throw new Error("Parent is not found in collection");
    }
    return parent;
}
