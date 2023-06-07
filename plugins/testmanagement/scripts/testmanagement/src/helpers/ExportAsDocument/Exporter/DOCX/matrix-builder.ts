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
    ExportDocument,
    TraceabilityMatrixElement,
    ArtifactFieldValueStepDefinitionEnhancedWithResults,
} from "../../../../type";
import {
    InternalHyperlink,
    Table,
    TableCell,
    Bookmark,
    TextRun,
    Paragraph,
    WidthType,
    TableRow,
} from "docx";
import {
    HEADER_LEVEL_SECTION_TITLE,
    HEADER_STYLE_SECTION_TITLE,
    MAIN_TITLES_NUMBERING_ID,
} from "./document-properties";
import {
    buildCellContentOptions,
    getAnchorToArtifactContent,
    TABLE_MARGINS,
} from "@tuleap/plugin-docgen-docx/src";
import { buildCellContentResult, TABLE_BORDERS, TABLE_LABEL_SHADING } from "./Table/table-builder";
import { computeRequirementStatus } from "./matrix-compute-requirement-status";
import type { GettextProvider } from "@tuleap/gettext";

export function getTraceabilityMatrixTitle(gettext_provider: GettextProvider): {
    id: string;
    text: string;
} {
    return {
        id: "matrix",
        text: gettext_provider.gettext("Traceability matrix"),
    };
}

export function buildTraceabilityMatrix(
    document: ExportDocument<ArtifactFieldValueStepDefinitionEnhancedWithResults>,
    gettext_provider: GettextProvider
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
                gettext_provider.gettext(
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

function buildTraceabilityMatrixTable(
    elements: ReadonlyArray<TraceabilityMatrixElement>,
    gettext_provider: GettextProvider
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
                    buildHeaderCell(gettext_provider.gettext("Requirements")),
                    buildHeaderCell(gettext_provider.gettext("Status")),
                    buildHeaderCell(gettext_provider.gettext("Tests")),
                    buildHeaderCell(gettext_provider.gettext("Campaigns")),
                    buildHeaderCell(gettext_provider.gettext("Results")),
                    buildHeaderCell(gettext_provider.gettext("Executed By")),
                    buildHeaderCell(gettext_provider.gettext("Executed On")),
                ],
            }),
            ...elements.reduce((acc: TableRow[], element) => {
                let is_first = true;
                for (const test of element.tests.values()) {
                    const first_cell: TableCell[] = [];
                    if (is_first) {
                        first_cell.push(
                            buildCellContentWithRowspan(
                                new InternalHyperlink({
                                    children: [new TextRun(element.requirement.title)],
                                    anchor: getAnchorToArtifactContent(element.requirement),
                                }),
                                element.tests.size
                            ),
                            buildCellContentResult(
                                computeRequirementStatus([...element.tests.values()]),
                                gettext_provider,
                                element.tests.size
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
