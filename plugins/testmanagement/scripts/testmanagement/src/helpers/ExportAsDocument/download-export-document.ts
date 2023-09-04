/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { createExportReport } from "./Reporter/report-creator";
import type {
    Campaign,
    ExportDocument,
    GlobalExportProperties,
    DateTimeLocaleInformation,
    ArtifactFieldValueStepDefinitionEnhancedWithResults,
} from "../../type";
import type { XmlComponent } from "docx";
import { buildCoverPage } from "./Exporter/DOCX/cover-builder";
import type { GettextProvider } from "@tuleap/gettext";
import { initGettextForDocumentExport } from "./init-gettext-for-document-export";

export async function downloadExportDocument(
    global_export_properties: GlobalExportProperties,
    download_document: (
        document: ExportDocument<ArtifactFieldValueStepDefinitionEnhancedWithResults>,
        gettext_provider: GettextProvider,
        global_export_properties: GlobalExportProperties,
        datetime_locale_information: DateTimeLocaleInformation,
        buildCoverPage: (exported_formatted_date: string) => Promise<ReadonlyArray<XmlComponent>>,
    ) => void,
    campaign: Campaign,
): Promise<void> {
    const datetime_locale_information: DateTimeLocaleInformation = {
        locale: global_export_properties.user_locale.replace("_", "-"),
        timezone: global_export_properties.user_timezone,
    };

    const gettext_provider = initGettextForDocumentExport(global_export_properties.user_locale);

    const report = await createExportReport(
        gettext_provider,
        global_export_properties,
        campaign,
        datetime_locale_information,
    );

    download_document(
        report,
        gettext_provider,
        global_export_properties,
        datetime_locale_information,
        (exported_formatted_date: string): Promise<ReadonlyArray<XmlComponent>> =>
            buildCoverPage(gettext_provider, global_export_properties, exported_formatted_date),
    );
}
