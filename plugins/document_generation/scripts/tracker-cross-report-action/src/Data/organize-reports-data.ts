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

import { getLinkedArtifacts, getReportArtifacts } from "../rest-querier";
import type { ExportSettings } from "../export-document";
import type {
    OrganizedReportsData,
    OrganizedReportDataLevel,
    ArtifactForCrossReportDocGen,
} from "../type";
import { limitConcurrencyPool } from "@tuleap/concurrency-limit-pool";

export async function organizeReportsData(
    export_settings: ExportSettings
): Promise<OrganizedReportsData> {
    const {
        first_level: export_settings_first_level,
        second_level: export_settings_second_level,
        third_level: export_settings_third_level,
    } = export_settings;

    const first_level_report_artifacts_responses: ArtifactForCrossReportDocGen[] =
        await getReportArtifacts(
            export_settings_first_level.report_id,
            true,
            export_settings_first_level.table_renderer_id
        );

    const first_level_artifacts_representations_map: Map<number, ArtifactForCrossReportDocGen> =
        new Map();
    for (const artifact_response of first_level_report_artifacts_responses) {
        first_level_artifacts_representations_map.set(artifact_response.id, artifact_response);
    }

    const first_level_organized_data: OrganizedReportDataLevel = {
        artifact_representations: first_level_artifacts_representations_map,
        tracker_name: export_settings_first_level.tracker_name,
        linked_artifacts: new Map<number, ReadonlyArray<number>>(),
    };
    let organized_reports_data: OrganizedReportsData = {
        first_level: first_level_organized_data,
    };

    if (export_settings_second_level) {
        const second_level_organized_data: OrganizedReportDataLevel = {
            artifact_representations: new Map<number, ArtifactForCrossReportDocGen>(),
            tracker_name: export_settings_second_level.tracker_name,
            linked_artifacts: new Map<number, ReadonlyArray<number>>(),
        };

        await retrieveLinkedArtifactsData(
            export_settings_second_level.report_id,
            Array.from(first_level_organized_data.artifact_representations.keys()),
            export_settings_first_level.artifact_link_types,
            second_level_organized_data.artifact_representations,
            first_level_organized_data.linked_artifacts
        );

        organized_reports_data = {
            first_level: first_level_organized_data,
            second_level: second_level_organized_data,
        };

        if (export_settings_third_level) {
            const third_level_organized_data: Omit<OrganizedReportDataLevel, "linked_artifacts"> = {
                artifact_representations: new Map<number, ArtifactForCrossReportDocGen>(),
                tracker_name: export_settings_third_level.tracker_name,
            };

            await retrieveLinkedArtifactsData(
                export_settings_third_level.report_id,
                Array.from(second_level_organized_data.artifact_representations.keys()),
                export_settings_second_level.artifact_link_types,
                third_level_organized_data.artifact_representations,
                second_level_organized_data.linked_artifacts
            );

            organized_reports_data = {
                first_level: first_level_organized_data,
                second_level: second_level_organized_data,
                third_level: third_level_organized_data,
            };
        }
    }

    return organized_reports_data;
}

async function retrieveLinkedArtifactsData(
    report_id: number,
    artifact_ids: ReadonlyArray<number>,
    artifact_link_types: ReadonlyArray<string>,
    linked_artifacts_representations: Map<number, ArtifactForCrossReportDocGen>,
    linked_artifacts_maps: Map<number, ReadonlyArray<number>>
): Promise<void> {
    const following_level_report_artifacts_responses: ArtifactForCrossReportDocGen[] =
        await getReportArtifacts(report_id, true, undefined);

    await limitConcurrencyPool(5, artifact_ids, async (artifact_id: number): Promise<void> => {
        for (const artifact_link_type of artifact_link_types) {
            const first_level_linked_artifacts_responses = await getLinkedArtifacts(
                artifact_id,
                artifact_link_type
            );
            for (const linked_artifacts_response of first_level_linked_artifacts_responses) {
                if (linked_artifacts_response.collection.length === 0) {
                    continue;
                }
                const matching_following_level_representations: ArtifactForCrossReportDocGen[] =
                    following_level_report_artifacts_responses.filter(
                        (value: ArtifactForCrossReportDocGen) =>
                            linked_artifacts_response.collection.find(
                                (element: ArtifactForCrossReportDocGen) => value.id === element.id
                            )
                    );

                for (const matching_representation of matching_following_level_representations) {
                    linked_artifacts_representations.set(
                        matching_representation.id,
                        matching_representation
                    );
                }
                const linked_artifacts_maps_for_artifact = linked_artifacts_maps.get(artifact_id);
                if (linked_artifacts_maps_for_artifact === undefined) {
                    linked_artifacts_maps.set(
                        artifact_id,
                        matching_following_level_representations.map(
                            (representation: ArtifactForCrossReportDocGen) => {
                                return representation.id;
                            }
                        )
                    );
                } else {
                    linked_artifacts_maps.set(
                        artifact_id,
                        matching_following_level_representations
                            .map((representation: ArtifactForCrossReportDocGen) => {
                                return representation.id;
                            })
                            .concat(linked_artifacts_maps_for_artifact)
                    );
                }
            }
        }
    });
}
