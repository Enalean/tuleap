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

import type { ArtifactResponse } from "@tuleap/plugin-docgen-docx";
import { getLinkedArtifacts, getReportArtifacts } from "../rest-querier";
import type { ExportSettings } from "../export-document";
import type { OrganizedReportsData } from "../type";
import { limitConcurrencyPool } from "@tuleap/concurrency-limit-pool";
import { extractFieldsLabels } from "./report-fields-labels-extractor";

export async function organizeReportsData(
    export_settings: ExportSettings
): Promise<OrganizedReportsData> {
    const first_level_report_artifacts_responses: ArtifactResponse[] = await getReportArtifacts(
        export_settings.first_level.report_id,
        true
    );

    const first_level_artifacts_representations_map: Map<number, ArtifactResponse> = new Map();
    for (const artifact_response of first_level_report_artifacts_responses) {
        first_level_artifacts_representations_map.set(artifact_response.id, artifact_response);
    }

    let organized_reports_data: OrganizedReportsData = {
        first_level: {
            artifact_representations: first_level_artifacts_representations_map,
            tracker_name: export_settings.first_level.tracker_name,
            report_fields_labels: extractFieldsLabels(first_level_artifacts_representations_map),
        },
    };

    if (export_settings.second_level) {
        const second_level_report_artifacts_responses: ArtifactResponse[] =
            await getReportArtifacts(export_settings.second_level.report_id, true);

        const linked_artifacts_representations: ArtifactResponse[] = [];
        await limitConcurrencyPool(
            5,
            Array.from(first_level_artifacts_representations_map.keys()),
            async (artifact_id: number): Promise<void> => {
                for (const artifact_link_type of export_settings.first_level.artifact_link_types) {
                    const linked_artifacts_responses = await getLinkedArtifacts(
                        artifact_id,
                        artifact_link_type
                    );
                    for (const linked_artifacts_response of linked_artifacts_responses) {
                        if (linked_artifacts_response.collection.length === 0) {
                            continue;
                        }
                        linked_artifacts_representations.push(
                            ...linked_artifacts_response.collection
                        );
                    }
                }
            }
        );

        const matching_second_level_representations: ArtifactResponse[] =
            second_level_report_artifacts_responses.filter((value: ArtifactResponse) =>
                linked_artifacts_representations.find(
                    (element: ArtifactResponse) => value.id === element.id
                )
            );

        const second_level_artifacts_representations_map: Map<number, ArtifactResponse> = new Map();
        for (const artifact_response of matching_second_level_representations) {
            second_level_artifacts_representations_map.set(artifact_response.id, artifact_response);
        }

        organized_reports_data = {
            ...organized_reports_data,
            second_level: {
                artifact_representations: second_level_artifacts_representations_map,
                tracker_name: export_settings.second_level.tracker_name,
                report_fields_labels: extractFieldsLabels(
                    second_level_artifacts_representations_map
                ),
            },
        };
    }

    return organized_reports_data;
}
