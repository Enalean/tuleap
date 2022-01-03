/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import type {
    ExternalHyperlink,
    InternalHyperlink,
    IShadingAttributesProperties,
    ITableCellOptions,
} from "docx";
import { BorderStyle, convertInchesToTwip, Paragraph, ShadingType, TableCell, TextRun } from "docx";
import type { VueGettextProvider } from "../../../../vue-gettext-provider";
import { getInternationalizedTestStatus } from "../internationalize-test-status";
import type { ArtifactFieldValueStatus } from "@tuleap/plugin-docgen-docx";

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

export const TABLE_LABEL_SHADING = {
    val: ShadingType.CLEAR,
    color: "auto",
    fill: "EEEEEE",
};

export const TABLE_MARGINS = {
    top: convertInchesToTwip(0.05),
    bottom: convertInchesToTwip(0.05),
    left: convertInchesToTwip(0.05),
    right: convertInchesToTwip(0.05),
};

export function buildTableCellLabel(name: string): TableCell {
    return new TableCell({
        children: [
            new Paragraph({
                text: name,
                style: "table_label",
            }),
        ],
        margins: TABLE_MARGINS,
        shading: TABLE_LABEL_SHADING,
    });
}

export function buildTableCellContent(
    content: TextRun | ExternalHyperlink | InternalHyperlink
): TableCell {
    return new TableCell({
        children: [
            new Paragraph({
                children: [content],
                style: "table_value",
            }),
        ],
        margins: TABLE_MARGINS,
    });
}

export const buildCellContentOptions = (
    content: TextRun | InternalHyperlink
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

export const buildCellContentResult = (
    result: ArtifactFieldValueStatus,
    gettext_provider: VueGettextProvider,
    rowSpan: number
): TableCell => {
    const status = getInternationalizedTestStatus(gettext_provider, result);
    const table_cell_options = buildCellContentOptions(
        new TextRun({
            text: status,
            color: "ffffff",
        })
    );

    const additional_cell_options: { shading?: IShadingAttributesProperties } = {};
    switch (result) {
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
