/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { Range } from "xlsx";
import type { CellObjectWithExtraInfo } from "./type";

export function createMerges(cells: CellObjectWithExtraInfo[][]): Range[] {
    return cells.flatMap((row: CellObjectWithExtraInfo[], row_line: number): Range[] => {
        if (typeof row[0] === "undefined") {
            return [];
        }
        const first_cell_row = row[0];
        if (first_cell_row.merge_columns) {
            return [
                {
                    s: { r: row_line, c: 0 },
                    e: { r: row_line, c: first_cell_row.merge_columns },
                },
            ];
        }
        return [];
    });
}

export function createMergesForWholeRowLine(cells: CellObjectWithExtraInfo[][]): Range[] {
    return cells.flatMap((rows: CellObjectWithExtraInfo[], row_line: number): Range[] => {
        if (typeof rows[0] === "undefined") {
            return [];
        }

        const merges: Array<Range> = [];
        let starting_cell = 0;
        for (const row of rows) {
            if (row.merge_columns) {
                merges.push({
                    s: { r: row_line, c: starting_cell },
                    e: { r: row_line, c: starting_cell + row.merge_columns },
                });
            } else {
                merges.push({
                    s: { r: row_line, c: starting_cell },
                    e: { r: row_line, c: starting_cell },
                });
            }

            starting_cell++;
        }

        return merges;
    });
}
