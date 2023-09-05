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

import type { ColInfo, RowInfo } from "xlsx";
import type { CellObjectWithExtraInfo } from "./type";

const CELL_MAX_CHARACTER_WIDTH = 65;
const LINE_HEIGHT_POINTS = 12;

export function fitColumnWidthsToContent(cells: CellObjectWithExtraInfo[][]): ColInfo[] {
    const max_column_width: number[] = [];

    cells.forEach((row: CellObjectWithExtraInfo[]): void => {
        row.forEach((cell: CellObjectWithExtraInfo, column_position: number): void => {
            const current_max_value = max_column_width[column_position];
            max_column_width[column_position] = Math.min(
                Math.max(isNaN(current_max_value) ? 0 : current_max_value, cell.character_width),
                CELL_MAX_CHARACTER_WIDTH,
            );
        });
    });

    return max_column_width.map((column_width: number): ColInfo => {
        return { wch: column_width };
    });
}

export function fitRowHeightsToContent(cells: CellObjectWithExtraInfo[][]): RowInfo[] {
    const row_info: RowInfo[] = [];
    cells.forEach((row: CellObjectWithExtraInfo[]): void => {
        const nb_lines_row = row.reduce(
            (previous: { nb_lines: number }, current: { nb_lines: number }) =>
                previous.nb_lines > current.nb_lines ? previous : current,
            { nb_lines: 1 },
        ).nb_lines;
        if (nb_lines_row >= 2) {
            row_info.push({ hpt: nb_lines_row * LINE_HEIGHT_POINTS });
        } else {
            row_info.push({});
        }
    });

    return row_info;
}
