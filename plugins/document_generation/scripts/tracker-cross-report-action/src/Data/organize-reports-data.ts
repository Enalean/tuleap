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

export async function organizeReportsData(
    export_settings: ExportSettings
): Promise<OrganizedReportsData> {
    const {
        first_level: export_settings_first_level,
        second_level: export_settings_second_level,
        third_level: export_settings_third_level,
    } = export_settings;

    const first_level_report_artifacts_responses: ArtifactResponse[] = await getReportArtifacts(
        export_settings_first_level.report_id,
        true
    );

    const first_level_artifacts_representations_map: Map<number, ArtifactResponse> = new Map();
    for (const artifact_response of first_level_report_artifacts_responses) {
        first_level_artifacts_representations_map.set(artifact_response.id, artifact_response);
    }

    const first_level_linked_artifacts_maps: Map<number, ReadonlyArray<number>> = new Map();
    let organized_reports_data: OrganizedReportsData = {
        first_level: {
            artifact_representations: first_level_artifacts_representations_map,
            tracker_name: export_settings_first_level.tracker_name,
        },
    };

    if (export_settings_second_level) {
        const second_level_report_artifacts_responses: ArtifactResponse[] =
            await getReportArtifacts(export_settings_second_level.report_id, true);

        const linked_artifacts_representations: ArtifactResponse[] = [];
        await limitConcurrencyPool(
            5,
            Array.from(first_level_artifacts_representations_map.keys()),
            async (artifact_id: number): Promise<void> => {
                for (const artifact_link_type of export_settings_first_level.artifact_link_types) {
                    const first_level_linked_artifacts_responses = await getLinkedArtifacts(
                        artifact_id,
                        artifact_link_type
                    );
                    for (const linked_artifacts_response of first_level_linked_artifacts_responses) {
                        if (linked_artifacts_response.collection.length === 0) {
                            continue;
                        }
                        const matching_second_level_representations: ArtifactResponse[] =
                            second_level_report_artifacts_responses.filter(
                                (value: ArtifactResponse) =>
                                    linked_artifacts_response.collection.find(
                                        (element: ArtifactResponse) => value.id === element.id
                                    )
                            );

                        linked_artifacts_representations.push(
                            ...matching_second_level_representations
                        );
                        first_level_linked_artifacts_maps.set(
                            artifact_id,
                            matching_second_level_representations.map(
                                (representation: ArtifactResponse) => {
                                    return representation.id;
                                }
                            )
                        );
                    }
                }

                organized_reports_data.first_level.linked_artifacts =
                    first_level_linked_artifacts_maps;
            }
        );

        const second_level_artifacts_representations_map: Map<number, ArtifactResponse> = new Map();
        for (const artifact_response of linked_artifacts_representations) {
            second_level_artifacts_representations_map.set(artifact_response.id, artifact_response);
        }

        organized_reports_data = {
            ...organized_reports_data,
            second_level: {
                artifact_representations: second_level_artifacts_representations_map,
                tracker_name: export_settings_second_level.tracker_name,
            },
        };

        if (export_settings_third_level) {
            const third_level_report_artifacts_responses: ArtifactResponse[] =
                await getReportArtifacts(export_settings_third_level.report_id, true);

            const second_level_linked_artifacts_maps: Map<
                number,
                ReadonlyArray<number>
            > = new Map();
            const linked_artifacts_representations: ArtifactResponse[] = [];
            await limitConcurrencyPool(
                5,
                Array.from(second_level_artifacts_representations_map.keys()),
                async (artifact_id: number): Promise<void> => {
                    for (const artifact_link_type of export_settings_second_level.artifact_link_types) {
                        const second_level_linked_artifacts_responses = await getLinkedArtifacts(
                            artifact_id,
                            artifact_link_type
                        );
                        for (const linked_artifacts_response of second_level_linked_artifacts_responses) {
                            if (linked_artifacts_response.collection.length === 0) {
                                continue;
                            }
                            const matching_third_level_representations: ArtifactResponse[] =
                                third_level_report_artifacts_responses.filter(
                                    (value: ArtifactResponse) =>
                                        linked_artifacts_response.collection.find(
                                            (element: ArtifactResponse) => value.id === element.id
                                        )
                                );

                            linked_artifacts_representations.push(
                                ...matching_third_level_representations
                            );
                            second_level_linked_artifacts_maps.set(
                                artifact_id,
                                matching_third_level_representations.map(
                                    (representation: ArtifactResponse) => {
                                        return representation.id;
                                    }
                                )
                            );
                        }
                    }

                    if (organized_reports_data.second_level) {
                        organized_reports_data.second_level.linked_artifacts =
                            second_level_linked_artifacts_maps;
                    }
                }
            );

            const third_level_artifacts_representations_map: Map<number, ArtifactResponse> =
                new Map();
            for (const artifact_response of linked_artifacts_representations) {
                third_level_artifacts_representations_map.set(
                    artifact_response.id,
                    artifact_response
                );
            }

            organized_reports_data = {
                ...organized_reports_data,
                third_level: {
                    artifact_representations: third_level_artifacts_representations_map,
                    tracker_name: export_settings_third_level.tracker_name,
                },
            };
        }
    }

    return organized_reports_data;
}
