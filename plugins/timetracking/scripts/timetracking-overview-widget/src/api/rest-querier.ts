/*
 * Copyright Enalean (c) 2019 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
import { uri, getJSON, putJSON, patch } from "@tuleap/fetch-result";
import { formatDatetimeToISO } from "@tuleap/plugin-timetracking-time-formatters";
import type { ProjectReference } from "@tuleap/core-rest-api-types";
import type {
    OverviewReport,
    OverviewReportTracker,
    TrackerWithTimes,
} from "@tuleap/plugin-timetracking-rest-api-types";

export function getTrackersFromReport(report_id: number): ResultAsync<OverviewReport, Fault> {
    return getJSON(uri`/api/v1/timetracking_reports/${report_id}`);
}

export function getTimes(
    report_id: number,
    trackers_id: number[],
    start_date: string,
    end_date: string,
): ResultAsync<TrackerWithTimes[], Fault> {
    const query = JSON.stringify({
        trackers_id: trackers_id,
        start_date: formatDatetimeToISO(start_date),
        end_date: formatDatetimeToISO(end_date),
    });

    return getJSON(uri`/api/v1/timetracking_reports/${report_id}/times`, {
        params: {
            query,
        },
    });
}

export function getTimesFromReport(
    report_id: number,
    start_date: string,
    end_date: string,
): ResultAsync<TrackerWithTimes[], Fault> {
    const query = JSON.stringify({
        start_date: formatDatetimeToISO(start_date),
        end_date: formatDatetimeToISO(end_date),
    });

    return getJSON(uri`/api/v1/timetracking_reports/${report_id}/times`, {
        params: {
            query,
        },
    });
}

export function getProjectsWithTimetracking(): ResultAsync<ProjectReference[], Fault> {
    return getJSON(uri`/api/v1/projects`, {
        params: {
            limit: 50,
            offset: 0,
            query: JSON.stringify({ with_time_tracking: true }),
        },
    });
}

export function getTrackersWithTimetracking(
    project_id: number,
): ResultAsync<OverviewReportTracker[], Fault> {
    return getJSON(uri`/api/v1/projects/${project_id}/trackers`, {
        params: {
            representation: "minimal",
            limit: 50,
            offset: 0,
            query: JSON.stringify({ with_time_tracking: true }),
        },
    });
}

export function saveNewReport(
    report_id: number,
    trackers_id: number[],
): ResultAsync<OverviewReport, Fault> {
    return putJSON(uri`/api/v1/timetracking_reports/${report_id}`, {
        trackers_id,
    });
}

export function setDisplayPreference(
    report_id: number,
    user_id: number,
    are_void_trackers_hidden: boolean,
): ResultAsync<unknown, Fault> {
    return patch(
        uri`/api/v1/users/${user_id}/preferences`,
        {},
        {
            key: "timetracking_overview_display_trackers_without_time_" + report_id,
            value: are_void_trackers_hidden.toString(),
        },
    );
}
