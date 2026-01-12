/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

import { recursiveGet } from "@tuleap/tlp-fetch";
import type {
    MinimalTrackerResponse,
    TrackerReportResponse,
    TrackerUsedArtifactLinkResponse,
} from "@tuleap/plugin-tracker-rest-api-types";

export function getTrackerReports(tracker_id: number): Promise<TrackerReportResponse[]> {
    return recursiveGet(`/api/v1/trackers/${encodeURIComponent(tracker_id)}/tracker_reports`);
}

export function getTrackerCurrentlyUsedArtifactLinkTypes(
    tracker_id: number,
): Promise<TrackerUsedArtifactLinkResponse[]> {
    return recursiveGet(`/api/v1/trackers/${encodeURIComponent(tracker_id)}/used_artifact_links`);
}

export interface ProjectResponse {
    readonly id: number;
    readonly label: string;
    readonly icon: string;
}

export async function getProjects(): Promise<ProjectResponse[]> {
    const projects: ProjectResponse[] = await recursiveGet("/api/v1/projects", {
        params: { limit: 50 },
    });

    return projects.sort((a, b) => a.label.localeCompare(b.label));
}

export function getTrackers(project_id: number): Promise<MinimalTrackerResponse[]> {
    return recursiveGet(`/api/v1/projects/${encodeURIComponent(project_id)}/trackers`, {
        params: { limit: 50, representation: "minimal" },
    });
}
