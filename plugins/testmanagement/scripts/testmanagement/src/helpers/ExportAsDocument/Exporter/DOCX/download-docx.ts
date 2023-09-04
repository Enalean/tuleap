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

import { triggerBlobDownload } from "./trigger-blob-download";
import type { XmlComponent } from "docx";
import { File, Packer, PageOrientation, Paragraph, StyleLevel } from "docx";
import type {
    DateTimeLocaleInformation,
    ExportDocument,
    GenericGlobalExportProperties,
    ArtifactFieldValueStepDefinitionEnhancedWithResults,
} from "../../../../type";
import {
    HEADER_LEVEL_SECTION,
    HEADER_STYLE_SECTION_TITLE,
    MAIN_TITLES_NUMBERING_ID,
    properties,
} from "./document-properties";
import { buildMilestoneBacklog } from "./backlog-builder";
import { buildFooter, buildHeader } from "./header-footer";
import { TableOfContentsPrefilled } from "./TableOfContents/table-of-contents";
import { buildTraceabilityMatrix } from "./matrix-builder";
import { buildMilestoneTestPlan } from "./testplan-builder";
import type { GettextProvider } from "@tuleap/gettext";

export async function downloadDocx(
    document: ExportDocument<ArtifactFieldValueStepDefinitionEnhancedWithResults>,
    gettext_provider: GettextProvider,
    global_export_properties: GenericGlobalExportProperties,
    datetime_locale_information: DateTimeLocaleInformation,
    buildCoverPage: (exported_formatted_date: string) => Promise<ReadonlyArray<XmlComponent>>,
): Promise<void> {
    const exported_formatted_date = new Date().toLocaleDateString(
        datetime_locale_information.locale,
        { timeZone: datetime_locale_information.timezone },
    );

    const footers = {
        default: buildFooter(gettext_provider, global_export_properties, exported_formatted_date),
    };

    const headers = {
        default: buildHeader(global_export_properties, document.name),
    };

    const file = new File({
        ...properties,
        sections: [
            {
                children: [...(await buildCoverPage(exported_formatted_date))],
                properties: {
                    titlePage: true,
                },
            },
            {
                headers,
                children: [
                    new Paragraph({
                        text: gettext_provider.gettext("Table of contents"),
                        heading: HEADER_LEVEL_SECTION,
                        numbering: {
                            reference: MAIN_TITLES_NUMBERING_ID,
                            level: 0,
                        },
                    }),
                    new TableOfContentsPrefilled(gettext_provider, global_export_properties, {
                        hyperlink: true,
                        stylesWithLevels: [
                            new StyleLevel(
                                HEADER_STYLE_SECTION_TITLE,
                                Number(HEADER_STYLE_SECTION_TITLE.substr(-1)),
                            ),
                        ],
                    }),
                ],
            },
            {
                headers,
                children: [...buildTraceabilityMatrix(document, gettext_provider)],
                footers,
                properties: {
                    page: {
                        size: {
                            orientation: PageOrientation.LANDSCAPE,
                        },
                    },
                },
            },
            {
                headers,
                children: [
                    ...(await buildMilestoneBacklog(
                        document,
                        gettext_provider,
                        global_export_properties,
                    )),
                ],
                footers,
            },
            {
                headers,
                children: [
                    ...(await buildMilestoneTestPlan(
                        document,
                        gettext_provider,
                        global_export_properties,
                    )),
                ],
                footers,
            },
        ],
    });
    triggerBlobDownload(`${document.name}.docx`, await Packer.toBlob(file));
}
