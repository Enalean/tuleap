/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
import { uri, patchJSON, getAllJSON } from "@tuleap/fetch-result";
import type { Fault } from "@tuleap/fault";
import type { Tracker, Project, DryRunResultPayload } from "./types";

export function getProjectList(): ResultAsync<ReadonlyArray<Project>, Fault> {
    return getAllJSON<Project>(uri`/api/projects`, {
        params: {
            query: JSON.stringify({
                is_tracker_admin: "true",
            }),
            limit: 50,
        },
    });
}

export function getTrackerList(project_id: number): ResultAsync<ReadonlyArray<Tracker>, Fault> {
    return getAllJSON<Tracker>(uri`/api/projects/${project_id}/trackers/`, {
        params: {
            query: JSON.stringify({
                is_tracker_admin: "true",
            }),
            limit: 50,
        },
    });
}

export function moveDryRunArtifact(
    artifact_id: number,
    tracker_id: number
): ResultAsync<DryRunResultPayload, Fault> {
    return patchJSON<DryRunResultPayload>(uri`/api/artifacts/${artifact_id}`, {
        move: { tracker_id, dry_run: true },
    });
}

export function moveArtifact(artifact_id: number, tracker_id: number): ResultAsync<never, Fault> {
    return patchJSON(uri`/api/artifacts/${artifact_id}`, {
        move: { tracker_id, should_populate_feedback_on_success: true },
    });
}
