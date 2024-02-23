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

import { get, put, patch } from "@tuleap/tlp-fetch";
import { formatDatetimeToISO } from "@tuleap/plugin-timetracking-time-formatters";
import type { ProjectReference } from "@tuleap/core-rest-api-types";
import type {
    OverviewReport,
    OverviewReportTracker,
    TrackerWithTimes,
} from "@tuleap/plugin-timetracking-rest-api-types";

const headers = {
    "content-type": "application/json",
};

export async function getTrackersFromReport(report_id: number): Promise<OverviewReport> {
    const response = await get("/api/v1/timetracking_reports/" + encodeURIComponent(report_id));
    return response.json();
}

export async function getTimes(
    report_id: number,
    trackers_id: number[],
    start_date: string,
    end_date: string,
): Promise<TrackerWithTimes[]> {
    const query = JSON.stringify({
        trackers_id: trackers_id,
        start_date: formatDatetimeToISO(start_date),
        end_date: formatDatetimeToISO(end_date),
    });

    const response = await get(
        "/api/v1/timetracking_reports/" + encodeURIComponent(report_id) + "/times",
        {
            params: {
                query,
            },
        },
    );
    return response.json();
}

export async function getTimesFromReport(
    report_id: number,
    start_date: string,
    end_date: string,
): Promise<TrackerWithTimes[]> {
    const query = JSON.stringify({
        start_date: formatDatetimeToISO(start_date),
        end_date: formatDatetimeToISO(end_date),
    });

    const response = await get(
        "/api/v1/timetracking_reports/" + encodeURIComponent(report_id) + "/times",
        {
            params: {
                query,
            },
        },
    );
    return response.json();
}

export async function getProjectsWithTimetracking(): Promise<ProjectReference[]> {
    const response = await get("/api/v1/projects", {
        params: {
            limit: 50,
            offset: 0,
            query: JSON.stringify({ with_time_tracking: true }),
        },
    });

    return response.json();
}

export async function getTrackersWithTimetracking(
    project_id: number,
): Promise<OverviewReportTracker[]> {
    const response = await get("/api/v1/projects/" + project_id + "/trackers", {
        params: {
            representation: "minimal",
            limit: 50,
            offset: 0,
            query: JSON.stringify({ with_time_tracking: true }),
        },
    });

    return response.json();
}

export async function saveNewReport(
    report_id: number,
    trackers_id: number[],
): Promise<OverviewReport> {
    const body = JSON.stringify({
        trackers_id: trackers_id,
    });

    const response = await put("/api/v1/timetracking_reports/" + report_id, {
        headers,
        body,
    });

    return response.json();
}

export async function setDisplayPreference(
    report_id: number,
    user_id: number,
    are_void_trackers_hidden: boolean,
): Promise<void> {
    const body = JSON.stringify({
        key: "timetracking_overview_display_trackers_without_time_" + report_id,
        value: are_void_trackers_hidden.toString(),
    });
    await patch("/api/v1/users/" + encodeURIComponent(user_id) + "/preferences", {
        headers,
        body,
    });
}
