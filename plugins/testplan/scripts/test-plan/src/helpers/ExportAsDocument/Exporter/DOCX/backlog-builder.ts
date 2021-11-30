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
import type { BacklogItem, ExportDocument, GlobalExportProperties } from "../../../../type";
import type { VueGettextProvider } from "../../../vue-gettext-provider";
import { Bookmark, Paragraph, Table, TableLayoutType, TableRow, TextRun, WidthType } from "docx";
import {
    HEADER_LEVEL_SECTION_TITLE,
    HEADER_STYLE_SECTION_TITLE,
    MAIN_TITLES_NUMBERING_ID,
} from "./Table/document-properties";
import { buildTableCellContent, TABLE_BORDERS } from "./Table/table-builder";

export function getMilestoneBacklogTitle(
    gettext_provider: VueGettextProvider,
    global_export_properties: GlobalExportProperties
): { id: string; text: string } {
    return {
        id: "backlog",
        text: gettext_provider.$gettextInterpolate(
            gettext_provider.$gettext("%{ milestone_title } backlog"),
            { milestone_title: global_export_properties.milestone_name }
        ),
    };
}

export function buildMilestoneBacklog(
    document: ExportDocument,
    gettext_provider: VueGettextProvider,
    global_export_properties: GlobalExportProperties
): (Paragraph | Table)[] {
    const title = getMilestoneBacklogTitle(gettext_provider, global_export_properties);

    return [
        new Paragraph({
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
        }),
        document.backlog.length === 0
            ? new Paragraph(gettext_provider.$gettext("There is no backlog item yet"))
            : buildBacklogSection(document.backlog),
    ];
}

function buildBacklogSection(backlog: ReadonlyArray<BacklogItem>): Table {
    return new Table({
        width: {
            size: 100,
            type: WidthType.PERCENTAGE,
        },
        borders: TABLE_BORDERS,
        layout: TableLayoutType.AUTOFIT,
        rows: backlog.map(
            (backlog_item) =>
                new TableRow({ children: [buildTableCellContent(new TextRun(backlog_item.label))] })
        ),
    });
}
