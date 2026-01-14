/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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
import type { ExportSettings } from "./export-document";
import type { ReportSection } from "./data/data-formator";
import { utils, writeFile } from "xlsx";
import type { CellObjectWithExtraInfo } from "@tuleap/plugin-docgen-xlsx";
import {
    createMergesForWholeRowLine,
    fitColumnWidthsToContent,
    fitRowHeightsToContent,
    transformReportCellIntoASheetCell,
} from "@tuleap/plugin-docgen-xlsx";
import { generateAutofilterRange } from "./export/autofilter-generator";
import { generateFilename } from "./export/file-name-generator";

export function downloadCSV(export_settings: ExportSettings, formatted_data: ReportSection): void {
    const book = utils.book_new();
    const cells = buildContent(export_settings, formatted_data);
    const sheet = utils.aoa_to_sheet(cells);
    sheet["!cols"] = fitColumnWidthsToContent(cells);
    sheet["!rows"] = fitRowHeightsToContent(cells);
    sheet["!merges"] = createMergesForWholeRowLine(cells);
    generateAutofilterRange(formatted_data).apply((ref) => (sheet["!autofilter"] = { ref }));
    utils.book_append_sheet(book, sheet);
    writeFile(book, generateFilename(export_settings, "csv"), {
        bookSST: true,
        bookType: "csv",
        FS: getCSVSeparator(export_settings),
    });
}

function buildContent(
    export_settings: ExportSettings,
    formatted_data: ReportSection,
): Array<Array<CellObjectWithExtraInfo>> {
    const content: CellObjectWithExtraInfo[][] = [];
    if (formatted_data.headers) {
        const report_columns_label: CellObjectWithExtraInfo[] = [];
        if (
            formatted_data.headers.reports_fields_labels &&
            formatted_data.headers.reports_fields_labels.length > 0
        ) {
            for (const header of formatted_data.headers.reports_fields_labels) {
                report_columns_label.push(transformReportCellIntoASheetCell(header));
            }
            content.push(report_columns_label);
        }
    }

    let artifact_value_rows: CellObjectWithExtraInfo[] = [];
    if (formatted_data.artifacts_rows && formatted_data.artifacts_rows.length > 0) {
        for (const row of formatted_data.artifacts_rows) {
            artifact_value_rows = [];
            for (const cell of row) {
                const sheet_cell = transformReportCellIntoASheetCell(cell);
                if (sheet_cell.t === "d") {
                    switch (export_settings.date_format) {
                        case "day_month_year":
                            sheet_cell.z = "dd/mm/yyyy hh:mm";
                            break;
                        case "month_day_year":
                        default:
                            sheet_cell.z = "mm/dd/yyyy hh:mm";
                            break;
                    }
                }
                artifact_value_rows.push(sheet_cell);
            }
            content.push(artifact_value_rows);
        }
    }

    return content;
}

function getCSVSeparator(export_settings: ExportSettings): string {
    switch (export_settings.csv_separator) {
        case "semicolon":
            return ";";
        case "tab":
            return "\t";
        case "comma":
        default:
            return ",";
    }
}
