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

export function hasExpandableLinks(row_entry: RowEntry, level: number): boolean {
    const total_number_of_links =
        row_entry.row.expected_number_of_forward_links +
        row_entry.row.expected_number_of_reverse_links;

    if (level === 0) {
        return total_number_of_links > 0;
    }
    return total_number_of_links > 1;
}
