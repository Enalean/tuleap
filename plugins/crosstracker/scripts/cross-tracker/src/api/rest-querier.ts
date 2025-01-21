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

import type { EncodedURI } from "@tuleap/fetch-result";
import {
    decodeAsText,
    decodeJSON,
    getAllJSON,
    getJSON,
    getResponse,
    getTextResponse,
    putJSON,
    uri,
} from "@tuleap/fetch-result";
import { type ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type {
    ArtifactsCollection,
    ProjectInfo,
    Report,
    TrackerAndProject,
    TrackerInfo,
} from "../type";
import type { TrackerProjectRepresentation } from "@tuleap/plugin-tracker-rest-api-types";
import type { ProjectIdentifier } from "../domain/ProjectIdentifier";
import type {
    ReportContentRepresentation,
    ReportRepresentation,
    TrackerReference,
} from "./cross-tracker-rest-api-types";

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
                expert_mode: true,
            };
        },
    );
}

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
            expert_mode: true,
        };
    });
}

const sortProjects = (
    projects: ReadonlyArray<TrackerProjectRepresentation>,
): ReadonlyArray<ProjectInfo> =>
    Array.from(projects).sort((project_a, project_b) =>
        project_a.label.localeCompare(project_b.label),
    );

export function getSortedProjectsIAmMemberOf(): ResultAsync<ReadonlyArray<ProjectInfo>, Fault> {
    return getAllJSON<TrackerProjectRepresentation, ReadonlyArray<TrackerProjectRepresentation>>(
        uri`/api/v1/projects`,
        {
            params: {
                limit: 50,
                query: `{"is_member_of":true}`,
            },
        },
    ).map(sortProjects);
}

export function getTrackersOfProject(
    project_id: ProjectIdentifier,
): ResultAsync<ReadonlyArray<TrackerInfo>, Fault> {
    return getAllJSON(uri`/api/v1/projects/${project_id.id}/trackers`, {
        params: {
            limit: 50,
            representation: "minimal",
        },
    });
}

export function getCSVReport(report_id: number): ResultAsync<string, Fault> {
    return recursiveGetCSV(uri`/plugins/crosstracker/csv_export/${report_id}`, 50, 0);
}

function recursiveGetCSV(
    api_uri: EncodedURI,
    limit: number,
    offset: number,
): ResultAsync<string, Fault> {
    return getTextResponse(api_uri, { params: { limit, offset } }).andThen((response) => {
        const size = response.headers.get("X-PAGINATION-SIZE");
        if (!size) {
            // This is likely an unexpected dev problem, we should not handle this case with Fault
            throw Error("No X-PAGINATION-SIZE field in the header.");
        }
        const total = Number.parseInt(size, 10);
        const new_offset = offset + limit;
        const csv_result = decodeAsText(response);
        if (new_offset >= total) {
            return csv_result;
        }

        return recursiveGetCSV(api_uri, limit, new_offset).andThen((second_csv) => {
            const csv_strings = second_csv.split("\r\n");
            // Remove the first line
            csv_strings.shift();
            return csv_result.map((first_csv) => first_csv + csv_strings.join("\r\n"));
        });
    });
}
