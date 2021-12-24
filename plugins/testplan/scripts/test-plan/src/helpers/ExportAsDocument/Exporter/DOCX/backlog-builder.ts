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
    GlobalExportProperties,
    ArtifactFieldValueStepDefinitionEnhancedWithResults,
} from "../../../../type";
import type { VueGettextProvider } from "../../../vue-gettext-provider";
import type { Table } from "docx";
import { Bookmark, Paragraph, TextRun } from "docx";
import {
    HEADER_LEVEL_ARTIFACT_TITLE,
    HEADER_LEVEL_SECTION_TITLE,
    HEADER_STYLE_ARTIFACT_TITLE,
    HEADER_STYLE_SECTION_TITLE,
    MAIN_TITLES_NUMBERING_ID,
} from "./document-properties";
import type { FormattedArtifact } from "@tuleap/plugin-docgen-docx";
import { buildListOfArtifactsContent } from "./build-list-of-artifacts-content";

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

export async function buildMilestoneBacklog(
    document: ExportDocument<ArtifactFieldValueStepDefinitionEnhancedWithResults>,
    gettext_provider: VueGettextProvider,
    global_export_properties: GlobalExportProperties
): Promise<(Paragraph | Table)[]> {
    const title = getMilestoneBacklogTitle(gettext_provider, global_export_properties);

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

    if (document.backlog.length === 0) {
        return [
            section_title,
            new Paragraph(gettext_provider.$gettext("There is no backlog item yet")),
        ];
    }

    return [
        section_title,
        ...(await buildBacklogSection(
            document.backlog,
            global_export_properties,
            gettext_provider
        )),
    ];
}

function buildBacklogSection(
    backlog: ReadonlyArray<FormattedArtifact<ArtifactFieldValueStepDefinitionEnhancedWithResults>>,
    global_export_properties: GlobalExportProperties,
    gettext_provider: VueGettextProvider
): Promise<(Paragraph | Table)[]> {
    return buildListOfArtifactsContent(
        gettext_provider,
        backlog,
        HEADER_LEVEL_ARTIFACT_TITLE,
        HEADER_STYLE_ARTIFACT_TITLE
    );
}
