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
import type { ILevelsOptions, XmlComponent } from "docx";
import { TabStopPosition, TabStopType } from "docx";
import { ShadingType } from "docx";
import {
    AlignmentType,
    Bookmark,
    BorderStyle,
    convertInchesToTwip,
    ExternalHyperlink,
    File,
    Footer,
    Header,
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
import { loadImage } from "./Image/image-loader";

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
    const exported_formatted_date = new Date().toLocaleDateString(
        datetime_locale_information.locale,
        { timeZone: datetime_locale_information.timezone }
    );
    const footers = {
        default: buildFooter(gettext_provider, global_export_properties, exported_formatted_date),
    };

    const headers = {
        default: buildHeader(global_export_properties),
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
                    id: HeadingLevel.TITLE,
                    name: HeadingLevel.TITLE,
                    basedOn: "Normal",
                    next: "Normal",
                    run: {
                        size: 64,
                        bold: true,
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
                    id: "cover_table_header",
                    name: "cover_table_header",
                    basedOn: "Normal",
                    next: "Normal",
                    run: {
                        size: 20,
                        color: "545454",
                    },
                    paragraph: {
                        alignment: AlignmentType.RIGHT,
                    },
                },
                {
                    id: "cover_table_value",
                    name: "cover_table_value",
                    basedOn: "Normal",
                    next: "Normal",
                    run: {
                        size: 20,
                    },
                    paragraph: {
                        alignment: AlignmentType.LEFT,
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
                    reference: MAIN_TITLES_NUMBERING_ID,
                    levels: generateMainTitlesNumberingLevelConfiguration(),
                },
            ],
        },
        sections: [
            {
                children: [
                    ...(await buildCoverPage(
                        gettext_provider,
                        global_export_properties,
                        exported_formatted_date
                    )),
                ],
            },
            {
                headers,
                children: [...table_of_contents],
            },
            {
                headers,
                children: [
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
    exported_formatted_date: string
): Footer {
    return new Footer({
        children: [
            new Paragraph({
                children: [
                    new TextRun(
                        sprintf(
                            gettext_provider.gettext("Exported on %s by %s"),
                            exported_formatted_date,
                            global_export_properties.user_display_name
                        )
                    ),
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
    exported_formatted_date: string
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
            exported_formatted_date
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
    exported_formatted_date: string
): Table {
    return new Table({
        width: {
            size: 0,
            type: WidthType.AUTO,
        },
        columnWidths: [2000, 7638],
        rows: [
            buildCoverTableRow(gettext_provider.gettext("Platform"), new TextRun(platform_name)),
            buildCoverTableRow(gettext_provider.gettext("Project"), new TextRun(project_name)),
            buildCoverTableRow(gettext_provider.gettext("Tracker"), new TextRun(tracker_name)),
            buildCoverTableRow(gettext_provider.gettext("Exported by"), new TextRun(user_name)),
            buildCoverTableRow(
                gettext_provider.gettext("Exported on"),
                new TextRun(exported_formatted_date)
            ),
            buildCoverTableRow(
                gettext_provider.gettext("Report URL"),
                new ExternalHyperlink({
                    child: new TextRun(report_url),
                    link: report_url,
                })
            ),
        ],
    });
}

function buildCoverTableRow(label: string, value: TextRun | ExternalHyperlink): TableRow {
    const cover_table_header_shading = {
        type: ShadingType.SOLID,
        color: "auto",
        fill: "EEEEEE",
    };

    const margins = {
        top: convertInchesToTwip(0.1),
        bottom: convertInchesToTwip(0.1),
        left: convertInchesToTwip(0.1),
        right: convertInchesToTwip(0.1),
    };

    const no_border = {
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
    };

    return new TableRow({
        children: [
            new TableCell({
                children: [
                    new Paragraph({
                        text: label,
                        style: "cover_table_header",
                    }),
                ],
                borders: no_border,
                margins: margins,
                shading: cover_table_header_shading,
            }),
            new TableCell({
                children: [
                    new Paragraph({
                        children: [value],
                        style: "cover_table_value",
                    }),
                ],
                borders: no_border,
                margins: margins,
            }),
        ],
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
