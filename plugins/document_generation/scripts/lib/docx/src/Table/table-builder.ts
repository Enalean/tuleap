/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { ArtifactFieldValueStatus } from "../type";
import { A4_PORTRAIT_APPROXIMATE_WIDTH_IN_DXA } from "../type";
import type {
    InternalHyperlink,
    IShadingAttributesProperties,
    ITableCellOptions,
    TableRow,
} from "docx";
import {
    convertInchesToTwip,
    Paragraph,
    Table,
    TableCell,
    TableLayoutType,
    TextRun,
    WidthType,
    BorderStyle,
} from "docx";

export const TABLE_MARGINS = {
    top: convertInchesToTwip(0.05),
    bottom: convertInchesToTwip(0.05),
    left: convertInchesToTwip(0.05),
    right: convertInchesToTwip(0.05),
};

export const TABLE_BORDERS = {
    top: {
        style: BorderStyle.NONE,
        size: 0,
        color: "FFFFFF",
    },
    right: {
        style: BorderStyle.NONE,
        size: 0,
        color: "FFFFFF",
    },
    bottom: {
        style: BorderStyle.NONE,
        size: 0,
        color: "FFFFFF",
    },
    left: {
        style: BorderStyle.NONE,
        size: 0,
        color: "FFFFFF",
    },
    insideHorizontal: {
        style: BorderStyle.NONE,
        size: 0,
        color: "FFFFFF",
    },
    insideVertical: {
        style: BorderStyle.NONE,
        size: 0,
        color: "FFFFFF",
    },
};

export const buildCellContentOptions = (
    content: TextRun | InternalHyperlink,
): ITableCellOptions => {
    return {
        children: [
            new Paragraph({
                children: [content],
                style: "table_value",
            }),
        ],
        margins: TABLE_MARGINS,
    };
};

export const buildCellContentStatus = (
    status: ArtifactFieldValueStatus,
    status_to_label: (status: ArtifactFieldValueStatus) => string,
    rowSpan: number,
): TableCell => {
    const text = status_to_label(status);
    const table_cell_options = buildCellContentOptions(
        new TextRun({
            text,
            color: "ffffff",
        }),
    );

    const additional_cell_options: { shading?: IShadingAttributesProperties } = {};
    switch (status) {
        case "passed":
            additional_cell_options.shading = {
                fill: "1aa350",
            };
            break;
        case "blocked":
            additional_cell_options.shading = {
                fill: "1aacd8",
            };
            break;
        case "failed":
            additional_cell_options.shading = {
                fill: "e04b4b",
            };
            break;
        default:
            additional_cell_options.shading = {
                fill: "717171",
            };
            break;
    }

    return new TableCell({ ...table_cell_options, rowSpan, ...additional_cell_options });
};

export const buildTable = (rows: TableRow[]): Table => {
    // Some readers such as Google Docs does not deal properly with automatic table column widths.
    // To avoid it, we use the same strategy as LibreOffice and set the column widths explicitly.
    // The table is expected to take the whole width, the page width with the margins is ~9638 DXA, so
    // we set the same size for every column

    const nb_columns = rows.length > 0 ? rows[0].cells.length : 0;
    const widths = Array(nb_columns).fill(A4_PORTRAIT_APPROXIMATE_WIDTH_IN_DXA / nb_columns);

    return buildTableWithGivenColumnWidths(rows, widths);
};

export const buildLabelValueTable = (fields_rows: TableRow[]): Table => {
    // Some readers such as Google Docs does not deal properly with automatic table column widths.
    // To avoid it, we use the same strategy as LibreOffice and set the column widths explicitly.
    // The table is expected to take the whole width, the page width with the margins is ~9638 DXA so
    // we set the size of the labels column 3000 and the size of the value column to 6638 so
    // (3000 + 6638) = 9638 DXA

    const first_column_width_in_dxa = 3000;
    const second_column_width_in_dxa =
        A4_PORTRAIT_APPROXIMATE_WIDTH_IN_DXA - first_column_width_in_dxa;

    return buildTableWithGivenColumnWidths(fields_rows, [
        first_column_width_in_dxa,
        second_column_width_in_dxa,
    ]);
};

function buildTableWithGivenColumnWidths(rows: TableRow[], widths: number[]): Table {
    return new Table({
        rows,
        width: {
            size: 100,
            type: WidthType.PERCENTAGE,
        },
        borders: TABLE_BORDERS,
        columnWidths: widths,
        layout: TableLayoutType.FIXED,
    });
}
