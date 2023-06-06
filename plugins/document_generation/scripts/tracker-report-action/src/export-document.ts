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

import type { GetText } from "@tuleap/gettext";
import type { ExportDocument, GlobalExportProperties } from "./type";
import { createExportDocument } from "./DocumentBuilder/create-export-document";
import type {
    DateTimeLocaleInformation,
    ArtifactFieldValueStepDefinitionContent,
} from "@tuleap/plugin-docgen-docx/src";

export async function startDownloadExportDocument(
    global_export_properties: GlobalExportProperties,
    gettext_provider: GetText,
    document_exporter: (
        doc: ExportDocument<ArtifactFieldValueStepDefinitionContent>,
        gettext_provider: GetText,
        global_export_properties: GlobalExportProperties,
        datetime_locale_information: DateTimeLocaleInformation
    ) => Promise<void>
): Promise<void> {
    const datetime_locale_information = {
        locale: gettext_provider.locale.replace("_", "-"),
        timezone: global_export_properties.user_timezone,
    };

    const export_document = await createExportDocument(
        global_export_properties.report_id,
        global_export_properties.report_has_changed,
        global_export_properties.report_name,
        global_export_properties.tracker_id,
        global_export_properties.tracker_shortname,
        datetime_locale_information,
        global_export_properties.base_url,
        global_export_properties.artifact_links_types
    );

    await document_exporter(
        export_document,
        gettext_provider,
        global_export_properties,
        datetime_locale_information
    );
}
