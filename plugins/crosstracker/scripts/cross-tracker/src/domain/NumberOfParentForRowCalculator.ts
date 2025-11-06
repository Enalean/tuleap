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

export function getNumberOfParent(
    table_data_store_row_list: Array<RowEntry>,
    row_entry: RowEntry,
): number {
    if (!row_entry || row_entry.parent_row_uuid === null) {
        return 0;
    }

    const parent = table_data_store_row_list.find(
        (item) => item.row.row_uuid === row_entry.parent_row_uuid,
    );

    if (!parent) {
        throw new Error("Parent not found in rows collection");
    }

    return 1 + getNumberOfParent(table_data_store_row_list, parent);
}
