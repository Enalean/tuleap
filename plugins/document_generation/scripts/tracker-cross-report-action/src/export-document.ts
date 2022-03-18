/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { GlobalExportProperties, ReportFieldColumn } from "./type";
import type { ArtifactResponse } from "@tuleap/plugin-docgen-docx";
import { getReportArtifacts } from "./rest-querier";

export async function downloadXLSXDocument(
    global_properties: GlobalExportProperties,
    download_document: (
        global_properties: GlobalExportProperties,
        report_field_columns: ReportFieldColumn[],
        report_artifacts: ArtifactResponse[]
    ) => void
): Promise<void> {
    const report_artifacts: ArtifactResponse[] = await getReportArtifacts(
        global_properties.report_id,
        true
    );

    if (report_artifacts.length === 0) {
        return;
    }

    const first_artifact_found: ArtifactResponse = report_artifacts[0];
    const report_field_columns: Array<ReportFieldColumn> = [];

    for (const field_value of first_artifact_found.values) {
        report_field_columns.push({
            field_label: field_value.label,
        });
    }

    download_document(global_properties, report_field_columns, report_artifacts);
}
