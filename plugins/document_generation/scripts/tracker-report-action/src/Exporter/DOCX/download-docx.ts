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
    ArtifactContainer,
    ArtifactFieldValue,
    ReportCriterionValue,
    DateTimeLocaleInformation,
    ExportDocument,
    GlobalExportProperties,
    DateReportCriterionValue,
} from "../../type";
import type { ILevelsOptions, ParagraphChild, XmlComponent } from "docx";
import {
    AlignmentType,
    Bookmark,
    BorderStyle,
    convertInchesToTwip,
    ExternalHyperlink,
    File,
    Footer,
    HeadingLevel,
    LevelFormat,
    Packer,
    PageBreak,
    PageNumber,
    Paragraph,
    StyleLevel,
    Table,
    TableCell,
    TableRow,
    TextRun,
    VerticalAlign,
    WidthType,
} from "docx";
import { TableOfContentsPrefilled } from "./TableOfContents/table-of-contents";
import { getAnchorToArtifactContent } from "./sections-anchor";
import type { GetText } from "../../../../../../../src/scripts/tuleap/gettext/gettext-init";
import { sprintf } from "sprintf-js";
import { triggerBlobDownload } from "../trigger-blob-download";

const MAIN_TITLES_NUMBERING_ID = "main-titles";
const HEADER_STYLE_ARTIFACT_TITLE = "ArtifactTitle";
const HEADER_LEVEL_ARTIFACT_TITLE = HeadingLevel.HEADING_2;
const HEADER_LEVEL_SECTION = HeadingLevel.HEADING_1;

