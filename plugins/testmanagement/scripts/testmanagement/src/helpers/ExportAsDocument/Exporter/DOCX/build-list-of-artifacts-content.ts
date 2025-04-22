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

import type { XmlComponent, Table } from "docx";
import {
    Bookmark,
    ExternalHyperlink,
    HeadingLevel,
    InternalHyperlink,
    PageBreak,
    Paragraph,
    ShadingType,
    TableCell,
    TableRow,
    TextRun,
} from "docx";
import type {
    ArtifactContainer,
    ArtifactFieldShortValue,
    ArtifactFieldValue,
    ArtifactFieldValueStepDefinitionEnhanced,
    FormattedArtifact,
    ReadonlyArrayWithAtLeastOneElement,
} from "@tuleap/plugin-docgen-docx";
import {
    buildLabelValueTable,
    buildTable,
    getAnchorToArtifactContent,
    HTML_ORDERED_LIST_NUMBERING,
    HTML_UNORDERED_LIST_NUMBERING,
    TABLE_MARGINS,
    transformLargeContentIntoParagraphs,
} from "@tuleap/plugin-docgen-docx";
import { sprintf } from "sprintf-js";
import { getInternationalizedTestStatus } from "./internationalize-test-status";
import type { ArtifactFieldValueStepDefinitionEnhancedWithResults } from "../../../../type";
import { buildCellContentResult } from "./Table/table-builder";
import type { GettextProvider } from "@tuleap/gettext";

const TABLE_LABEL_SHADING = {
    val: ShadingType.CLEAR,
    color: "auto",
    fill: "EEEEEE",
};

export async function buildListOfArtifactsContent(
    gettext_provider: GettextProvider,
    artifacts: ReadonlyArray<
        FormattedArtifact<ArtifactFieldValueStepDefinitionEnhancedWithResults>
    >,
    heading: HeadingLevel,
    style: string,
): Promise<(Paragraph | Table)[]> {
    const artifacts_content = [];
    for (const artifact of artifacts) {
        artifacts_content.push(
            new Paragraph({
                heading,
                style,
                children: [
                    new Bookmark({
                        id: getAnchorToArtifactContent(artifact),
                        children: [new TextRun(artifact.title)],
                    }),
                ],
            }),
            ...(await buildFieldValuesDisplayZone(artifact, artifact.fields, gettext_provider)),
            ...(await buildContainersDisplayZone(artifact, artifact.containers, gettext_provider)),
            new Paragraph({ children: [new PageBreak()] }),
        );
    }

    return artifacts_content;
}

