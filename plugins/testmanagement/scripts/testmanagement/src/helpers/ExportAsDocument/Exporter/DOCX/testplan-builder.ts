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
    GenericGlobalExportProperties,
    ArtifactFieldValueStepDefinitionEnhancedWithResults,
} from "../../../../type";
import type { Table } from "docx";
import { Bookmark, Paragraph, TextRun } from "docx";
import {
    HEADER_LEVEL_ARTIFACT_TITLE,
    HEADER_LEVEL_SECTION_TITLE,
    HEADER_STYLE_ARTIFACT_TITLE,
    HEADER_STYLE_SECTION_TITLE,
    MAIN_TITLES_NUMBERING_ID,
} from "./document-properties";
import type { FormattedArtifact } from "@tuleap/plugin-docgen-docx/src";
import { buildListOfArtifactsContent } from "./build-list-of-artifacts-content";
import type { GettextProvider } from "@tuleap/gettext";
import { sprintf } from "sprintf-js";

export function getMilestoneTestPlanTitle(
    gettext_provider: GettextProvider,
    global_export_properties: GenericGlobalExportProperties,
): { id: string; text: string } {
    return {
        id: "testplan",
        text: sprintf(gettext_provider.gettext("%(title)s tests"), {
            title: global_export_properties.title,
        }),
    };
}

export async function buildMilestoneTestPlan(
    document: ExportDocument<ArtifactFieldValueStepDefinitionEnhancedWithResults>,
    gettext_provider: GettextProvider,
    global_export_properties: GenericGlobalExportProperties,
): Promise<(Paragraph | Table)[]> {
    const title = getMilestoneTestPlanTitle(gettext_provider, global_export_properties);

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

    if (document.tests.length === 0) {
        return [section_title, new Paragraph(gettext_provider.gettext("There are no tests."))];
    }

    return [section_title, ...(await buildTestPlanSection(document.tests, gettext_provider))];
}

function buildTestPlanSection(
    tests: ReadonlyArray<FormattedArtifact<ArtifactFieldValueStepDefinitionEnhancedWithResults>>,
    gettext_provider: GettextProvider,
): Promise<(Paragraph | Table)[]> {
    return buildListOfArtifactsContent(
        gettext_provider,
        tests,
        HEADER_LEVEL_ARTIFACT_TITLE,
        HEADER_STYLE_ARTIFACT_TITLE,
    );
}
