/**
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

import { utils, writeFile } from "xlsx";
import type { ReportSection } from "../../Data/data-formator";
import type { ReportCell, CellObjectWithExtraInfo } from "@tuleap/plugin-docgen-xlsx";
import { fitColumnWidthsToContent, fitRowHeightsToContent } from "@tuleap/plugin-docgen-xlsx";
import type { ExportSettings } from "../../export-document";

const CELL_BASE_CHARACTER_WIDTH = 10;

export function downloadXLSX(export_settings: ExportSettings, formatted_data: ReportSection): void {
    const book = utils.book_new();
    const cells = buildContent(export_settings, formatted_data);
    const sheet = utils.aoa_to_sheet(cells);
    sheet["!cols"] = fitColumnWidthsToContent(cells);
    sheet["!rows"] = fitRowHeightsToContent(cells);
    utils.book_append_sheet(book, sheet);
    writeFile(
        book,
        export_settings.first_level.tracker_name +
            "-" +
            export_settings.first_level.report_name +
            ".xlsx",
        {
            bookSST: true,
        }
    );
}

function buildContent(
    export_settings: ExportSettings,
    formatted_data: ReportSection
): Array<Array<CellObjectWithExtraInfo>> {
    const content: CellObjectWithExtraInfo[][] = [];
    const report_columns_label: CellObjectWithExtraInfo[] = [];
    if (formatted_data.headers && formatted_data.headers.length > 0) {
        for (const header of formatted_data.headers) {
            report_columns_label.push(transformFormattedCellIntoASheetCell(header));
        }
        content.push(report_columns_label);
    }

    let artifact_value_rows: CellObjectWithExtraInfo[] = [];
    if (formatted_data.rows && formatted_data.rows.length > 0) {
        for (const row of formatted_data.rows) {
            artifact_value_rows = [];
            for (const cell of row) {
                artifact_value_rows.push(transformFormattedCellIntoASheetCell(cell));
            }
            content.push(artifact_value_rows);
        }
    }

    return content;
}

function transformFormattedCellIntoASheetCell(formatted_cell: ReportCell): CellObjectWithExtraInfo {
    switch (formatted_cell.type) {
        case "text":
            return buildSheetTextCell(formatted_cell.value);
        case "html":
            return buildSheetTextCell(extractPlaintextFromHTMLString(formatted_cell.value));
        case "number": {
            return {
                t: "n",
                v: formatted_cell.value,
                character_width: String(formatted_cell.value).length,
                nb_lines: 1,
            };
        }
        case "date":
            return {
                t: "d",
                v: formatted_cell.value,
                character_width: CELL_BASE_CHARACTER_WIDTH,
                nb_lines: 1,
            };
        case "empty":
        default:
            return {
                t: "z",
                character_width: 0,
                nb_lines: 0,
            };
    }
}

function buildSheetTextCell(value: string): CellObjectWithExtraInfo {
    const text_value = value.replace(/\r\n/g, "\n").trim();
    const text_value_by_lines = text_value.split("\n");
    const max_length_line = text_value_by_lines.reduce(
        (previous: string, current: string) =>
            previous.length > current.length ? previous : current,
        ""
    ).length;
    return {
        t: "s",
        v: text_value,
        character_width: max_length_line,
        nb_lines: text_value_by_lines.length,
    };
}

function extractPlaintextFromHTMLString(html: string): string {
    const dom_parser = new DOMParser();
    return dom_parser.parseFromString(html, "text/html").body.textContent ?? "";
}
