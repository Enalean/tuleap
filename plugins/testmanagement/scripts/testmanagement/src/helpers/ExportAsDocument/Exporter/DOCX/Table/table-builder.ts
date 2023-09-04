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

import type { ExternalHyperlink, InternalHyperlink, TextRun } from "docx";
import { BorderStyle, Paragraph, ShadingType, TableCell } from "docx";
import { getInternationalizedTestStatus } from "../internationalize-test-status";
import type { ArtifactFieldValueStatus } from "@tuleap/plugin-docgen-docx";
import { buildCellContentStatus, TABLE_MARGINS } from "@tuleap/plugin-docgen-docx";
import type { GettextProvider } from "@tuleap/gettext";

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
    content: TextRun | ExternalHyperlink | InternalHyperlink,
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

export const buildCellContentResult = (
    status: ArtifactFieldValueStatus,
    gettext_provider: GettextProvider,
    row_span: number,
): TableCell => {
    return buildCellContentStatus(
        status,
        (status) => getInternationalizedTestStatus(gettext_provider, status),
        row_span,
    );
};
