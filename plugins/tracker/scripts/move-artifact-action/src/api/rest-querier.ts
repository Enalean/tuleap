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

import { patch, recursiveGet } from "@tuleap/tlp-fetch";
import type { Tracker, Project } from "../store/types";

export function getProjectList(): Promise<Project[]> {
    return recursiveGet("/api/projects", {
        params: {
            query: JSON.stringify({
                is_tracker_admin: "true",
            }),
            limit: 50,
            offset: 0,
        },
    });
}

export function getTrackerList(project_id: number): Promise<Tracker[]> {
    return recursiveGet("/api/projects/" + project_id + "/trackers/", {
        params: {
            query: JSON.stringify({
                is_tracker_admin: "true",
            }),
            limit: 50,
        },
    });
}

export function moveDryRunArtifact(artifact_id: number, tracker_id: number): Promise<Response> {
    return processMove(artifact_id, tracker_id, true, false);
}

export function moveArtifact(artifact_id: number, tracker_id: number): Promise<Response> {
    return processMove(artifact_id, tracker_id, false, true);
}

function processMove(
    artifact_id: number,
    tracker_id: number,
    is_dry_run_move: boolean,
    should_populate_feedback_on_success: boolean
): Promise<Response> {
    const headers = {
        "content-type": "application/json",
    };

    const body = JSON.stringify({
        move: { tracker_id, dry_run: is_dry_run_move, should_populate_feedback_on_success },
    });

    return patch("/api/artifacts/" + artifact_id, {
        headers,
        body,
    });
}
