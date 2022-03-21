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

import type { CellObject } from "xlsx";
import { utils, writeFile } from "xlsx";
import type { GlobalExportProperties } from "../../type";
import type { ReportSection, TextCell } from "../../Data/data-formator";

export function downloadXLSX(
    global_properties: GlobalExportProperties,
    formatted_data: ReportSection
): void {
    const book = utils.book_new();
    const sheet = utils.aoa_to_sheet(buildContent(global_properties, formatted_data));
    utils.book_append_sheet(book, sheet);
    writeFile(
        book,
        global_properties.tracker_name + "-" + global_properties.report_name + ".xlsx",
        {
            bookSST: true,
        }
    );
}

function buildContent(
    global_properties: GlobalExportProperties,
    formatted_data: ReportSection
): Array<Array<CellObject>> {
    const content: CellObject[][] = [];
    const report_columns_label: CellObject[] = [];
    if (formatted_data.headers && formatted_data.headers.length > 0) {
        for (const header of formatted_data.headers) {
            report_columns_label.push(transformFormattedTextCellIntoASheetCell(header));
        }
        content.push(report_columns_label);
    }

    return content;
}

function transformFormattedTextCellIntoASheetCell(report_cell: TextCell): CellObject {
    return {
        t: "s",
        v: report_cell.value,
    };
}
