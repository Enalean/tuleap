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

import { CellObject, ColInfo, utils, WorkSheet } from "xlsx";
import { ExportReport, ReportSection } from "./report-creator";
import { ReportCell } from "./report-cells";

type CellObjectWithCharacterWidth = CellObject & { character_width: number };

export function transformAReportIntoASheet(report: ExportReport): WorkSheet {
    const cells = transformSectionsIntoSheetRows(report.sections);
    const worksheet = utils.aoa_to_sheet(transformSectionsIntoSheetRows(report.sections));
    worksheet["!cols"] = fitColumnWidthsToContent(cells);

    return worksheet;
}

function transformSectionsIntoSheetRows(
    sections: ReadonlyArray<ReportSection>
): CellObjectWithCharacterWidth[][] {
    return sections.flatMap((section) => section.rows.map(transformReportSectionRowsIntoSheetRows));
}

function transformReportSectionRowsIntoSheetRows(
    report_section_row: ReadonlyArray<ReportCell>
): CellObjectWithCharacterWidth[] {
    return report_section_row.map(transformReportCellIntoASheetCell);
}

function transformReportCellIntoASheetCell(report_cell: ReportCell): CellObjectWithCharacterWidth {
    return {
        t: "s",
        v: report_cell.value,
        character_width: report_cell.value.length,
    };
}

function fitColumnWidthsToContent(cells: CellObjectWithCharacterWidth[][]): ColInfo[] {
    const max_column_width: number[] = [];

    cells.forEach((row: CellObjectWithCharacterWidth[]): void => {
        row.forEach((cell: CellObjectWithCharacterWidth, column_position: number): void => {
            const current_max_value = max_column_width[column_position];
            max_column_width[column_position] = Math.max(
                isNaN(current_max_value) ? 0 : current_max_value,
                cell.character_width
            );
        });
    });

    return max_column_width.map(
        (column_width: number): ColInfo => {
            return { wch: column_width };
        }
    );
}
