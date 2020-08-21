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

import { CellObject, ColInfo, Comments, Range, RowInfo, utils, WorkSheet } from "xlsx";
import { ExportReport, ReportSection } from "../../Report/report-creator";
import { ReportCell } from "../../Report/report-cells";

const CELL_BASE_CHARACTER_WIDTH = 10;
const CELL_MAX_CHARACTER_WIDTH = 65;
const LINE_HEIGHT_POINTS = 12;

type CellObjectWithExtraInfo = CellObject & {
    character_width: number;
    nb_lines: number;
    merge_columns?: number;
};

export function transformAReportIntoASheet(report: ExportReport): WorkSheet {
    const cells = transformSectionsIntoSheetRows(report.sections);
    const worksheet = utils.aoa_to_sheet(transformSectionsIntoSheetRows(report.sections));
    worksheet["!cols"] = fitColumnWidthsToContent(cells);
    worksheet["!rows"] = fitRowHeightsToContent(cells);
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

function buildSheetEmptyCell(): CellObjectWithExtraInfo {
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

function fitColumnWidthsToContent(cells: CellObjectWithExtraInfo[][]): ColInfo[] {
    const max_column_width: number[] = [];

    cells.forEach((row: CellObjectWithExtraInfo[]): void => {
        row.forEach((cell: CellObjectWithExtraInfo, column_position: number): void => {
            const current_max_value = max_column_width[column_position];
            max_column_width[column_position] = Math.min(
                Math.max(isNaN(current_max_value) ? 0 : current_max_value, cell.character_width),
                CELL_MAX_CHARACTER_WIDTH
            );
        });
    });

    return max_column_width.map(
        (column_width: number): ColInfo => {
            return { wch: column_width };
        }
    );
}

function fitRowHeightsToContent(cells: CellObjectWithExtraInfo[][]): RowInfo[] {
    const row_info: RowInfo[] = [];
    cells.forEach((row: CellObjectWithExtraInfo[]): void => {
        const nb_lines_row = row.reduce(
            (previous: { nb_lines: number }, current: { nb_lines: number }) =>
                previous.nb_lines > current.nb_lines ? previous : current,
            { nb_lines: 1 }
        ).nb_lines;
        if (nb_lines_row >= 2) {
            row_info.push({ hpt: nb_lines_row * LINE_HEIGHT_POINTS });
        } else {
            row_info.push({});
        }
    });

    return row_info;
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
