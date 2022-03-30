/**
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

import { get, post, del } from "tlp";
import DateUtils from "../support/date-utils";

export {
    getOpenMilestones,
    createBaseline,
    getBaseline,
    getComparison,
    deleteBaseline,
    deleteComparison,
    getTracker,
    getBaselines,
    getUser,
    getArtifact,
    getBaselineArtifacts,
    getBaselineArtifactsByIds,
    getComparisons,
    createComparison,
};

const JSON_HEADERS = {
    "content-type": "application/json",
};

async function getOpenMilestones(project_id) {
    const response = await get(`/api/projects/${project_id}/milestones?query={"status":"open"}`);
    return response.json();
}

async function createBaseline(name, milestone, snapshot_date) {
    let formatted_date = null;
    if (snapshot_date) {
        formatted_date = DateUtils.formatToISO(snapshot_date);
    }
    const body = JSON.stringify({
        name,
        artifact_id: milestone.id,
        snapshot_date: formatted_date,
    });

    const response = await post("/api/baselines/", {
        headers: JSON_HEADERS,
        body,
    });

    return response.json();
}

async function getBaseline(id) {
    const response = await get(`/api/baselines/${id}`);
    return response.json();
}

async function getComparison(id) {
    const response = await get(`/api/baselines_comparisons/${id}`);
    return response.json();
}

async function deleteBaseline(id) {
    await del(`/api/baselines/${id}`);
}

async function deleteComparison(id) {
    await del(`/api/baselines_comparisons/${id}`);
}

async function getTracker(id) {
    const response = await get(`/api/trackers/${id}`);
    return response.json();
}

async function getBaselines(project_id) {
    const response = await get(`/api/projects/${project_id}/baselines?limit=1000&offset=0`);
    const baselines_with_total_count = await response.json();
    return baselines_with_total_count.baselines;
}

async function getUser(user_id) {
    const response = await get(`/api/users/${user_id}`);
    return response.json();
}

async function getArtifact(artifact_id) {
    const response = await get(`/api/artifacts/${artifact_id}`);
    return response.json();
}

async function getBaselineArtifacts(baseline_id) {
    const response = await get(`/api/baselines/${baseline_id}/artifacts`);
    let json_response = await response.json();
    return json_response.artifacts;
}

async function getBaselineArtifactsByIds(baseline_id, artifact_ids) {
    const query = JSON.stringify({
        ids: artifact_ids,
    });
    const response = await get(
        `/api/baselines/${baseline_id}/artifacts?query=${encodeURIComponent(query)}`
    );

    let json_response = await response.json();
    return json_response.artifacts;
}

async function getComparisons(project_id) {
    const response = await get(
        `/api/projects/${project_id}/baselines_comparisons?limit=1000&offset=0`
    );
    const comparisons_with_total_count = await response.json();
    return comparisons_with_total_count.comparisons;
}

async function createComparison(name, comment, base_baseline_id, compared_to_baseline_id) {
    const body = JSON.stringify({
        name,
        comment,
        base_baseline_id,
        compared_to_baseline_id,
    });

    const response = await post("/api/baselines_comparisons/", {
        headers: JSON_HEADERS,
        body,
    });

    return response.json();
}
