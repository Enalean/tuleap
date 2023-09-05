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

import type {
    DateReportCriterionValue,
    ExportDocument,
    GlobalExportProperties,
    ReportCriterionValue,
    TraceabilityMatrixElement,
} from "../../type";
import type { ITableCellOptions, XmlComponent } from "docx";
import {
    AlignmentType,
    BorderStyle,
    convertInchesToTwip,
    ExternalHyperlink,
    File,
    Footer,
    Header,
    HeadingLevel,
    InternalHyperlink,
    LevelFormat,
    Packer,
    PageBreak,
    PageNumber,
    PageOrientation,
    Paragraph,
    ShadingType,
    StyleLevel,
    Table,
    TableCell,
    TableLayoutType,
    TableRow,
    TabStopPosition,
    TabStopType,
    TextRun,
    WidthType,
} from "docx";
import { TableOfContentsPrefilled } from "./TableOfContents/table-of-contents";
import type { GetText } from "@tuleap/gettext";
import { sprintf } from "sprintf-js";
import { triggerBlobDownload } from "../trigger-blob-download";
import type {
    ArtifactFieldValueStatus,
    DateTimeLocaleInformation,
    ArtifactFieldValueStepDefinitionContent,
} from "@tuleap/plugin-docgen-docx";
import {
    loadImage,
    getAnchorToArtifactContent,
    HTML_ORDERED_LIST_NUMBERING,
    HTML_UNORDERED_LIST_NUMBERING,
    TABLE_MARGINS,
    buildCellContentStatus,
} from "@tuleap/plugin-docgen-docx";
import { getInternationalizedTestStatus } from "./internationalize-test-status";
import { buildListOfArtifactsContent } from "./build-list-of-artifacts-content";

