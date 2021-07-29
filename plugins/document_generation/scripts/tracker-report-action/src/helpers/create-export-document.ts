/*
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import type { ArtifactReportResponse, ExportDocument } from "../type";

export function createExportDocument(
    report_artifacts: ArtifactReportResponse[],
    tracker_shortname: string
): ExportDocument {
    const artifact_data = [];
    for (const artifact of report_artifacts) {
        const artifact_id = artifact.id;
        const artifact_title = artifact.title;
        const fields_content = [];
        for (const value of artifact.values) {
            if (value.type === "aid" || value.type === "string") {
                fields_content.push({
                    field_name: value.label,
                    field_value: value.value,
                });
            }
        }
        let formatted_title = tracker_shortname + " #" + artifact.id;
        if (artifact_title !== null) {
            formatted_title += " - " + artifact_title;
        }
        artifact_data.push({
            id: artifact_id,
            title: formatted_title,
            fields: fields_content,
        });
    }

    return { artifacts: artifact_data };
}
