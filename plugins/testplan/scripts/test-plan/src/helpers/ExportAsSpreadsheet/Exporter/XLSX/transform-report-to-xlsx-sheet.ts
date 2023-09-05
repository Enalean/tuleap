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
import type { ExportReport, ReportSection } from "../../Report/report-creator";
import type { ReportCell, CellObjectWithExtraInfo } from "@tuleap/plugin-docgen-xlsx";
import {
    fitColumnWidthsToContent,
    fitRowHeightsToContent,
    transformReportCellIntoASheetCell,
    buildSheetTextCell,
    buildSheetEmptyCell,
    createMerges,
} from "@tuleap/plugin-docgen-xlsx";

const CELL_BASE_CHARACTER_WIDTH = 10;

export function transformAReportIntoASheet(report: ExportReport): WorkSheet {
    const cells = transformSectionsIntoSheetRows(report.sections);
    const worksheet = utils.aoa_to_sheet(transformSectionsIntoSheetRows(report.sections));
    worksheet["!cols"] = fitColumnWidthsToContent(cells);
    worksheet["!rows"] = fitRowHeightsToContent(cells);
    worksheet["!merges"] = createMerges(cells);

    return worksheet;
}

function transformSectionsIntoSheetRows(
    sections: ReadonlyArray<ReportSection>,
): CellObjectWithExtraInfo[][] {
    return sections.flatMap((section) => {
        const section_cells: CellObjectWithExtraInfo[][] = [];

        if (section.title) {
            let nb_columns_to_merge = 0;
            if (section.headers) {
                nb_columns_to_merge = section.headers.length - 1;
            }
            section_cells.push([
                {
                    ...buildSheetTextCell(section.title.value),
                    ...(nb_columns_to_merge > 0
                        ? { character_width: CELL_BASE_CHARACTER_WIDTH }
                        : {}),
                    merge_columns: nb_columns_to_merge,
                },
            ]);
        }

        if (section.headers) {
            section_cells.push(transformReportSectionRowsIntoSheetRows(section.headers));
        }

        section_cells.push(...section.rows.map(transformReportSectionRowsIntoSheetRows));
        section_cells.push([buildSheetEmptyCell()]);

        return section_cells;
    });
}

function transformReportSectionRowsIntoSheetRows(
    report_section_row: ReadonlyArray<ReportCell>,
): CellObjectWithExtraInfo[] {
    return report_section_row.map(transformReportCellIntoASheetCell);
}
