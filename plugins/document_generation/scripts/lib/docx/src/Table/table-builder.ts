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
import type { InternalHyperlink, IShadingAttributesProperties, ITableCellOptions } from "docx";
import { convertInchesToTwip, Paragraph, TableCell, TextRun } from "docx";

export const TABLE_MARGINS = {
    top: convertInchesToTwip(0.05),
    bottom: convertInchesToTwip(0.05),
    left: convertInchesToTwip(0.05),
    right: convertInchesToTwip(0.05),
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
