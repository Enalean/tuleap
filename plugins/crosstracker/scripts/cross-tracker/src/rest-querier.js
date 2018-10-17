/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

import { get, put, recursiveGet } from "tlp";

export {
    getReport,
    getReportContent,
    getQueryResult,
    updateReport,
    getSortedProjectsIAmMemberOf,
    getTrackersOfProject
};

async function getReport(report_id) {
    const response = await get("/api/v1/cross_tracker_reports/" + report_id);
    return response.json();
}

async function getReportContent(report_id, limit, offset) {
    const response = await get("/api/v1/cross_tracker_reports/" + report_id + "/content", {
        params: {
            limit,
            offset
        }
    });
    const total = response.headers.get("X-PAGINATION-SIZE");
    const { artifacts } = await response.json();
    return { artifacts, total };
}

async function getQueryResult(report_id, trackers_id, expert_query, limit, offset) {
    const response = await get("/api/v1/cross_tracker_reports/" + report_id + "/content", {
        params: {
            limit,
            offset,
            query: JSON.stringify({ trackers_id, expert_query })
        }
    });
    const total = response.headers.get("X-PAGINATION-SIZE");
    const { artifacts } = await response.json();

    return { artifacts, total };
}

async function updateReport(report_id, trackers_id, expert_query) {
    const response = await put("/api/v1/cross_tracker_reports/" + report_id, {
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ trackers_id, expert_query })
    });
    return response.json();
}

async function getSortedProjectsIAmMemberOf() {
    const json = await recursiveGet("/api/v1/projects", {
        params: {
            limit: 50,
            query: JSON.stringify({ is_member_of: true })
        }
    });

    return json.sort(({ label: label_a }, { label: label_b }) => label_a.localeCompare(label_b));
}

function getTrackersOfProject(project_id) {
    return recursiveGet("/api/v1/projects/" + project_id + "/trackers", {
        params: {
            limit: 50,
            representation: "minimal"
        }
    });
}
