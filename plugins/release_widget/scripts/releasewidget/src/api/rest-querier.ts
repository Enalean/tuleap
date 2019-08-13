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

import { get, recursiveGet } from "tlp";
import {
    TrackerProject,
    MilestoneContent,
    MilestoneData,
    ParametersRequestWithId,
    ParametersRequestWithoutId
} from "../type";

export {
    getNbOfBacklogItems,
    getNbOfUpcomingReleases,
    getCurrentMilestones,
    getNbOfSprints,
    getMilestonesContent,
    getTrackersProject
};

function recursiveGetProjectMilestonesWithQuery(
    project_id: number,
    query: string,
    limit: number,
    offset: number
): Promise<MilestoneData[]> {
    return recursiveGet(`/api/v1/projects/${encodeURIComponent(project_id)}/milestones`, {
        params: {
            limit,
            offset,
            query
        }
    });
}

async function getNbOfUpcomingReleases({
    project_id,
    limit,
    offset
}: ParametersRequestWithId): Promise<number> {
    const query = JSON.stringify({
        period: "future"
    });

    const response = await recursiveGetProjectMilestonesWithQuery(
        project_id!,
        query,
        limit,
        offset
    );

    return response.length;
}

function getCurrentMilestones({
    project_id,
    limit,
    offset
}: ParametersRequestWithId): Promise<MilestoneData[]> {
    const query = JSON.stringify({
        period: "current"
    });

    return recursiveGetProjectMilestonesWithQuery(project_id!, query, limit, offset);
}

async function getNbOfBacklogItems({
    project_id,
    limit,
    offset
}: ParametersRequestWithId): Promise<number> {
    const response = await get(`/api/v1/projects/${encodeURIComponent(project_id!)}/backlog`, {
        params: {
            limit,
            offset
        }
    });

    return getPaginationSizeFromHeader(response.headers);
}

async function getNbOfSprints(
    milestone_id: number,
    { limit, offset }: ParametersRequestWithoutId
): Promise<number> {
    const response = await get(
        `/api/v1/milestones/${encodeURIComponent(milestone_id)}/milestones`,
        {
            params: {
                limit,
                offset
            }
        }
    );

    return getPaginationSizeFromHeader(response.headers);
}

function getMilestonesContent(
    id_release: number,
    { limit, offset }: ParametersRequestWithoutId
): Promise<MilestoneContent[]> {
    return recursiveGet(`/api/v1/milestones/${encodeURIComponent(id_release)}/content`, {
        params: {
            limit,
            offset
        }
    });
}

function getTrackersProject({
    project_id,
    limit,
    offset
}: ParametersRequestWithId): Promise<TrackerProject[]> {
    return recursiveGet(`/api/v1/projects/${encodeURIComponent(project_id!)}/trackers`, {
        params: {
            limit,
            offset
        }
    });
}

function getPaginationSizeFromHeader(header: any): number {
    return Number.parseInt(header.get("X-PAGINATION-SIZE"), 10);
}
