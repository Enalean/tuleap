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

export {
    getTrackersFromReport,
    getTimesFromReport,
    getTimes,
    getProjectsWithTimetracking,
    getTrackersWithTimetracking,
    saveNewReport,
    setDisplayPreference,
};

const headers = {
    "content-type": "application/json",
};

async function getTrackersFromReport(report_id) {
    const response = await get("/api/v1/timetracking_reports/" + encodeURI(report_id));
    return response.json();
}

async function getTimes(report_id, trackers_id, start_date, end_date) {
    const query = JSON.stringify({
        trackers_id: trackers_id,
        start_date: formatDatetimeToISO(start_date),
        end_date: formatDatetimeToISO(end_date),
    });

    const response = await get("/api/v1/timetracking_reports/" + encodeURI(report_id) + "/times", {
        params: {
            query,
        },
    });
    return response.json();
}

async function getTimesFromReport(report_id, start_date, end_date) {
    const query = JSON.stringify({
        start_date: formatDatetimeToISO(start_date),
        end_date: formatDatetimeToISO(end_date),
    });

    const response = await get("/api/v1/timetracking_reports/" + encodeURI(report_id) + "/times", {
        params: {
            query,
        },
    });
    return response.json();
}

async function getProjectsWithTimetracking() {
    const response = await get("/api/v1/projects", {
        params: {
            limit: 50,
            offset: 0,
            query: JSON.stringify({ with_time_tracking: true }),
        },
    });

    return response.json();
}

async function getTrackersWithTimetracking(project_id) {
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

async function saveNewReport(report_id, trackers_id) {
    const body = JSON.stringify({
        trackers_id: trackers_id,
    });

    const response = await put("/api/v1/timetracking_reports/" + report_id, {
        headers,
        body,
    });

    return response.json();
}

async function setDisplayPreference(report_id, user_id, are_void_trackers_hidden) {
    const body = JSON.stringify({
        key: "timetracking_overview_display_trackers_without_time_" + report_id,
        value: are_void_trackers_hidden.toString(),
    });
    await patch("/api/v1/users/" + encodeURI(user_id) + "/preferences", {
        headers,
        body,
    });
}
