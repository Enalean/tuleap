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

import { get, post, del, recursiveGet } from "@tuleap/tlp-fetch";
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

function getOpenMilestones(project_id) {
    return recursiveGet(`/api/projects/${encodeURIComponent(project_id)}/milestones`, {
        params: {
            query: JSON.stringify({ status: "open" }),
            limit: 10,
            offset: 0,
        },
    });
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

function getBaselines(project_id) {
    return recursiveGet(`/api/projects/${encodeURIComponent(project_id)}/baselines`, {
        params: {
            limit: 50,
            offset: 0,
        },
        getCollectionCallback: (collection) => {
            return collection.baselines;
        },
    });
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
    let artifacts = [];
    const limit = 100;
    for (let i = 0; i < artifact_ids.length; i += limit) {
        const query = JSON.stringify({
            ids: artifact_ids.slice(i, i + (limit - 1)),
        });
        const response = await get(
            `/api/baselines/${baseline_id}/artifacts?query=${encodeURIComponent(query)}`,
        );

        let json_response = await response.json();
        artifacts = artifacts.concat(json_response.artifacts);
    }

    return artifacts;
}

function getComparisons(project_id) {
    return recursiveGet(`/api/projects/${encodeURIComponent(project_id)}/baselines_comparisons`, {
        params: {
            limit: 50,
            offset: 0,
        },
        getCollectionCallback: (collection) => {
            return collection.comparisons;
        },
    });
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
