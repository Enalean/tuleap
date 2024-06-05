/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

import { decodeJSON, getJSON, getResponse, putJSON, uri } from "@tuleap/fetch-result";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { get, recursiveGet } from "@tuleap/tlp-fetch";
import type { ProjectReference } from "@tuleap/core-rest-api-types";
import type {
    Artifact,
    ArtifactsCollection,
    Report,
    TrackerAndProject,
    TrackerInfo,
} from "../type";
import type { TrackerResponseWithProject } from "@tuleap/plugin-tracker-rest-api-types";

export type TrackerReference = Pick<TrackerResponseWithProject, "id" | "label" | "project">;

type ReportRepresentation = {
    readonly trackers: ReadonlyArray<TrackerReference>;
    readonly expert_query: string;
    readonly invalid_trackers: ReadonlyArray<TrackerReference>;
};

const mapToTrackerAndProject = (
    trackers: ReadonlyArray<TrackerReference>,
): ReadonlyArray<TrackerAndProject> =>
    trackers.map((tracker): TrackerAndProject => {
        return {
            tracker: { id: tracker.id, label: tracker.label },
            project: tracker.project,
        };
    });

export function getReport(report_id: number): ResultAsync<Report, Fault> {
    return getJSON<ReportRepresentation>(uri`/api/v1/cross_tracker_reports/${report_id}`).map(
        (report): Report => {
            return {
                expert_query: report.expert_query,
                trackers: mapToTrackerAndProject(report.trackers),
                invalid_trackers: report.invalid_trackers,
            };
        },
    );
}

type ReportContentRepresentation = {
    readonly artifacts: ReadonlyArray<Artifact>;
};

export function getReportContent(
    report_id: number,
    limit: number,
    offset: number,
): ResultAsync<ArtifactsCollection, Fault> {
    return getResponse(uri`/api/v1/cross_tracker_reports/${report_id}/content`, {
        params: {
            limit,
            offset,
        },
    }).andThen((response) => {
        const total = Number.parseInt(response.headers.get("X-PAGINATION-SIZE") ?? "0", 10);
        return decodeJSON<ReportContentRepresentation>(response).map((collection) => ({
            artifacts: collection.artifacts,
            total,
        }));
    });
}

export function getQueryResult(
    report_id: number,
    trackers_id: Array<number>,
    expert_query: string,
    limit: number,
    offset: number,
): ResultAsync<ArtifactsCollection, Fault> {
    return getResponse(uri`/api/v1/cross_tracker_reports/${report_id}/content`, {
        params: {
            limit,
            offset,
            query: JSON.stringify({ trackers_id, expert_query }),
        },
    }).andThen((response) => {
        const total = Number.parseInt(response.headers.get("X-PAGINATION-SIZE") ?? "0", 10);
        return decodeJSON<ReportContentRepresentation>(response).map((collection) => ({
            artifacts: collection.artifacts,
            total,
        }));
    });
}

export function updateReport(
    report_id: number,
    trackers_id: Array<number>,
    expert_query: string,
): ResultAsync<Report, Fault> {
    return putJSON<ReportRepresentation>(uri`/api/v1/cross_tracker_reports/${report_id}`, {
        trackers_id,
        expert_query,
    }).map((report): Report => {
        return {
            expert_query: report.expert_query,
            trackers: mapToTrackerAndProject(report.trackers),
            invalid_trackers: report.invalid_trackers,
        };
    });
}

export async function getSortedProjectsIAmMemberOf(): Promise<ProjectReference[]> {
    const json = await recursiveGet<unknown[], ProjectReference>("/api/v1/projects", {
        params: {
            limit: 50,
            query: JSON.stringify({ is_member_of: true }),
        },
    });

    return json.sort(({ label: label_a }, { label: label_b }) => {
        return label_a.localeCompare(label_b);
    });
}

export function getTrackersOfProject(project_id: number): Promise<Array<TrackerInfo>> {
    return recursiveGet("/api/v1/projects/" + project_id + "/trackers", {
        params: {
            limit: 50,
            representation: "minimal",
        },
    });
}

export function getCSVReport(report_id: number): Promise<string> {
    return recursiveGetCSV("/plugins/crosstracker/csv_export/" + report_id, {
        limit: 50,
        offset: 0,
    });
}

async function recursiveGetCSV(
    route: string,
    params: { limit: number; offset: number },
): Promise<string> {
    const { limit = 50, offset = 0 } = params;
    const response = await get(route, {
        params: {
            ...params,
            limit,
            offset,
        },
    });
    const results = await response.text();
    const size = response.headers.get("X-PAGINATION-SIZE");
    if (!size) {
        throw new Error("can not get query result, pagination size is not sent in headers");
    }

    const total = Number.parseInt(size, 10);
    const new_offset = offset + limit;

    if (new_offset >= total) {
        return results;
    }

    const new_params = {
        ...params,
        offset: new_offset,
    };

    const second_response = await recursiveGetCSV(route, new_params);
    const second_results = second_response.split("\r\n");
    second_results.shift();
    return results + second_results.join("\r\n");
}
