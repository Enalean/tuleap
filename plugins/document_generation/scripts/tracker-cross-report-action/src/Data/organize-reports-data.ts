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

import type { ArtifactResponse } from "../../../lib/docx";
import { getReportArtifacts } from "../rest-querier";
import type { ExportSettings } from "../export-document";
import type { OrganizedReportsData } from "../type";

export async function organizeReportsData(
    export_settings: ExportSettings
): Promise<OrganizedReportsData> {
    const report_artifacts_reponses: ArtifactResponse[] = await getReportArtifacts(
        export_settings.first_level.report_id,
        true
    );

    const artifact_representations_map: Map<number, ArtifactResponse> = new Map();
    for (const artifact_response of report_artifacts_reponses) {
        artifact_representations_map.set(artifact_response.id, artifact_response);
    }

    return {
        artifact_representations: artifact_representations_map,
    };
}
