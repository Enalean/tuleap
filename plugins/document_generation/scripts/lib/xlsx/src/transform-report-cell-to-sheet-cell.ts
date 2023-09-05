/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { ReportCell } from "./report-cells";
import type { CellObjectWithExtraInfo } from "./type";
import type { Comments } from "xlsx";

const CELL_BASE_CHARACTER_WIDTH = 10;
const CELL_CONTENT_MAX_LENGTH = 32767;
const CELL_TEXT_CONTENT_TRUNCATED_ENDING = "[...]";

export function transformReportCellIntoASheetCell(
    report_cell: ReportCell,
): CellObjectWithExtraInfo {
    let sheet_cell: CellObjectWithExtraInfo;
    switch (report_cell.type) {
        case "text":
            sheet_cell = buildSheetTextCell(report_cell.value);
            break;
        case "html":
            sheet_cell = buildSheetTextCell(extractPlaintextFromHTMLString(report_cell.value));
            break;
        case "date":
            sheet_cell = {
                t: "d",
                v: report_cell.value,
                character_width: CELL_BASE_CHARACTER_WIDTH,
                nb_lines: 1,
            };
            break;
        case "number":
            sheet_cell = {
                t: "n",
                v: report_cell.value,
                character_width: String(report_cell.value).length,
                nb_lines: 1,
            };
            break;
        case "empty":
            sheet_cell = buildSheetEmptyCell();
            break;
        default:
            return ((val: never): never => val)(report_cell);
    }

    if (report_cell.comment) {
        const comments: Comments = [{ t: report_cell.comment }];
        comments.hidden = true;
        return {
            ...sheet_cell,
            c: comments,
        };
    }

    return sheet_cell;
}

export function buildSheetTextCell(value: string): CellObjectWithExtraInfo {
    let text_value = value.replace(/\r\n/g, "\n").trim();
    if (text_value.length > CELL_CONTENT_MAX_LENGTH) {
        text_value =
            text_value.substring(
                0,
                CELL_CONTENT_MAX_LENGTH - CELL_TEXT_CONTENT_TRUNCATED_ENDING.length,
            ) + CELL_TEXT_CONTENT_TRUNCATED_ENDING;
    }
    const text_value_by_lines = text_value.split("\n");
    const max_length_line = text_value_by_lines.reduce(
        (previous: string, current: string) =>
            previous.length > current.length ? previous : current,
        "",
    ).length;
    return {
        t: "s",
        v: text_value,
        character_width: max_length_line,
        nb_lines: text_value_by_lines.length,
    };
}

export function buildSheetEmptyCell(): CellObjectWithExtraInfo {
    return {
        t: "z",
        character_width: 0,
        nb_lines: 1,
    };
}

function extractPlaintextFromHTMLString(html: string): string {
    const dom_parser = new DOMParser();
    return dom_parser.parseFromString(html, "text/html").body.textContent ?? "";
}
