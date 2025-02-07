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
import { getAllJSON, getJSON, uri, postJSON } from "@tuleap/fetch-result";
import type { ProjectResponse } from "@tuleap/core-rest-api-types";
import type {
    ArtifactCreationPayload,
    JustCreatedArtifactResponse,
    TrackerResponseWithCannotCreateReason,
} from "@tuleap/plugin-tracker-rest-api-types";
import type { RetrieveProjects } from "../../../domain/creation/RetrieveProjects";
import type { Project } from "../../../domain/Project";
import { ProjectProxy } from "../ProjectProxy";
import type { RetrieveProjectTrackers } from "../../../domain/creation/RetrieveProjectTrackers";
import type { Tracker } from "../../../domain/Tracker";
import { MINIMAL_REPRESENTATION, SEMANTIC_TO_CHECK, TrackerProxy } from "../TrackerProxy";
import type { RetrieveTrackerWithTitleSemantic } from "./RetrieveTrackerWithTitleSemantic";
import type { TrackerWithTitleSemantic } from "./TrackerWithTitleSemantic";
import type { CreateArtifactWithTitle } from "./CreateArtifactWithTitle";
import { ArtifactCreatedIdentifier } from "../../../domain/creation/ArtifactCreatedIdentifier";

export type ArtifactCreationAPIClient = RetrieveProjects &
    RetrieveProjectTrackers &
    RetrieveTrackerWithTitleSemantic &
    CreateArtifactWithTitle;

export const ArtifactCreationAPIClient = (): ArtifactCreationAPIClient => ({
    getProjects(): ResultAsync<readonly Project[], Fault> {
        return getAllJSON<ProjectResponse>(uri`/api/projects`, {
            params: { limit: 50 },
        }).map((projects) => projects.map(ProjectProxy.fromAPIProject));
    },

    getTrackersByProject(project_id): ResultAsync<readonly Tracker[], Fault> {
        return getAllJSON<TrackerResponseWithCannotCreateReason>(
            uri`/api/projects/${project_id.id}/trackers`,
            {
                params: {
                    limit: 50,
                    representation: MINIMAL_REPRESENTATION,
                    with_creation_semantic_check: SEMANTIC_TO_CHECK,
                },
            },
        ).map((trackers) => trackers.map(TrackerProxy.fromAPIProject));
    },

    getTrackerWithTitleSemantic(tracker_id): ResultAsync<TrackerWithTitleSemantic, Fault> {
        return getJSON<TrackerWithTitleSemantic>(uri`/api/trackers/${tracker_id.id}`);
    },

    createArtifactWithTitle(
        tracker_identifier,
        title_field_identifier,
        title,
    ): ResultAsync<ArtifactCreatedIdentifier, Fault> {
        const payload: ArtifactCreationPayload = {
            tracker: { id: tracker_identifier.id },
            values: [{ field_id: title_field_identifier.id, value: title }],
        };
        return postJSON<JustCreatedArtifactResponse>(uri`/api/v1/artifacts`, payload).map(
            (response) => ArtifactCreatedIdentifier.fromId(response.id),
        );
    },
});
