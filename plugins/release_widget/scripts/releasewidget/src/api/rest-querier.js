/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { get } from "tlp";

export {
    getNbOfBacklogItems,
    getNbOfUpcomingReleases,
    getCurrentMilestones,
    getNbOfSprints,
    getInitialEffortOfRelease
};

function getProjectMilestonesWithQuery(project_id, query, pagination_limit, pagination_offset) {
    return get(`/api/v1/projects/${encodeURIComponent(project_id)}/milestones`, {
        params: {
            pagination_limit,
            pagination_offset,
            query
        }
    });
}

async function getNbOfUpcomingReleases({ project_id, pagination_limit, pagination_offset }) {
    const query = JSON.stringify({
        period: "future"
    });

    const response = await getProjectMilestonesWithQuery(
        project_id,
        query,
        pagination_limit,
        pagination_offset
    );

    return getPaginationSizeFromHeader(response.headers);
}

async function getCurrentMilestones({ project_id, pagination_limit, pagination_offset }) {
    const query = JSON.stringify({
        period: "current"
    });

    const response = await getProjectMilestonesWithQuery(
        project_id,
        query,
        pagination_limit,
        pagination_offset
    );

    return response.json();
}

async function getNbOfBacklogItems({ project_id, pagination_limit, pagination_offset }) {
    const response = await get(`/api/v1/projects/${encodeURIComponent(project_id)}/backlog`, {
        params: {
            pagination_limit,
            pagination_offset
        }
    });

    return getPaginationSizeFromHeader(response.headers);
}

async function getNbOfSprints(milestone_id, { pagination_limit, pagination_offset }) {
    const response = await get(
        `/api/v1/milestones/${encodeURIComponent(milestone_id)}/milestones`,
        {
            params: {
                pagination_limit,
                pagination_offset
            }
        }
    );

    return getPaginationSizeFromHeader(response.headers);
}

async function getInitialEffortOfRelease(id_release, { pagination_limit, pagination_offset }) {
    const response = await get(`/api/v1/milestones/${encodeURIComponent(id_release)}/content`, {
        params: {
            pagination_limit,
            pagination_offset
        }
    });

    return response.json();
}

function getPaginationSizeFromHeader(header) {
    return Number(header.get("X-PAGINATION-SIZE"));
}
