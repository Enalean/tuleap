/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import type { ExportDocument } from "../type";
import {
    AlignmentType,
    Bookmark,
    File,
    Footer,
    HeadingLevel,
    Packer,
    PageBreak,
    PageNumber,
    Paragraph,
    StyleLevel,
    Table,
    TableCell,
    TableRow,
    TextRun,
    WidthType,
} from "docx";
import { TableOfContentsPrefilled } from "./DOCX/TableOfContents/table-of-contents";
import { getAnchorToArtifactContent } from "./DOCX/sections-anchor";
import {
    getPOFileFromLocale,
    initGettext,
} from "../../../../../../src/scripts/tuleap/gettext/gettext-init";

const HEADER_STYLE_ARTIFACT_TITLE = "ArtifactTitle";
const HEADER_LEVEL_ARTIFACT_TITLE = HeadingLevel.HEADING_6;

export async function downloadDocx(document: ExportDocument, language: string): Promise<void> {
    const gettext_provider = await initGettext(
        language,
        "tracker-report-action",
        (locale) =>
            import(
                /* webpackChunkName: "tracker-report-po-" */ "../../po/" +
                    getPOFileFromLocale(locale)
            )
    );

    const footers = {
        default: new Footer({
            children: [
                new Paragraph({
                    alignment: AlignmentType.CENTER,
                    children: [
                        new TextRun({
                            children: [PageNumber.CURRENT, " / ", PageNumber.TOTAL_PAGES],
                        }),
                    ],
                }),
            ],
        }),
    };

    const artifacts_content = [];
    for (const artifact of document.artifacts) {
        artifacts_content.push(
            new Paragraph({
                heading: HEADER_LEVEL_ARTIFACT_TITLE,
                style: HEADER_STYLE_ARTIFACT_TITLE,
                children: [
                    new Bookmark({
                        id: getAnchorToArtifactContent(artifact),
                        children: [new TextRun(artifact.title)],
                    }),
                ],
            })
        );

        const fields_rows = [
            new TableRow({
                children: [
                    new TableCell({
                        children: [
                            new Paragraph({
                                text: gettext_provider.gettext("Field name"),
                                style: "table_header",
                            }),
                        ],
                    }),
                    new TableCell({
                        children: [
                            new Paragraph({
                                text: gettext_provider.gettext("Value"),
                                style: "table_header",
                            }),
                        ],
                    }),
                ],
                tableHeader: true,
            }),
        ];

        for (const artifact_value of artifact.fields) {
            const table_row = new TableRow({
                children: [
                    new TableCell({
                        children: [new Paragraph(artifact_value.field_name)],
                    }),
                    new TableCell({
                        children: [new Paragraph(artifact_value.field_value.toString())],
                    }),
                ],
            });

            fields_rows.push(table_row);
        }
        artifacts_content.push(
            new Table({
                rows: fields_rows,
                width: {
                    size: 100,
                    type: WidthType.PERCENTAGE,
                },
            })
        );
    }

    const table_of_contents = new TableOfContentsPrefilled(document.artifacts, {
        hyperlink: true,
        stylesWithLevels: [
            new StyleLevel("ArtifactTitle", Number(HEADER_LEVEL_ARTIFACT_TITLE.substr(-1))),
        ],
    });

    const file = new File({
        styles: {
            paragraphStyles: [
                {
                    id: HEADER_STYLE_ARTIFACT_TITLE,
                    name: HEADER_STYLE_ARTIFACT_TITLE,
                    basedOn: HEADER_LEVEL_ARTIFACT_TITLE,
                    next: HEADER_LEVEL_ARTIFACT_TITLE,
                    quickFormat: true,
                },
                {
                    id: "table_header",
                    name: "table_header",
                    basedOn: "Normal",
                    next: "Normal",
                    run: {
                        bold: true,
                        allCaps: true,
                        size: 18,
                    },
                    paragraph: {
                        alignment: AlignmentType.CENTER,
                    },
                },
            ],
        },
        sections: [
            {
                children: [
                    table_of_contents,
                    new Paragraph({ children: [new PageBreak()] }),
                    ...artifacts_content,
                ],
                footers,
            },
        ],
    });
    await triggerDownload(document.name, file);
}

async function triggerDownload(filename: string, file: File): Promise<void> {
    const blob = await Packer.toBlob(file);
    const download_link = document.createElement("a");
    const object_url = URL.createObjectURL(blob);
    download_link.href = object_url;
    download_link.setAttribute("download", `${filename}.docx`);
    document.body.appendChild(download_link);
    download_link.click();
    download_link.remove();
    URL.revokeObjectURL(object_url);
}
