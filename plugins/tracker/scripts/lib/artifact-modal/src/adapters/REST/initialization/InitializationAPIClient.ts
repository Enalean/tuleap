/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { decodeJSON, getResponse, uri } from "@tuleap/fetch-result";
import type { CurrentArtifactIdentifier } from "@tuleap/plugin-tracker-artifact-common";
import type { RetrieveCurrentArtifactWithTrackerStructure } from "../../../domain/initialization/RetrieveCurrentArtifactWithTrackerStructure";
import type {
    CurrentArtifactWithTrackerStructure,
    TrackerStructure,
} from "../../../domain/initialization/CurrentArtifactWithTrackerStructure";
import type {
    ArtifactResponseNoInstance,
    TrackerProjectRepresentation,
    TrackerResponseNoInstance,
} from "@tuleap/plugin-tracker-rest-api-types";

export type InitializationAPIClient = RetrieveCurrentArtifactWithTrackerStructure;

type ReducedProjectRepresentation = Pick<TrackerProjectRepresentation, "id">;

export interface ReducedTrackerRepresentation
    extends Pick<
        TrackerResponseNoInstance,
        "id" | "item_name" | "color_name" | "parent" | "workflow" | "fields" | "notifications"
    > {
    readonly project: ReducedProjectRepresentation;
}

export interface ArtifactResponseFromREST
    extends Pick<ArtifactResponseNoInstance, "id" | "title" | "values"> {
    readonly tracker: ReducedTrackerRepresentation;
}

function mapTracker(tracker_from_api: ReducedTrackerRepresentation): TrackerStructure {
    return {
        ...tracker_from_api,
        are_mentions_effective: tracker_from_api.notifications.enabled,
    };
}

export const InitializationAPIClient = (): InitializationAPIClient => ({
    getCurrentArtifactWithTrackerStructure(
        current_artifact: CurrentArtifactIdentifier,
    ): ResultAsync<CurrentArtifactWithTrackerStructure, Fault> {
        return getResponse(
            uri`/api/v1/artifacts/${current_artifact.id}?tracker_structure_format=complete`,
        ).andThen((response) =>
            decodeJSON<ArtifactResponseFromREST>(response).map((artifact_from_api) => {
                const artifact_with_structure: CurrentArtifactWithTrackerStructure = {
                    etag: response.headers.get("Etag"),
                    last_modified: response.headers.get("Last-Modified"),
                    ...artifact_from_api,
                    tracker: mapTracker(artifact_from_api.tracker),
                };
                return artifact_with_structure;
            }),
        );
    },
});
