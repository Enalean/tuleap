/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import type { WorkSheet } from "xlsx";
import { utils } from "xlsx";
import type { CellObjectWithExtraInfo } from "@tuleap/plugin-docgen-xlsx";
import {
    fitColumnWidthsToContent,
    fitRowHeightsToContent,
    transformReportCellIntoASheetCell,
    buildSheetTextCell,
} from "@tuleap/plugin-docgen-xlsx";
import type { ReportSection } from "./data-formater";

export function transformAReportCellIntoASheet(report: ReportSection): WorkSheet {
    const book = utils.book_new();
    const cells = transformReportCellRowsIntoSheetRows(report);
    const worksheet = utils.aoa_to_sheet(cells);
    worksheet["!cols"] = fitColumnWidthsToContent(cells);
    worksheet["!rows"] = fitRowHeightsToContent(cells);
    utils.book_append_sheet(book, worksheet);
    return worksheet;
}

function transformReportCellRowsIntoSheetRows(
    xlsx_report_cells: ReportSection,
): CellObjectWithExtraInfo[][] {
    const report_cells_for_sheet: CellObjectWithExtraInfo[][] = [];
    report_cells_for_sheet.push(
        xlsx_report_cells.headers.map((report_header_cell_value) =>
            buildSheetTextCell(report_header_cell_value.value),
        ),
    );
    report_cells_for_sheet.push(
        ...xlsx_report_cells.rows.map((row) => row.map(transformReportCellIntoASheetCell)),
    );
    return report_cells_for_sheet;
}