export async function downloadDocx(
    document: ExportDocument,
    gettext_provider: GetText,
    global_export_properties: GlobalExportProperties,
    datetime_locale_information: DateTimeLocaleInformation
): Promise<void> {
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

        if (artifact.fields.length > 0) {
            artifacts_content.push(buildFieldValuesDisplayZone(artifact.fields));
        }

        artifacts_content.push(...buildContainersDisplayZone(artifact.containers));
    }

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
                new StyleLevel("ArtifactTitle", Number(HEADER_LEVEL_ARTIFACT_TITLE.substr(-1))),
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
        })
    );

    const report_criteria = global_export_properties.report_criteria;
    let report_criteria_content;
    if (report_criteria.is_in_expert_mode) {
        if (report_criteria.query === "") {
            report_criteria_content = new Paragraph(
                gettext_provider.gettext(
                    "No filter has been applied to this report because no TQL query has been set"
                )
            );
        } else {
            report_criteria_content = new Paragraph(
                gettext_provider.gettext("TQL query: ") + report_criteria.query
            );
        }
    } else {
        if (report_criteria.criteria.length > 0) {
            report_criteria_content = buildReportCriteriaDisplayZone(
                report_criteria.criteria,
                gettext_provider,
                datetime_locale_information
            );
        } else {
            report_criteria_content = new Paragraph(
                gettext_provider.gettext(
                    "No filter has been applied to this report because no search criteria has a value set"
                )
            );
        }
    }

    report_criteria_data.push(report_criteria_content);

    const file = new File({
        creator: global_export_properties.user_display_name,
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
                        spacing: {
                            before: 200,
                            after: 200,
                        },
                    },
                },
                {
                    id: "table_content",
                    name: "table_content",
                    basedOn: "Normal",
                    next: "Normal",
                    paragraph: {
                        alignment: AlignmentType.LEFT,
                        spacing: {
                            before: 50,
                            after: 50,
                        },
                    },
                },
            ],
        },
        numbering: {
            config: [
                {
                    reference: "unordered-list",
                    levels: [
                        {
                            level: 0,
                            format: LevelFormat.BULLET,
                            text: "•",
                            alignment: AlignmentType.LEFT,
                            style: {
                                paragraph: {
                                    indent: {
                                        left: convertInchesToTwip(0.1),
                                        hanging: convertInchesToTwip(0.1),
                                    },
                                },
                            },
                        },
                    ],
                },
                {
                    reference: MAIN_TITLES_NUMBERING_ID,
                    levels: generateMainTitlesNumberingLevelConfiguration(),
                },
            ],
        },
        sections: [
            {
                children: [
                    ...buildIntroductionParagraphes(
                        gettext_provider,
                        global_export_properties,
                        datetime_locale_information
                    ),
                    ...table_of_contents,
                    new Paragraph({ children: [new PageBreak()] }),
                    ...report_criteria_data,
                    new Paragraph({ children: [new PageBreak()] }),
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

function buildIntroductionParagraphes(
    gettext_provider: GetText,
    global_export_properties: GlobalExportProperties,
    datetime_locale_information: DateTimeLocaleInformation
): ReadonlyArray<Paragraph> {
    const { platform_name, project_name, tracker_name, report_name, report_url } =
        global_export_properties;
    return [
        new Paragraph({
            heading: HeadingLevel.TITLE,
            text: `${platform_name} ‒ ${project_name} ‒ ${tracker_name} ‒ ${report_name}`,
        }),
        buildParagraphOfAnUnorderedList(
            new TextRun(
                sprintf(
                    gettext_provider.gettext("Export date: %s"),
                    new Date().toLocaleDateString(datetime_locale_information.locale, {
                        timeZone: datetime_locale_information.timezone,
                    })
                )
            )
        ),
        buildParagraphOfAnUnorderedList(
            new TextRun(
                sprintf(
                    gettext_provider.gettext("Exported by: %s"),
                    global_export_properties.user_display_name
                )
            )
        ),
        buildParagraphOfAnUnorderedList(
            new ExternalHyperlink({
                child: new TextRun(
                    sprintf(gettext_provider.gettext("Tracker report URL: %s"), report_url)
                ),
                link: report_url,
            })
        ),
        new Paragraph({ children: [new PageBreak()] }),
    ];
}

function buildParagraphOfAnUnorderedList(content: ParagraphChild): Paragraph {
    return new Paragraph({
        children: [content],
        numbering: {
            reference: "unordered-list",
            level: 0,
        },
    });
}

function buildContainersDisplayZone(
    containers: ReadonlyArray<ArtifactContainer>
): ReadonlyArray<XmlComponent> {
    return containers.flatMap((container) => {
        const sub_containers_display_zones = buildContainersDisplayZone(container.containers);
        const field_values_display_zone = [];
        if (container.fields.length > 0) {
            field_values_display_zone.push(buildFieldValuesDisplayZone(container.fields));
        }

        if (sub_containers_display_zones.length === 0 && field_values_display_zone.length === 0) {
            return [];
        }

        return [
            new Paragraph({
                text: container.name,
                heading: HeadingLevel.HEADING_3,
            }),
            ...field_values_display_zone,
            ...sub_containers_display_zones,
        ];
    });
}

function buildReportCriteriaDisplayZone(
    criterion_values: ReadonlyArray<ReportCriterionValue>,
    gettext_provider: GetText,
    datetime_locale_information: DateTimeLocaleInformation
): Table {
    const fields_rows = [
        new TableRow({
            children: [
                buildTableCellHeader(gettext_provider.gettext("Search criteria")),
                buildTableCellHeader(gettext_provider.gettext("Value")),
            ],
            tableHeader: true,
        }),
    ];

    for (const criterion_value of criterion_values) {
        if (criterion_value.criterion_type === "classic") {
            const table_row = new TableRow({
                children: [
                    buildTableCellContent(criterion_value.criterion_name),
                    buildTableCellContent(criterion_value.criterion_value),
                ],
            });

            fields_rows.push(table_row);
        } else if (criterion_value.criterion_type === "date") {
            fields_rows.push(
                buildDateReportCriterionValue(
                    criterion_value,
                    gettext_provider,
                    datetime_locale_information
                )
            );
        }
    }

    return buildTable(fields_rows);
}

function buildDateReportCriterionValue(
    date_criterion_value: DateReportCriterionValue,
    gettext_provider: GetText,
    datetime_locale_information: DateTimeLocaleInformation
): TableRow {
    if (!date_criterion_value.is_advanced) {
        const formatted_to_date = new Date(
            date_criterion_value.criterion_to_value
        ).toLocaleDateString(datetime_locale_information.locale, {
            timeZone: datetime_locale_information.timezone,
        });

        let table_cell_date_criterion_content = "";
        if (date_criterion_value.criterion_value_operator === ">") {
            table_cell_date_criterion_content = sprintf(
                gettext_provider.gettext("After %s"),
                formatted_to_date
            );
        } else if (date_criterion_value.criterion_value_operator === "<") {
            table_cell_date_criterion_content = sprintf(
                gettext_provider.gettext("Before %s"),
                formatted_to_date
            );
        } else {
            table_cell_date_criterion_content = sprintf(
                gettext_provider.gettext("As of %s"),
                formatted_to_date
            );
        }

        return new TableRow({
            children: [
                buildTableCellContent(date_criterion_value.criterion_name),
                buildTableCellContent(table_cell_date_criterion_content),
            ],
        });
    }

    let table_cell_date_criterion_content = "";
    if (date_criterion_value.criterion_from_value && date_criterion_value.criterion_to_value) {
        const formatted_from_date = new Date(
            date_criterion_value.criterion_from_value
        ).toLocaleDateString(datetime_locale_information.locale, {
            timeZone: datetime_locale_information.timezone,
        });

        const formatted_to_date = new Date(
            date_criterion_value.criterion_to_value
        ).toLocaleDateString(datetime_locale_information.locale, {
            timeZone: datetime_locale_information.timezone,
        });

        table_cell_date_criterion_content = sprintf(
            gettext_provider.gettext("After %s and before %s"),
            formatted_from_date,
            formatted_to_date
        );
    } else if (
        date_criterion_value.criterion_from_value &&
        !date_criterion_value.criterion_to_value
    ) {
        const formatted_from_date = new Date(
            date_criterion_value.criterion_from_value
        ).toLocaleDateString(datetime_locale_information.locale, {
            timeZone: datetime_locale_information.timezone,
        });

        table_cell_date_criterion_content = sprintf(
            gettext_provider.gettext("After %s"),
            formatted_from_date
        );
    } else if (
        !date_criterion_value.criterion_from_value &&
        date_criterion_value.criterion_to_value
    ) {
        const formatted_to_date = new Date(
            date_criterion_value.criterion_to_value
        ).toLocaleDateString(datetime_locale_information.locale, {
            timeZone: datetime_locale_information.timezone,
        });

        table_cell_date_criterion_content = sprintf(
            gettext_provider.gettext("Before %s"),
            formatted_to_date
        );
    }

    return new TableRow({
        children: [
            buildTableCellContent(date_criterion_value.criterion_name),
            buildTableCellContent(table_cell_date_criterion_content),
        ],
    });
}

function buildFieldValuesDisplayZone(artifact_values: ReadonlyArray<ArtifactFieldValue>): Table {
    const fields_rows: TableRow[] = [];

    for (const artifact_value of artifact_values) {
        const table_row = new TableRow({
            children: [
                buildTableCellContent(artifact_value.field_name),
                buildTableCellContent(artifact_value.field_value),
            ],
        });

        fields_rows.push(table_row);
    }

    return buildTable(fields_rows);
}

function buildTable(fields_rows: TableRow[]): Table {
    return new Table({
        rows: fields_rows,
        alignment: AlignmentType.CENTER,
        // Some readers such as Google Docs does not deal properly with automatic table column widths.
        // To avoid that we use the same strategy than LibreOffice and set the column widths explicitly.
        // The table is expected to take the whole width, the page width with the margins is ~9638 DXA so
        // we set the size of each columns to (9638 / 2) = 4619 DXA
        width: {
            size: 0,
            type: WidthType.AUTO,
        },
        columnWidths: [4619, 4619],
    });
}

function buildTableCellHeader(name: string): TableCell {
    return new TableCell({
        children: [
            new Paragraph({
                text: name,
                style: "table_header",
            }),
        ],
        verticalAlign: VerticalAlign.CENTER,
        borders: {
            top: {
                size: 0,
                style: BorderStyle.NONE,
                color: "ffffff",
            },
            left: {
                size: 0,
                style: BorderStyle.NONE,
                color: "ffffff",
            },
            right: {
                size: 0,
                style: BorderStyle.NONE,
                color: "ffffff",
            },
        },
    });
}

function buildTableCellContent(content: string): TableCell {
    return new TableCell({
        children: [
            new Paragraph({
                text: content,
                style: "table_content",
            }),
        ],
        verticalAlign: VerticalAlign.CENTER,
        margins: {
            left: 50,
        },
    });
}

function generateMainTitlesNumberingLevelConfiguration(): ILevelsOptions[] {
    const levels: ILevelsOptions[] = [];
    let text_level = "";
    for (let level = 0; level < 10; level++) {
        text_level += `%${level + 1}.`;
        levels.push({
            level: level,
            format: LevelFormat.DECIMAL,
            alignment: AlignmentType.START,
            text: text_level,
        });
    }

    return levels;
}
