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
import type { CellObjectWithExtraInfo } from "@tuleap/plugin-docgen-xlsx";
import {
    buildSheetEmptyCell,
    buildSheetTextCell,
    createMergesForWholeRowLine,
    fitColumnWidthsToContent,
    fitRowHeightsToContent,
    transformReportCellIntoASheetCell,
} from "@tuleap/plugin-docgen-xlsx";
import type { ExportSettings } from "../../export-document";
import { generateFilename } from "./file-name-generator";
import { generateAutofilterRange } from "./autofilter-generator";

export function downloadXLSX(export_settings: ExportSettings, formatted_data: ReportSection): void {
    const book = utils.book_new();
    const cells = buildContent(export_settings, formatted_data);
    const sheet = utils.aoa_to_sheet(cells);
    sheet["!cols"] = fitColumnWidthsToContent(cells);
    sheet["!rows"] = fitRowHeightsToContent(cells);
    sheet["!merges"] = createMergesForWholeRowLine(cells);
    sheet["!autofilter"] = { ref: generateAutofilterRange(formatted_data) };
    utils.book_append_sheet(book, sheet);
    writeFile(book, generateFilename(export_settings), {
        bookSST: true,
    });
}

function buildContent(
    export_settings: ExportSettings,
    formatted_data: ReportSection,
): Array<Array<CellObjectWithExtraInfo>> {
    const content: CellObjectWithExtraInfo[][] = [];
    const report_trackers_names: CellObjectWithExtraInfo[] = [];
    if (formatted_data.headers) {
        if (
            formatted_data.headers.tracker_names &&
            formatted_data.headers.tracker_names.length > 0
        ) {
            for (const tracker_name of formatted_data.headers.tracker_names) {
                report_trackers_names.push({
                    ...buildSheetTextCell(tracker_name.value),
                    ...(tracker_name.merges > 0 ? { character_width: 10 } : {}),
                    merge_columns: tracker_name.merges - 1,
                });

                let empty_cells_to_add = 0;
                while (empty_cells_to_add < tracker_name.merges - 1) {
                    report_trackers_names.push(buildSheetEmptyCell());
                    empty_cells_to_add++;
                }
            }

            content.push(report_trackers_names);
        }

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
                artifact_value_rows.push(transformReportCellIntoASheetCell(cell));
            }
            content.push(artifact_value_rows);
        }
    }

    return content;
}
