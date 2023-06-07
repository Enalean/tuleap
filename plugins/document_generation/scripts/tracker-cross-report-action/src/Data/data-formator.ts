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

import { transformFieldValueIntoAFormattedCell } from "./transform-field-value-into-formatted-cell";
import type { ReportCell, TextCell } from "@tuleap/plugin-docgen-xlsx";
import type { ExportSettings } from "../export-document";
import { organizeReportsData } from "./organize-reports-data";
import type {
    OrganizedReportsData,
    TextCellWithMerges,
    ArtifactForCrossReportDocGen,
} from "../type";
import { isFieldTakenIntoAccount } from "./field-type-checker";
import { formatHeaders } from "./headers-formator";

export interface ReportSection {
    readonly headers?: HeadersSection;
    readonly artifacts_rows?: ReadonlyArray<ReadonlyArray<ReportCell>>;
}

export interface HeadersSection {
    readonly tracker_names: ReadonlyArray<TextCellWithMerges>;
    readonly reports_fields_labels: ReadonlyArray<TextCell>;
}

export async function formatData(export_settings: ExportSettings): Promise<ReportSection> {
    const organized_reports_data: OrganizedReportsData = await organizeReportsData(export_settings);

    if (organized_reports_data.first_level.artifact_representations.size === 0) {
        return {};
    }

    const all_artifact_rows: Array<Array<ReportCell>> = [];

    for (const first_level_artifact_representation of organized_reports_data.first_level.artifact_representations.values()) {
        if (
            organized_reports_data.first_level.linked_artifacts.size > 0 &&
            organized_reports_data.second_level
        ) {
            const first_level_artifact_cells = transformArtifactRepresentationAsCells(
                first_level_artifact_representation
            );

            const second_level_linked_artifacts_cells: Map<
                number,
                Array<ReportCell>
            > = buildFollowingLevelLinkedArtifactsCells(
                first_level_artifact_representation.id,
                organized_reports_data.first_level.linked_artifacts,
                organized_reports_data.second_level.artifact_representations
            );

            if (second_level_linked_artifacts_cells.size > 0) {
                for (const second_level_linked_artifact_cells of second_level_linked_artifacts_cells) {
                    const second_level_artifact_id = second_level_linked_artifact_cells[0];
                    const second_level_artifact_cells = second_level_linked_artifact_cells[1];

                    if (
                        organized_reports_data.second_level.linked_artifacts.size > 0 &&
                        organized_reports_data.third_level
                    ) {
                        const third_level_linked_artifacts_cells: Map<
                            number,
                            Array<ReportCell>
                        > = buildFollowingLevelLinkedArtifactsCells(
                            second_level_artifact_id,
                            organized_reports_data.second_level.linked_artifacts,
                            organized_reports_data.third_level.artifact_representations
                        );

                        if (third_level_linked_artifacts_cells.size > 0) {
                            for (const third_level_linked_artifact_cells of third_level_linked_artifacts_cells) {
                                const third_level_artifact_cells =
                                    third_level_linked_artifact_cells[1];
                                all_artifact_rows.push(
                                    first_level_artifact_cells
                                        .concat(second_level_artifact_cells)
                                        .concat(third_level_artifact_cells)
                                );
                            }
                        } else {
                            all_artifact_rows.push(
                                first_level_artifact_cells.concat(second_level_artifact_cells)
                            );
                        }
                    } else {
                        all_artifact_rows.push(
                            first_level_artifact_cells.concat(second_level_artifact_cells)
                        );
                    }
                }
            } else {
                all_artifact_rows.push(first_level_artifact_cells);
            }
        } else {
            all_artifact_rows.push(
                transformArtifactRepresentationAsCells(first_level_artifact_representation)
            );
        }
    }

    return {
        headers: formatHeaders(organized_reports_data),
        artifacts_rows: all_artifact_rows,
    };
}

function buildFollowingLevelLinkedArtifactsCells(
    current_artifact_id: number,
    current_level_linked_artifacts: Map<number, ReadonlyArray<number>>,
    following_level_artifacts_representations: Map<number, ArtifactForCrossReportDocGen>
): Map<number, Array<ReportCell>> {
    const following_level_linked_artifact_ids =
        current_level_linked_artifacts.get(current_artifact_id);
    if (
        following_level_linked_artifact_ids === undefined ||
        following_level_linked_artifact_ids.length === 0
    ) {
        return new Map<number, Array<ReportCell>>();
    }
    const linked_artifacts_cells: Map<number, Array<ReportCell>> = new Map();
    for (const following_level_linked_artifact_id of following_level_linked_artifact_ids) {
        const following_level_artifact_representation =
            following_level_artifacts_representations.get(following_level_linked_artifact_id);
        if (following_level_artifact_representation === undefined) {
            throw new Error(
                "Artifact " +
                    following_level_linked_artifact_id +
                    " representation not found in collection."
            );
        }

        linked_artifacts_cells.set(
            following_level_linked_artifact_id,
            transformArtifactRepresentationAsCells(following_level_artifact_representation)
        );
    }

    return linked_artifacts_cells;
}

function transformArtifactRepresentationAsCells(
    artifact_representation: ArtifactForCrossReportDocGen
): Array<ReportCell> {
    const artifact_value_rows = [];

    for (const field_value of artifact_representation.values) {
        if (!isFieldTakenIntoAccount(field_value)) {
            continue;
        }

        artifact_value_rows.push(transformFieldValueIntoAFormattedCell(field_value));
    }

    return artifact_value_rows;
}