async function buildContainersDisplayZone(
    artifact: FormattedArtifact<ArtifactFieldValueStepDefinitionEnhancedWithResults>,
    containers: ReadonlyArray<
        ArtifactContainer<ArtifactFieldValueStepDefinitionEnhancedWithResults>
    >,
    gettext_provider: GettextProvider,
): Promise<XmlComponent[]> {
    const xml_components_promises = containers.map(async (container): Promise<XmlComponent[]> => {
        const sub_containers_display_zones = await buildContainersDisplayZone(
            artifact,
            container.containers,
            gettext_provider,
        );
        const field_values_display_zone: XmlComponent[] = [];
        field_values_display_zone.push(
            ...(await buildFieldValuesDisplayZone(artifact, container.fields, gettext_provider)),
        );

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

    const xml_components: XmlComponent[] = [];
    for (const xml_components_promise of xml_components_promises) {
        xml_components.push(...(await xml_components_promise));
    }

    return xml_components;
}

async function buildFieldValuesDisplayZone(
    artifact: FormattedArtifact<ArtifactFieldValueStepDefinitionEnhancedWithResults>,
    artifact_values: ReadonlyArray<
        ArtifactFieldValue<ArtifactFieldValueStepDefinitionEnhancedWithResults>
    >,
    gettext_provider: GettextProvider,
): Promise<XmlComponent[]> {
    const short_fields: ArtifactFieldShortValue[] = [];
    const display_zone_long_fields: XmlComponent[] = [];

    for (const field of artifact_values) {
        switch (field.content_length) {
            case "short":
                short_fields.push(field);
                break;
            case "long":
                display_zone_long_fields.push(
                    new Paragraph({
                        heading: HeadingLevel.HEADING_4,
                        children: [new TextRun(field.field_name)],
                    }),
                    ...(await buildParagraphsFromContent(field.field_value, field.content_format, [
                        HeadingLevel.HEADING_5,
                        HeadingLevel.HEADING_6,
                    ])),
                );
                break;
            case "blockttmstepdef":
                display_zone_long_fields.push(
                    new Paragraph({
                        heading: HeadingLevel.HEADING_4,
                        children: [new TextRun(field.field_name)],
                    }),
                );
                for (const step of field.steps) {
                    display_zone_long_fields.push(
                        new Paragraph({
                            heading: HeadingLevel.HEADING_5,
                            children: [
                                new TextRun(
                                    sprintf(gettext_provider.gettext("Step %d"), step.rank),
                                ),
                            ],
                        }),
                        ...(await buildStepDefinitionParagraphs(step, gettext_provider, false)),
                    );
                }
                break;
            case "blockttmstepdefenhanced":
                display_zone_long_fields.push(
                    new Paragraph({
                        heading: HeadingLevel.HEADING_4,
                        children: [new TextRun(field.field_name)],
                    }),
                );
                for (const step of field.steps) {
                    display_zone_long_fields.push(
                        new Paragraph({
                            heading: HeadingLevel.HEADING_5,
                            children: [
                                new TextRun(
                                    sprintf(gettext_provider.gettext("Step %d"), step.rank),
                                ),
                            ],
                        }),
                        ...(await buildStepDefinitionParagraphs(step, gettext_provider, true)),
                    );
                }
                display_zone_long_fields.push(
                    ...(await buildStepDefinitionTestResultParagraphs(field, gettext_provider)),
                );
                break;
            case "blockttmstepexec": {
                display_zone_long_fields.push(
                    new Paragraph({
                        heading: HeadingLevel.HEADING_4,
                        children: [new TextRun(field.field_name)],
                    }),
                );

                if (field.steps.length === 0) {
                    break;
                }

                const step_exec_table_rows: TableRow[] = [];
                step_exec_table_rows.push(
                    new TableRow({
                        children: [
                            buildTableCellHeaderLabel(gettext_provider.gettext("Step")),
                            buildTableCellHeaderValue(gettext_provider.gettext("Status")),
                        ],
                        tableHeader: true,
                    }),
                );
                let step_number = 1;
                for (const step_status of field.steps_values) {
                    step_exec_table_rows.push(
                        new TableRow({
                            children: [
                                buildTableCellLabel(
                                    sprintf(gettext_provider.gettext("Step %d"), step_number),
                                ),
                                buildTableCellContent(
                                    new TextRun(
                                        getInternationalizedTestStatus(
                                            gettext_provider,
                                            step_status,
                                        ),
                                    ),
                                ),
                            ],
                            tableHeader: true,
                        }),
                    );
                    step_number++;
                }
                display_zone_long_fields.push(buildLabelValueTable(step_exec_table_rows));
                for (const step of field.steps) {
                    display_zone_long_fields.push(
                        new Paragraph({
                            heading: HeadingLevel.HEADING_5,
                            children: [
                                new TextRun(
                                    sprintf(gettext_provider.gettext("Step %d"), step.rank),
                                ),
                            ],
                        }),
                        new Paragraph({
                            children: [
                                new TextRun(
                                    sprintf(
                                        gettext_provider.gettext("Status: %s"),
                                        getInternationalizedTestStatus(
                                            gettext_provider,
                                            step.status,
                                        ),
                                    ),
                                ),
                            ],
                        }),
                        ...(await buildStepDefinitionParagraphs(step, gettext_provider, false)),
                    );
                }
                break;
            }
            case "artlinktable": {
                display_zone_long_fields.push(
                    new Paragraph({
                        heading: HeadingLevel.HEADING_4,
                        children: [new TextRun(field.field_name)],
                    }),
                );

                if (field.links.length > 0) {
                    display_zone_long_fields.push(
                        new Paragraph({
                            heading: HeadingLevel.HEADING_5,
                            children: [
                                new TextRun(
                                    sprintf(
                                        gettext_provider.gettext("Artifacts referenced by “%s”"),
                                        artifact.short_title,
                                    ),
                                ),
                            ],
                        }),
                    );
                    const links_table_rows: TableRow[] = [
                        new TableRow({
                            children: [
                                buildTableCellHeaderValue(gettext_provider.gettext("Artifact ID")),
                                buildTableCellHeaderValue(gettext_provider.gettext("Title")),
                                buildTableCellHeaderValue(gettext_provider.gettext("Link type")),
                            ],
                            tableHeader: true,
                        }),
                    ];
                    for (const link of field.links) {
                        links_table_rows.push(
                            new TableRow({
                                children: [
                                    buildTableCellContent(
                                        link.is_linked_artifact_part_of_document
                                            ? new InternalHyperlink({
                                                  children: [
                                                      new TextRun(link.artifact_id.toString()),
                                                  ],
                                                  anchor: getAnchorToArtifactContent({
                                                      id: link.artifact_id,
                                                  }),
                                              })
                                            : new TextRun(link.artifact_id.toString()),
                                    ),
                                    buildTableCellContent(
                                        link.html_url
                                            ? new ExternalHyperlink({
                                                  children: [
                                                      new TextRun({
                                                          text: link.title,
                                                          style: "Hyperlink",
                                                      }),
                                                  ],
                                                  link: link.html_url.toString(),
                                              })
                                            : new TextRun(link.title),
                                    ),
                                    buildTableCellContent(new TextRun(link.type)),
                                ],
                            }),
                        );
                    }
                    display_zone_long_fields.push(buildTable(links_table_rows));
                }

                if (field.reverse_links.length > 0) {
                    display_zone_long_fields.push(
                        new Paragraph({
                            heading: HeadingLevel.HEADING_5,
                            children: [
                                new TextRun(
                                    sprintf(
                                        gettext_provider.gettext("Artifacts that reference “%s”"),
                                        artifact.short_title,
                                    ),
                                ),
                            ],
                        }),
                    );
                    const reverse_links_table_rows: TableRow[] = [
                        new TableRow({
                            children: [
                                buildTableCellHeaderValue(gettext_provider.gettext("Artifact ID")),
                                buildTableCellHeaderValue(gettext_provider.gettext("Title")),
                                buildTableCellHeaderValue(gettext_provider.gettext("Link type")),
                            ],
                            tableHeader: true,
                        }),
                    ];
                    for (const reverse_link of field.reverse_links) {
                        reverse_links_table_rows.push(
                            new TableRow({
                                children: [
                                    buildTableCellContent(
                                        reverse_link.is_linked_artifact_part_of_document
                                            ? new InternalHyperlink({
                                                  children: [
                                                      new TextRun(
                                                          reverse_link.artifact_id.toString(),
                                                      ),
                                                  ],
                                                  anchor: getAnchorToArtifactContent({
                                                      id: reverse_link.artifact_id,
                                                  }),
                                              })
                                            : new TextRun(reverse_link.artifact_id.toString()),
                                    ),
                                    buildTableCellContent(
                                        reverse_link.html_url
                                            ? new ExternalHyperlink({
                                                  children: [
                                                      new TextRun({
                                                          text: reverse_link.title,
                                                          style: "Hyperlink",
                                                      }),
                                                  ],
                                                  link: reverse_link.html_url.toString(),
                                              })
                                            : new TextRun(reverse_link.title),
                                    ),
                                    buildTableCellContent(new TextRun(reverse_link.type)),
                                ],
                            }),
                        );
                    }
                    display_zone_long_fields.push(buildTable(reverse_links_table_rows));
                }

                break;
            }
            default:
                ((value: never): never => {
                    throw new Error("Should never happen, all fields must be handled " + value);
                })(field);
        }
    }

    const display_zone: XmlComponent[] = [];

    if (short_fields.length > 0) {
        display_zone.push(buildShortFieldValuesDisplayZone(short_fields));
    }

    display_zone.push(...display_zone_long_fields);

    return display_zone;
}

function buildShortFieldValuesDisplayZone(
    artifact_values: ReadonlyArray<ArtifactFieldShortValue>,
): Table {
    const fields_rows: TableRow[] = [];

    for (const artifact_value of artifact_values) {
        if (artifact_value.value_type === "links") {
            const links_value: ExternalHyperlink[] = [];
            for (const link of artifact_value.field_value) {
                links_value.push(
                    new ExternalHyperlink({
                        children: [new TextRun({ text: link.link_label, style: "Hyperlink" })],
                        link: link.link_url,
                    }),
                );
            }

            const table_row = new TableRow({
                children: [
                    buildTableCellLabel(artifact_value.field_name),
                    buildTableCellLinksContent(links_value),
                ],
            });
            fields_rows.push(table_row);
        } else {
            const table_row = new TableRow({
                children: [
                    buildTableCellLabel(artifact_value.field_name),
                    buildTableCellContent(new TextRun(artifact_value.field_value)),
                ],
            });
            fields_rows.push(table_row);
        }
    }

    return buildLabelValueTable(fields_rows);
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

function buildTableCellLinksContent(links: Array<ExternalHyperlink>): TableCell {
    const content = links.flatMap((l) => [l, new TextRun(", ")]);
    content.splice(-1);

    return new TableCell({
        children: [
            new Paragraph({
                children: content,
                style: "table_value",
            }),
        ],
        margins: TABLE_MARGINS,
    });
}

async function buildStepDefinitionParagraphs(
    step: ArtifactFieldValueStepDefinitionEnhanced,
    gettext_provider: GettextProvider,
    must_display_enhanced_data: boolean,
): Promise<(Table | Paragraph)[]> {
    const paragraphs: (Table | Paragraph)[] = [];

    if (must_display_enhanced_data) {
        paragraphs.push(
            buildLabelValueTable([
                new TableRow({
                    children: [
                        buildTableCellLabel(gettext_provider.gettext("Status")),
                        buildCellContentResult(step.status, gettext_provider, 1),
                    ],
                }),
            ]),
        );
    }

    paragraphs.push(
        new Paragraph({
            heading: HeadingLevel.HEADING_6,
            children: [new TextRun(gettext_provider.gettext("Description"))],
        }),
        ...(await buildParagraphsFromContent(step.description, step.description_format, [
            HeadingLevel.HEADING_6,
        ])),
        new Paragraph({
            heading: HeadingLevel.HEADING_6,
            children: [new TextRun(gettext_provider.gettext("Expected results"))],
        }),
        ...(await buildParagraphsFromContent(step.expected_results, step.expected_results_format, [
            HeadingLevel.HEADING_6,
        ])),
    );

    return paragraphs;
}

async function buildStepDefinitionTestResultParagraphs(
    field: ArtifactFieldValueStepDefinitionEnhancedWithResults,
    gettext_provider: GettextProvider,
): Promise<(Table | Paragraph)[]> {
    const paragraphs: (Table | Paragraph)[] = [];

    paragraphs.push(
        new Paragraph({
            heading: HeadingLevel.HEADING_5,
            children: [new TextRun(gettext_provider.gettext("Test Results"))],
        }),
    );
    if (field.last_execution_user !== "" && field.last_execution_date !== "") {
        paragraphs.push(
            buildLabelValueTable([
                new TableRow({
                    children: [
                        buildTableCellLabel(gettext_provider.gettext("Executed By")),
                        buildTableCellContent(new TextRun(field.last_execution_user)),
                    ],
                }),
                new TableRow({
                    children: [
                        buildTableCellLabel(gettext_provider.gettext("Executed On")),
                        buildTableCellContent(
                            new TextRun(new Date(field.last_execution_date).toDateString()),
                        ),
                    ],
                }),
            ]),
        );
    }
    paragraphs.push(
        new Paragraph({
            heading: HeadingLevel.HEADING_6,
            children: [new TextRun(gettext_provider.gettext("Status"))],
        }),
        buildLabelValueTable([
            new TableRow({
                children: [buildCellContentResult(field.status, gettext_provider, 1)],
            }),
        ]),
        ...(field.steps.length > 0
            ? [
                  new Paragraph({ children: [new TextRun("")] }),
                  buildLabelValueTable([
                      new TableRow({
                          children: [
                              buildTableCellHeaderLabel(gettext_provider.gettext("Step")),
                              buildTableCellHeaderValue(gettext_provider.gettext("Status")),
                          ],
                          tableHeader: true,
                      }),
                      ...field.steps.map(
                          ({ status }, index) =>
                              new TableRow({
                                  children: [
                                      buildTableCellLabel(
                                          sprintf(gettext_provider.gettext("Step %d"), index + 1),
                                      ),
                                      buildCellContentResult(status, gettext_provider, 1),
                                  ],
                              }),
                      ),
                  ]),
              ]
            : []),
    );

    if (field.attachments.length > 0) {
        const links = field.attachments.flatMap((attachment) => [
            new ExternalHyperlink({
                children: [
                    new TextRun({
                        text: attachment.filename,
                        style: "Hyperlink",
                    }),
                ],
                link: attachment.html_url,
            }),
            new TextRun(", "),
        ]);
        links.splice(-1);

        paragraphs.push(
            new Paragraph({
                heading: HeadingLevel.HEADING_6,
                children: [new TextRun(gettext_provider.gettext("Attachments"))],
            }),
            new Paragraph({
                children: links,
            }),
        );
    }

    if (field.linked_bugs.length > 0) {
        paragraphs.push(
            new Paragraph({
                heading: HeadingLevel.HEADING_6,
                children: [new TextRun(gettext_provider.gettext("Linked bugs"))],
            }),
        );
        for (const bug of field.linked_bugs) {
            paragraphs.push(
                new Paragraph({
                    children: [
                        new ExternalHyperlink({
                            children: [
                                new TextRun({
                                    text: bug.xref + (bug.title ? ": " + bug.title : ""),
                                    style: "Hyperlink",
                                }),
                            ],
                            link: bug.html_url,
                        }),
                    ],
                    bullet: {
                        level: 0,
                    },
                }),
            );
        }
    }

    if (field.result.trim()) {
        paragraphs.push(
            new Paragraph({
                heading: HeadingLevel.HEADING_6,
                children: [new TextRun(gettext_provider.gettext("Comment"))],
            }),
        );

        (await buildParagraphsFromContent(field.result, "html", [HeadingLevel.HEADING_6])).forEach(
            (paragraph) => paragraphs.push(paragraph),
        );
    }

    return paragraphs;
}

async function buildParagraphsFromContent(
    content: string,
    format: "plaintext" | "html",
    title_levels: ReadonlyArrayWithAtLeastOneElement<HeadingLevel>,
): Promise<Paragraph[]> {
    const paragraphs: Paragraph[] = [];

    paragraphs.push(
        ...(await transformLargeContentIntoParagraphs(content, format, {
            ordered_title_levels: title_levels,
            unordered_list_reference: HTML_UNORDERED_LIST_NUMBERING.reference,
            ordered_list_reference: HTML_ORDERED_LIST_NUMBERING.reference,
            monospace_font: "Courier New",
        })),
    );

    return paragraphs;
}
