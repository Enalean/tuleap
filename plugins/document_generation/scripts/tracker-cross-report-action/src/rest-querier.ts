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
import type { LinkedArtifactsResponse, ArtifactForCrossReportDocGen } from "./type";

export function getReportArtifacts(
    report_id: number,
    report_has_changed: boolean,
    table_renderer_id: number | undefined
): Promise<ArtifactForCrossReportDocGen[]> {
    const params: Record<string, number | string | boolean> = {
        values: "from_table_renderer",
        with_unsaved_changes: report_has_changed,
        limit: 50,
    };
    if (table_renderer_id !== undefined) {
        params.table_renderer_id = table_renderer_id;
    }
    return recursiveGet(`/api/v1/tracker_reports/${encodeURIComponent(report_id)}/artifacts`, {
        params,
    });
}

export function getLinkedArtifacts(
    artifact_id: number,
    artifact_link_type: string
): Promise<LinkedArtifactsResponse[]> {
    return recursiveGet(`/api/v1/artifacts/${encodeURIComponent(artifact_id)}/linked_artifacts`, {
        params: {
            direction: "forward",
            nature: artifact_link_type,
            limit: 10,
        },
    });
}

export function getTrackerReports(tracker_id: number): Promise<TrackerReportResponse[]> {
    return recursiveGet(`/api/v1/trackers/${encodeURIComponent(tracker_id)}/tracker_reports`);
}

export function getTrackerCurrentlyUsedArtifactLinkTypes(
    tracker_id: number
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
