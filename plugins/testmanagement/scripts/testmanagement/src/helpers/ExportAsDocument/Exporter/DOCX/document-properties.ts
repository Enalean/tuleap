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
import type { IPropertiesOptions } from "docx/build/file/core-properties";
import { AlignmentType, convertInchesToTwip, HeadingLevel } from "docx";

const HEADER_STYLE_ARTIFACT_TITLE = "ArtifactTitle";
const HEADER_LEVEL_ARTIFACT_TITLE = HeadingLevel.HEADING_2;

export const properties: Omit<IPropertiesOptions, "sections"> = {
    features: {
        updateFields: true,
    },
    styles: {
        paragraphStyles: [
            {
                id: HeadingLevel.TITLE,
                name: HeadingLevel.TITLE,
                basedOn: "Normal",
                next: "Normal",
                run: {
                    size: 64,
                    bold: true,
                    color: "000000",
                },
                paragraph: {
                    alignment: AlignmentType.CENTER,
                    spacing: {
                        before: convertInchesToTwip(1.5),
                    },
                },
            },
            {
                id: "title_separator",
                name: "title_separator",
                basedOn: "Normal",
                next: "Normal",
                run: {
                    size: 48,
                },
                paragraph: {
                    alignment: AlignmentType.CENTER,
                    spacing: {
                        before: convertInchesToTwip(0.2),
                        after: convertInchesToTwip(0.75),
                    },
                },
            },
            {
                id: HEADER_STYLE_ARTIFACT_TITLE,
                name: HEADER_STYLE_ARTIFACT_TITLE,
                basedOn: HEADER_LEVEL_ARTIFACT_TITLE,
                next: HEADER_LEVEL_ARTIFACT_TITLE,
                quickFormat: true,
            },
            {
                id: "table_header_label",
                name: "table_header_label",
                basedOn: "Normal",
                next: "Normal",
                run: {
                    size: 20,
                    color: "333333",
                    allCaps: true,
                    bold: true,
                },
                paragraph: {
                    alignment: AlignmentType.RIGHT,
                },
            },
            {
                id: "table_header_value",
                name: "table_header_value",
                basedOn: "Normal",
                next: "Normal",
                run: {
                    size: 20,
                    color: "333333",
                    allCaps: true,
                    bold: true,
                },
                paragraph: {
                    alignment: AlignmentType.LEFT,
                },
            },
            {
                id: "table_label",
                name: "table_label",
                basedOn: "Normal",
                next: "Normal",
                run: {
                    size: 20,
                    color: "333333",
                },
                paragraph: {
                    alignment: AlignmentType.RIGHT,
                },
            },
            {
                id: "table_value",
                name: "table_value",
                basedOn: "Normal",
                next: "Normal",
                run: {
                    size: 20,
                },
                paragraph: {
                    alignment: AlignmentType.LEFT,
                },
            },
        ],
    },
};
