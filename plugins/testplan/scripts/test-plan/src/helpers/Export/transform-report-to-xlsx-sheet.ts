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

import { CellObject, ColInfo, Comments, Range, utils, WorkSheet } from "xlsx";
import { ExportReport, ReportSection } from "./report-creator";
import { ReportCell } from "./report-cells";

type CellObjectWithExtraInfo = CellObject & {
    character_width: number;
    merge_columns?: number;
};

export function transformAReportIntoASheet(report: ExportReport): WorkSheet {
    const cells = transformSectionsIntoSheetRows(report.sections);
    const worksheet = utils.aoa_to_sheet(transformSectionsIntoSheetRows(report.sections));
    worksheet["!cols"] = fitColumnWidthsToContent(cells);
    worksheet["!merges"] = createMerges(cells);

    return worksheet;
}

function transformSectionsIntoSheetRows(
    sections: ReadonlyArray<ReportSection>
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
    report_section_row: ReadonlyArray<ReportCell>
): CellObjectWithExtraInfo[] {
    return report_section_row.map(transformReportCellIntoASheetCell);
}

function transformReportCellIntoASheetCell(report_cell: ReportCell): CellObjectWithExtraInfo {
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
                character_width: 10,
            };
            break;
        case "number":
            sheet_cell = {
                t: "n",
                v: report_cell.value,
                character_width: String(report_cell.value).length,
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

function buildSheetTextCell(value: string): CellObjectWithExtraInfo {
    return {
        t: "s",
        v: value,
        character_width: value.length,
    };
}

function buildSheetEmptyCell(): CellObjectWithExtraInfo {
    return {
        t: "z",
        character_width: 0,
    };
}

function extractPlaintextFromHTMLString(html: string): string {
    const dom_parser = new DOMParser();
    return dom_parser.parseFromString(html, "text/html").body.textContent ?? "";
}

function fitColumnWidthsToContent(cells: CellObjectWithExtraInfo[][]): ColInfo[] {
    const max_column_width: number[] = [];

    cells.forEach((row: CellObjectWithExtraInfo[]): void => {
        row.forEach((cell: CellObjectWithExtraInfo, column_position: number): void => {
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

function createMerges(cells: CellObjectWithExtraInfo[][]): Range[] {
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
