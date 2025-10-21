/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { RowEntry } from "./TableDataStore";
import { getNumberOfParent } from "./NumberOfParentForRowCalculator";
import type { ArtifactLinkRowData } from "../components/TableWrapper.vue";

export function isLastVisibleChildWithMoreUnloadedSiblings(
    row_entry: RowEntry,
    table_data_store_row_list: Array<RowEntry>,
    uuids_of_loading_rows: Array<ArtifactLinkRowData>,
): boolean {
    if (
        uuids_of_loading_rows.some(
            (loading_row) => loading_row.row_uuid === row_entry.parent_row_uuid,
        )
    ) {
        return false;
    }
    const level_where_we_can_have_a_load_all_button = 2;
    let remove_already_seen_parent = 0;
    if (
        getNumberOfParent(table_data_store_row_list, row_entry) >=
        level_where_we_can_have_a_load_all_button
    ) {
        remove_already_seen_parent = 1;
    }

    const parent_row = table_data_store_row_list.find(
        (entry) => entry.row.row_uuid === row_entry.parent_row_uuid,
    );
    if (!parent_row || !row_entry.parent_row_uuid) {
        return false;
    }

    const last_child_of_parent = findLastChildOfParent(
        table_data_store_row_list,
        row_entry.parent_row_uuid,
    );
    const is_last_child =
        last_child_of_parent !== undefined &&
        last_child_of_parent.row.row_uuid === row_entry.row.row_uuid;

    if (!is_last_child) {
        return false;
    }

    const expected_links =
        parent_row.row.expected_number_of_forward_links +
        parent_row.row.expected_number_of_reverse_links;

    if (expected_links === 0) {
        return false;
    }

    const parent_children = table_data_store_row_list.filter(
        (entry) => entry.parent_row_uuid === row_entry.parent_row_uuid,
    );

    const displayed_children_count = parent_children.length;

    return expected_links - remove_already_seen_parent > displayed_children_count;
}

function findLastChildOfParent(rows: Array<RowEntry>, parent_uuid: string): RowEntry | undefined {
    const children = rows.filter((entry) => entry.parent_row_uuid === parent_uuid);

    if (children.length === 0) {
        return undefined;
    }

    let last_index = -1;
    let last_child: RowEntry | undefined = undefined;

    for (let i = 0; i < rows.length; i++) {
        if (rows[i].parent_row_uuid === parent_uuid) {
            if (i > last_index) {
                last_index = i;
                last_child = rows[i];
            }
        }
    }

    return last_child;
}
