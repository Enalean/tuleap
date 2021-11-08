/**
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

import { get, recursiveGet } from "@tuleap/tlp-fetch";
import type {
    TrackerDefinition,
    ArtifactResponse,
    TestExecutionResponse,
} from "./artifacts-retriever";
import { limitConcurrencyPool } from "@tuleap/concurrency-limit-pool";

export async function getTrackerDefinition(tracker_id: number): Promise<TrackerDefinition> {
    const tracker_structure_response = await get(
        `/api/v1/trackers/${encodeURIComponent(tracker_id)}`
    );
    return tracker_structure_response.json();
}

export async function getReportArtifacts(
    report_id: number,
    report_has_changed: boolean
): Promise<ArtifactResponse[]> {
    const report_artifacts: ArtifactResponse[] = await recursiveGet(
        `/api/v1/tracker_reports/${encodeURIComponent(report_id)}/artifacts`,
        {
            params: {
                values: "all",
                with_unsaved_changes: report_has_changed,
                limit: 50,
            },
        }
    );

    return report_artifacts;
}

export async function getTestManagementExecution(
    artifact_id: number
): Promise<TestExecutionResponse> {
    const test_execution_response = await get(
        `/api/v1/testmanagement_executions/${encodeURIComponent(artifact_id)}`
    );

    return test_execution_response.json();
}

const MAX_CHUNK_SIZE_ARTIFACTS = 100;
const MAX_CONCURRENT_REQUESTS_WHEN_RETRIEVING_ARTIFACT_CHUNKS = 5;

export async function getArtifacts(
    artifact_ids: ReadonlySet<number>
): Promise<Map<number, ArtifactResponse>> {
    const artifacts: Map<number, ArtifactResponse> = new Map();
    const responses = await limitConcurrencyPool(
        MAX_CONCURRENT_REQUESTS_WHEN_RETRIEVING_ARTIFACT_CHUNKS,
        getArtifactIDsChunks([...artifact_ids]),
        (artifact_ids_chunk: ReadonlyArray<number>): Promise<Response> => {
            return get(
                `/api/v1/artifacts?query=${encodeURIComponent(
                    JSON.stringify({ id: artifact_ids_chunk })
                )}`
            );
        }
    );

    for (const response of responses) {
        const artifacts_collection: { collection: ArtifactResponse[] } = await response.json();

        for (const artifact of artifacts_collection.collection) {
            artifacts.set(artifact.id, artifact);
        }
    }

    return artifacts;
}

function getArtifactIDsChunks(
    artifact_ids: ReadonlyArray<number>
): ReadonlyArray<ReadonlyArray<number>> {
    const artifact_ids_chunks: ReadonlyArray<number>[] = [];

    let index = 0;
    while (index < artifact_ids.length) {
        artifact_ids_chunks.push(artifact_ids.slice(index, index + MAX_CHUNK_SIZE_ARTIFACTS));
        index += MAX_CHUNK_SIZE_ARTIFACTS;
    }

    return artifact_ids_chunks;
}