const MAIN_TITLES_NUMBERING_ID = "main-titles";
const HEADER_STYLE_ARTIFACT_TITLE = "ArtifactTitle";
const HEADER_LEVEL_ARTIFACT_TITLE = HeadingLevel.HEADING_2;
const HEADER_LEVEL_SECTION = HeadingLevel.HEADING_1;
const TABLE_LABEL_SHADING = {
    val: ShadingType.CLEAR,
    color: "auto",
    fill: "EEEEEE",
};
const TABLE_BORDERS = {
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

export async function downloadDocx(
    document: ExportDocument<ArtifactFieldValueStepDefinitionContent>,
    gettext_provider: GetText,
    global_export_properties: GlobalExportProperties,
    datetime_locale_information: DateTimeLocaleInformation,
): Promise<void> {
    const exported_formatted_date = new Date().toLocaleDateString(
        datetime_locale_information.locale,
        { timeZone: datetime_locale_information.timezone },
    );

    const footers = {
        default: buildFooter(gettext_provider, global_export_properties, exported_formatted_date),
    };

    const headers = {
        default: buildHeader(global_export_properties),
    };

    const artifacts_content = await buildListOfArtifactsContent(
        document.artifacts,
        HEADER_LEVEL_ARTIFACT_TITLE,
        HEADER_STYLE_ARTIFACT_TITLE,
        gettext_provider,
    );

    const table_of_contents = [
        new Paragraph({
            text: gettext_provider.gettext("Table of contents"),
            heading: HEADER_LEVEL_SECTION,
            numbering: {
                reference: MAIN_TITLES_NUMBERING_ID,
                level: 0,
            },
        }),
        new TableOfContentsPrefilled(document.artifacts, {
            hyperlink: true,
            stylesWithLevels: [
                new StyleLevel(
                    HEADER_STYLE_ARTIFACT_TITLE,
                    Number(HEADER_LEVEL_ARTIFACT_TITLE.substr(-1)),
                ),
            ],
        }),
    ];

    const report_criteria_data = [];
    report_criteria_data.push(
        new Paragraph({
            text: gettext_provider.gettext("Tracker Report"),
            heading: HEADER_LEVEL_SECTION,
            numbering: {
                reference: MAIN_TITLES_NUMBERING_ID,
                level: 0,
            },
        }),
    );

    const report_criteria = global_export_properties.report_criteria;
    let report_criteria_content;
    if (report_criteria.is_in_expert_mode) {
        if (report_criteria.query === "") {
            report_criteria_content = new Paragraph(
                gettext_provider.gettext(
                    "No filter has been applied to this report because no TQL query has been set",
                ),
            );
        } else {
            report_criteria_content = new Paragraph(
                gettext_provider.gettext("TQL query: ") + report_criteria.query,
            );
        }
    } else {
        if (report_criteria.criteria.length > 0) {
            report_criteria_content = buildReportCriteriaDisplayZone(
                report_criteria.criteria,
                gettext_provider,
                datetime_locale_information,
            );
        } else {
            report_criteria_content = new Paragraph(
                gettext_provider.gettext(
                    "No filter has been applied to this report because no search criteria has a value set",
                ),
            );
        }
    }

    report_criteria_data.push(report_criteria_content);

    const traceability_matrix_children = buildTraceabilityMatrix(
        document.traceability_matrix,
        gettext_provider,
    );
    const traceability_matrix_sections = [];
    if (traceability_matrix_children.length > 0) {
        traceability_matrix_sections.push({
            headers,
            children: [...traceability_matrix_children],
            footers,
            properties: {
                page: {
                    size: {
                        orientation: PageOrientation.LANDSCAPE,
                    },
                },
            },
        });
    }

    const file = new File({
        features: {
            updateFields: true,
        },
        creator: global_export_properties.user_display_name,
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
        numbering: {
            config: [
                {
                    reference: MAIN_TITLES_NUMBERING_ID,
                    levels: [
                        {
                            level: 0,
                            format: LevelFormat.DECIMAL,
                            alignment: AlignmentType.START,
                            text: "%1.",
                        },
                    ],
                },
                HTML_UNORDERED_LIST_NUMBERING,
                HTML_ORDERED_LIST_NUMBERING,
            ],
        },
        sections: [
            {
                children: [
                    ...(await buildCoverPage(
                        gettext_provider,
                        global_export_properties,
                        exported_formatted_date,
                    )),
                ],
                properties: {
                    titlePage: true,
                },
            },
            {
                headers,
                children: [...table_of_contents],
            },
            {
                headers,
                children: [...report_criteria_data],
                footers,
            },
            ...traceability_matrix_sections,
            {
                headers,
                children: [
                    new Paragraph({
                        text: gettext_provider.gettext("Artifacts"),
                        heading: HEADER_LEVEL_SECTION,
                        numbering: {
                            reference: MAIN_TITLES_NUMBERING_ID,
                            level: 0,
                        },
                    }),
                    ...artifacts_content,
                ],
                footers,
            },
        ],
    });
    triggerBlobDownload(`${document.name}.docx`, await Packer.toBlob(file));
}

function buildHeader(global_export_properties: GlobalExportProperties): Header {
    return new Header({
        children: [
            new Paragraph({
                children: [
                    new TextRun({
                        children: [
                            global_export_properties.platform_name,
                            " | ",
                            global_export_properties.project_name,
                        ],
                    }),
                    new TextRun({
                        children: [
                            "\t",
                            global_export_properties.tracker_name,
                            " | ",
                            global_export_properties.report_name,
                        ],
                    }),
                ],
                tabStops: [
                    {
                        type: TabStopType.RIGHT,
                        position: TabStopPosition.MAX,
                    },
                ],
            }),
        ],
    });
}

function buildFooter(
    gettext_provider: GetText,
    global_export_properties: GlobalExportProperties,
    exported_formatted_date: string,
): Footer {
    return new Footer({
        children: [
            new Paragraph({
                children: [
                    new TextRun({
                        children: [
                            sprintf(
                                gettext_provider.gettext("Exported on %s by %s"),
                                exported_formatted_date,
                                global_export_properties.user_display_name,
                            ),
                        ],
                    }),
                    new TextRun({
                        children: ["\t", PageNumber.CURRENT, " / ", PageNumber.TOTAL_PAGES],
                    }),
                ],
                tabStops: [
                    {
                        type: TabStopType.RIGHT,
                        position: TabStopPosition.MAX,
                    },
                ],
            }),
        ],
    });
}

async function buildCoverPage(
    gettext_provider: GetText,
    global_export_properties: GlobalExportProperties,
    exported_formatted_date: string,
): Promise<ReadonlyArray<XmlComponent>> {
    const {
        platform_name,
        platform_logo_url,
        project_name,
        tracker_name,
        report_name,
        report_url,
        user_display_name,
    } = global_export_properties;

    return [
        new Paragraph({
            children: [await loadImage(platform_logo_url)],
            alignment: AlignmentType.CENTER,
        }),
        new Paragraph({
            text: report_name,
            heading: HeadingLevel.TITLE,
        }),
        new Paragraph({
            text: `———`,
            style: "title_separator",
        }),
        buildCoverTable(
            gettext_provider,
            platform_name,
            project_name,
            tracker_name,
            report_url,
            user_display_name,
            exported_formatted_date,
        ),
        new Paragraph({ children: [new PageBreak()] }),
    ];
}

function buildCoverTable(
    gettext_provider: GetText,
    platform_name: string,
    project_name: string,
    tracker_name: string,
    report_url: string,
    user_name: string,
    exported_formatted_date: string,
): Table {
    return new Table({
        width: {
            size: 100,
            type: WidthType.PERCENTAGE,
        },
        borders: TABLE_BORDERS,
        columnWidths: [2000, 7638],
        layout: TableLayoutType.FIXED,
        rows: [
            buildCoverTableRow(gettext_provider.gettext("Platform"), new TextRun(platform_name)),
            buildCoverTableRow(gettext_provider.gettext("Project"), new TextRun(project_name)),
            buildCoverTableRow(gettext_provider.gettext("Tracker"), new TextRun(tracker_name)),
            buildCoverTableRow(gettext_provider.gettext("Exported by"), new TextRun(user_name)),
            buildCoverTableRow(
                gettext_provider.gettext("Exported on"),
                new TextRun(exported_formatted_date),
            ),
            buildCoverTableRow(
                gettext_provider.gettext("Report URL"),
                new ExternalHyperlink({
                    children: [new TextRun(report_url)],
                    link: report_url,
                }),
            ),
        ],
    });
}

function buildCoverTableRow(label: string, value: TextRun | ExternalHyperlink): TableRow {
    return new TableRow({
        children: [buildTableCellLabel(label), buildTableCellContent(value)],
    });
}

function buildReportCriteriaDisplayZone(
    criterion_values: ReadonlyArray<ReportCriterionValue>,
    gettext_provider: GetText,
    datetime_locale_information: DateTimeLocaleInformation,
): Table {
    const fields_rows = [
        new TableRow({
            children: [
                buildTableCellHeaderLabel(gettext_provider.gettext("Search criteria")),
                buildTableCellHeaderValue(gettext_provider.gettext("Value")),
            ],
            tableHeader: true,
        }),
    ];

    for (const criterion_value of criterion_values) {
        if (criterion_value.criterion_type === "classic") {
            const table_row = new TableRow({
                children: [
                    buildTableCellLabel(criterion_value.criterion_name),
                    buildTableCellContent(new TextRun(criterion_value.criterion_value)),
                ],
            });

            fields_rows.push(table_row);
        } else if (criterion_value.criterion_type === "date") {
            fields_rows.push(
                buildDateReportCriterionValue(
                    criterion_value,
                    gettext_provider,
                    datetime_locale_information,
                ),
            );
        }
    }

    return buildTable(fields_rows);
}

function buildDateReportCriterionValue(
    date_criterion_value: DateReportCriterionValue,
    gettext_provider: GetText,
    datetime_locale_information: DateTimeLocaleInformation,
): TableRow {
    if (!date_criterion_value.is_advanced) {
        const formatted_to_date = new Date(
            date_criterion_value.criterion_to_value,
        ).toLocaleDateString(datetime_locale_information.locale, {
            timeZone: datetime_locale_information.timezone,
        });

        let table_cell_date_criterion_content = "";
        if (date_criterion_value.criterion_value_operator === ">") {
            table_cell_date_criterion_content = sprintf(
                gettext_provider.gettext("After %s"),
                formatted_to_date,
            );
        } else if (date_criterion_value.criterion_value_operator === "<") {
            table_cell_date_criterion_content = sprintf(
                gettext_provider.gettext("Before %s"),
                formatted_to_date,
            );
        } else {
            table_cell_date_criterion_content = sprintf(
                gettext_provider.gettext("As of %s"),
                formatted_to_date,
            );
        }

        return new TableRow({
            children: [
                buildTableCellLabel(date_criterion_value.criterion_name),
                buildTableCellContent(new TextRun(table_cell_date_criterion_content)),
            ],
        });
    }

    let table_cell_date_criterion_content = "";
    if (date_criterion_value.criterion_from_value && date_criterion_value.criterion_to_value) {
        const formatted_from_date = new Date(
            date_criterion_value.criterion_from_value,
        ).toLocaleDateString(datetime_locale_information.locale, {
            timeZone: datetime_locale_information.timezone,
        });

        const formatted_to_date = new Date(
            date_criterion_value.criterion_to_value,
        ).toLocaleDateString(datetime_locale_information.locale, {
            timeZone: datetime_locale_information.timezone,
        });

        table_cell_date_criterion_content = sprintf(
            gettext_provider.gettext("After %s and before %s"),
            formatted_from_date,
            formatted_to_date,
        );
    } else if (
        date_criterion_value.criterion_from_value &&
        !date_criterion_value.criterion_to_value
    ) {
        const formatted_from_date = new Date(
            date_criterion_value.criterion_from_value,
        ).toLocaleDateString(datetime_locale_information.locale, {
            timeZone: datetime_locale_information.timezone,
        });

        table_cell_date_criterion_content = sprintf(
            gettext_provider.gettext("After %s"),
            formatted_from_date,
        );
    } else if (
        !date_criterion_value.criterion_from_value &&
        date_criterion_value.criterion_to_value
    ) {
        const formatted_to_date = new Date(
            date_criterion_value.criterion_to_value,
        ).toLocaleDateString(datetime_locale_information.locale, {
            timeZone: datetime_locale_information.timezone,
        });

        table_cell_date_criterion_content = sprintf(
            gettext_provider.gettext("Before %s"),
            formatted_to_date,
        );
    }

    return new TableRow({
        children: [
            buildTableCellLabel(date_criterion_value.criterion_name),
            buildTableCellContent(new TextRun(table_cell_date_criterion_content)),
        ],
    });
}

function buildTable(fields_rows: TableRow[]): Table {
    return new Table({
        rows: fields_rows,
        // Some readers such as Google Docs does not deal properly with automatic table column widths.
        // To avoid that we use the same strategy than LibreOffice and set the column widths explicitly.
        // The table is expected to take the whole width, the page width with the margins is ~9638 DXA so
        // we set the size of the labels column 3000 and the size of the values column to 6638 so
        // (3000 + 6638) = 9638 DXA
        width: {
            size: 100,
            type: WidthType.PERCENTAGE,
        },
        borders: TABLE_BORDERS,
        columnWidths: [3000, 6638],
        layout: TableLayoutType.FIXED,
    });
}

function buildTableCellHeaderLabel(name: string): TableCell {
    return new TableCell({
        children: [
            new Paragraph({
                text: name,
                style: "table_header_label",
            }),
        ],
        margins: TABLE_MARGINS,
        shading: {
            type: ShadingType.CLEAR,
            color: "auto",
            fill: "DDDDDD",
        },
    });
}

function buildTableCellHeaderValue(name: string): TableCell {
    return new TableCell({
        children: [
            new Paragraph({
                text: name,
                style: "table_header_value",
            }),
        ],
        margins: TABLE_MARGINS,
        shading: {
            type: ShadingType.CLEAR,
            color: "auto",
            fill: "DDDDDD",
        },
    });
}

function buildTableCellLabel(name: string): TableCell {
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

function buildTableCellContent(
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

function buildTraceabilityMatrix(
    elements: ReadonlyArray<TraceabilityMatrixElement>,
    gettext_provider: GetText,
): (Paragraph | Table)[] {
    if (elements.length === 0) {
        return [];
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

    const buildCellContentResult = (status: ArtifactFieldValueStatus): TableCell => {
        return buildCellContentStatus(
            status,
            (status) => getInternationalizedTestStatus(gettext_provider, status),
            1,
        );
    };

    const table = new Table({
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
                    buildHeaderCell(gettext_provider.gettext("Tests")),
                    buildHeaderCell(gettext_provider.gettext("Campaigns")),
                    buildHeaderCell(gettext_provider.gettext("Results")),
                    buildHeaderCell(gettext_provider.gettext("Executed By")),
                    buildHeaderCell(gettext_provider.gettext("Executed On")),
                ],
            }),
            ...elements.map(
                (element) =>
                    new TableRow({
                        children: [
                            buildCellContent(new TextRun(element.requirement)),
                            buildCellContent(
                                new InternalHyperlink({
                                    children: [new TextRun(element.test.title)],
                                    anchor: getAnchorToArtifactContent(element.test),
                                }),
                            ),
                            buildCellContent(new TextRun(element.campaign)),
                            buildCellContentResult(element.result),
                            buildCellContent(new TextRun(element.executed_by || "")),
                            buildCellContent(new TextRun(element.executed_on || "")),
                        ],
                    }),
            ),
        ],
    });

    return [
        new Paragraph({
            text: gettext_provider.gettext("Traceability Matrix"),
            heading: HEADER_LEVEL_SECTION,
            numbering: {
                reference: MAIN_TITLES_NUMBERING_ID,
                level: 0,
            },
        }),
        table,
        new Paragraph({ children: [new PageBreak()] }),
    ];
}
