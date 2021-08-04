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

import type { GetText } from "../../../../../src/scripts/tuleap/gettext/gettext-init";
import type { ArtifactReportResponse, ExportDocument, GlobalExportProperties } from "./type";
import { recursiveGet } from "@tuleap/tlp-fetch";
import { createExportDocument } from "./helpers/create-export-document";

export async function startDownloadExportDocument(
    global_export_properties: GlobalExportProperties,
    gettext_provider: GetText,
    document_exporter: (
        doc: ExportDocument,
        gettext_provider: GetText,
        global_export_properties: GlobalExportProperties
    ) => Promise<void>
): Promise<void> {
    const report_artifacts: ArtifactReportResponse[] = await recursiveGet(
        `/api/v1/tracker_reports/${encodeURIComponent(
            global_export_properties.report_id
        )}/artifacts`,
        {
            params: {
                values: "all",
                with_unsaved_changes: global_export_properties.report_has_changed,
                limit: 50,
            },
        }
    );

    const export_document = createExportDocument(
        report_artifacts,
        global_export_properties.report_name,
        global_export_properties.tracker_shortname
    );

    await document_exporter(export_document, gettext_provider, global_export_properties);
}
