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

import type { VueGettextProvider } from "../../../vue-gettext-provider";
import type { GlobalExportProperties } from "../../../../type";
import type { XmlComponent } from "docx";
import {
    AlignmentType,
    ExternalHyperlink,
    HeadingLevel,
    PageBreak,
    Paragraph,
    Table,
    TableLayoutType,
    TableRow,
    TextRun,
    WidthType,
} from "docx";
import { loadImage } from "@tuleap/plugin-docgen-docx";
import {
    buildTableCellContent,
    buildTableCellLabel,
    TABLE_BORDERS,
} from "@tuleap/plugin-testmanagement/scripts/testmanagement/src/helpers/ExportAsDocument/Exporter/DOCX/Table/table-builder";
import { buildMilestoneTitle } from "./cover-milestone-title-builder";

export async function buildCoverPage(
    gettext_provider: VueGettextProvider,
    global_export_properties: GlobalExportProperties,
    exported_formatted_date: string,
): Promise<ReadonlyArray<XmlComponent>> {
    const {
        platform_name,
        platform_logo_url,
        project_name,
        milestone_name,
        parent_milestone_name,
        milestone_url,
        user_display_name,
    } = global_export_properties;

    return [
        new Paragraph({
            children: [await loadImage(platform_logo_url)],
            alignment: AlignmentType.CENTER,
        }),
        new Paragraph({
            text: milestone_name,
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
            milestone_name,
            parent_milestone_name,
            milestone_url,
            user_display_name,
            exported_formatted_date,
        ),
        new Paragraph({ children: [new PageBreak()] }),
    ];
}

function buildCoverTable(
    gettext_provider: VueGettextProvider,
    platform_name: string,
    project_name: string,
    milestone_name: string,
    parent_milestone_name: string,
    milestone_url: string,
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
            buildCoverTableRow(gettext_provider.$gettext("Platform"), new TextRun(platform_name)),
            buildCoverTableRow(gettext_provider.$gettext("Project"), new TextRun(project_name)),
            buildCoverTableRow(
                gettext_provider.$gettext("Milestone"),
                new TextRun(buildMilestoneTitle(milestone_name, parent_milestone_name)),
            ),
            buildCoverTableRow(gettext_provider.$gettext("Exported by"), new TextRun(user_name)),
            buildCoverTableRow(
                gettext_provider.$gettext("Exported on"),
                new TextRun(exported_formatted_date),
            ),
            buildCoverTableRow(
                gettext_provider.$gettext("Milestone URL"),
                new ExternalHyperlink({
                    children: [
                        new TextRun({
                            text: milestone_url,
                            style: "Hyperlink",
                        }),
                    ],
                    link: milestone_url,
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
