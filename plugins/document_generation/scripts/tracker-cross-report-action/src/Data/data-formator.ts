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
import type { ReportCell } from "@tuleap/plugin-docgen-xlsx";
import type { TextCell } from "@tuleap/plugin-docgen-xlsx";
import type { ExportSettings } from "../export-document";
import { organizeReportsData } from "./organize-reports-data";
import type { OrganizedReportsData } from "../type";
import { isFieldTakenIntoAccount } from "./field-type-checker";
import { formatHeaders } from "./headers-formator";
import type { TextCellWithMerges } from "../type";
import type { ArtifactResponse } from "@tuleap/plugin-docgen-docx";

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

    if (
        organized_reports_data.first_level.linked_artifacts &&
        organized_reports_data.second_level
    ) {
        for (const linked_artifacts_map of organized_reports_data.first_level.linked_artifacts) {
            const first_level_artifact_id: number = linked_artifacts_map[0];
            const first_level_artifact_representation =
                organized_reports_data.first_level.artifact_representations.get(
                    first_level_artifact_id
                );
            if (first_level_artifact_representation === undefined) {
                throw new Error(
                    "Artifact " +
                        first_level_artifact_id +
                        " representation not found in collection."
                );
            }
            const first_level_artifact_cells = transformArtifactRepresentationAsCells(
                first_level_artifact_representation
            );

            const second_level_linked_artifact_ids: ReadonlyArray<number> = linked_artifacts_map[1];
            if (second_level_linked_artifact_ids.length === 0) {
                all_artifact_rows.push(first_level_artifact_cells);
            } else {
                for (const second_level_linked_artifact_id of second_level_linked_artifact_ids) {
                    const second_level_artifact_representation =
                        organized_reports_data.second_level.artifact_representations.get(
                            second_level_linked_artifact_id
                        );
                    if (second_level_artifact_representation === undefined) {
                        throw new Error(
                            "Artifact " +
                                second_level_linked_artifact_id +
                                " representation not found in collection."
                        );
                    }

                    const second_level_artifact_cells = transformArtifactRepresentationAsCells(
                        second_level_artifact_representation
                    );
                    all_artifact_rows.push(
                        first_level_artifact_cells.concat(second_level_artifact_cells)
                    );
                }
            }
        }
    } else {
        for (const artifact of organized_reports_data.first_level.artifact_representations.values()) {
            all_artifact_rows.push(transformArtifactRepresentationAsCells(artifact));
        }
    }

    return {
        headers: formatHeaders(organized_reports_data),
        artifacts_rows: all_artifact_rows,
    };
}

function transformArtifactRepresentationAsCells(
    artifact_representation: ArtifactResponse
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
