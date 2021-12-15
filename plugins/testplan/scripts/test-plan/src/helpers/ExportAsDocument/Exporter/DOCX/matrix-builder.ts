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

import type { ExportDocument, TraceabilityMatrixElement } from "../../../../type";
import type { VueGettextProvider } from "../../../vue-gettext-provider";
import type { IShadingAttributesProperties, ITableCellOptions } from "docx";
import { InternalHyperlink, Table, TableCell } from "docx";
import {
    HEADER_LEVEL_SECTION_TITLE,
    HEADER_STYLE_SECTION_TITLE,
    MAIN_TITLES_NUMBERING_ID,
} from "./document-properties";
import { Bookmark, TextRun, Paragraph, WidthType, TableRow } from "docx";
import type { ArtifactFieldValueStatus } from "@tuleap/plugin-docgen-docx/src";
import { getAnchorToArtifactContent } from "@tuleap/plugin-docgen-docx/src";
import { TABLE_BORDERS, TABLE_LABEL_SHADING, TABLE_MARGINS } from "./Table/table-builder";
import { getInternationalizedTestStatus } from "../../../ExportAsSpreadsheet/Report/internationalize-test-status";
import { computeRequirementStatus } from "./matrix-compute-requirement-status";

export function getTraceabilityMatrixTitle(gettext_provider: VueGettextProvider): {
    id: string;
    text: string;
} {
    return {
        id: "matrix",
        text: gettext_provider.$gettext("Traceability matrix"),
    };
}

export function buildTraceabilityMatrix(
    document: ExportDocument,
    gettext_provider: VueGettextProvider
): (Paragraph | Table)[] {
    const title = getTraceabilityMatrixTitle(gettext_provider);

    const section_title = new Paragraph({
        heading: HEADER_LEVEL_SECTION_TITLE,
        style: HEADER_STYLE_SECTION_TITLE,
        numbering: {
            reference: MAIN_TITLES_NUMBERING_ID,
            level: 0,
        },
        children: [
            new Bookmark({
                id: title.id,
                children: [new TextRun(title.text)],
            }),
        ],
    });

    if (document.traceability_matrix.length === 0) {
        return [
            section_title,
            new Paragraph(
                gettext_provider.$gettext(
                    "There isn't any requirements to put in the traceability matrix."
                )
            ),
        ];
    }

    return [
        section_title,
        buildTraceabilityMatrixTable(document.traceability_matrix, gettext_provider),
    ];
}

const buildHeaderCell = (value: string): TableCell =>
    new TableCell({
        children: [
            new Paragraph({
                text: value,
            }),
        ],
        margins: TABLE_MARGINS,
        shading: TABLE_LABEL_SHADING,
    });

const buildCellContentOptions = (content: TextRun | InternalHyperlink): ITableCellOptions => {
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

const buildCellContent = (content: TextRun | InternalHyperlink): TableCell =>
    new TableCell(buildCellContentOptions(content));

const buildCellContentWithRowspan = (
    content: TextRun | InternalHyperlink,
    rowSpan: number
): TableCell =>
    new TableCell({
        ...buildCellContentOptions(content),
        rowSpan,
    });

const buildCellContentResult = (
    result: ArtifactFieldValueStatus,
    gettext_provider: VueGettextProvider,
    rowSpan: number
): TableCell => {
    const status = getInternationalizedTestStatus(gettext_provider, result);
    let table_cell_options = buildCellContentOptions(new TextRun(status));

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
            table_cell_options = buildCellContentOptions(
                new TextRun({
                    text: status,
                    color: "ffffff",
                })
            );
            break;
    }

    return new TableCell({ ...table_cell_options, rowSpan, ...additional_cell_options });
};

function buildTraceabilityMatrixTable(
    elements: ReadonlyArray<TraceabilityMatrixElement>,
    gettext_provider: VueGettextProvider
): Table {
    return new Table({
        width: {
            size: 100,
            type: WidthType.PERCENTAGE,
        },
        borders: TABLE_BORDERS,
        rows: [
            new TableRow({
                tableHeader: true,
                children: [
                    buildHeaderCell(gettext_provider.$gettext("Requirements")),
                    buildHeaderCell(gettext_provider.$gettext("Status")),
                    buildHeaderCell(gettext_provider.$gettext("Tests")),
                    buildHeaderCell(gettext_provider.$gettext("Campaigns")),
                    buildHeaderCell(gettext_provider.$gettext("Results")),
                    buildHeaderCell(gettext_provider.$gettext("Executed By")),
                    buildHeaderCell(gettext_provider.$gettext("Executed On")),
                ],
            }),
            ...elements.reduce((acc: TableRow[], element) => {
                let is_first = true;
                for (const test of element.tests) {
                    const first_cell: TableCell[] = [];
                    if (is_first) {
                        first_cell.push(
                            buildCellContentWithRowspan(
                                new TextRun(element.requirement.title),
                                element.tests.length
                            ),
                            buildCellContentResult(
                                computeRequirementStatus(element.tests),
                                gettext_provider,
                                element.tests.length
                            )
                        );
                        is_first = false;
                    }
                    const children = [
                        ...first_cell,
                        buildCellContent(
                            new InternalHyperlink({
                                children: [new TextRun(test.title)],
                                anchor: getAnchorToArtifactContent(test),
                            })
                        ),
                        buildCellContent(new TextRun(test.campaign)),
                        buildCellContentResult(test.status, gettext_provider, 1),
                        buildCellContent(new TextRun(test.executed_by || "")),
                        buildCellContent(new TextRun(test.executed_on || "")),
                    ];

                    acc.push(
                        new TableRow({
                            children,
                        })
                    );
                }
                return acc;
            }, []),
        ],
    });
}
