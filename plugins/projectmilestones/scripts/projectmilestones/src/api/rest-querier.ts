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

import { get, recursiveGet } from "@tuleap/tlp-fetch";
import type {
    MilestoneContent,
    MilestoneData,
    ParametersRequestWithId,
    ParametersRequestWithoutId,
    ArtifactMilestone,
    TestManagementCampaign,
} from "../type";

function recursiveGetProjectMilestonesWithQuery(
    project_id: number,
    query: string,
    limit: number,
    offset: number,
): Promise<MilestoneData[]> {
    return recursiveGet(`/api/v1/projects/${encodeURIComponent(project_id)}/milestones`, {
        params: {
            limit,
            offset,
            query,
        },
    });
}

export function getCurrentMilestones({
    project_id,
    limit,
    offset,
}: ParametersRequestWithId): Promise<MilestoneData[]> {
    const query = JSON.stringify({
        period: "current",
    });

    return recursiveGetProjectMilestonesWithQuery(project_id, query, limit, offset);
}

export function getAllSprints(
    milestone_id: number,
    { limit, offset }: ParametersRequestWithoutId,
): Promise<MilestoneData[]> {
    return recursiveGet(`/api/v1/milestones/${encodeURIComponent(milestone_id)}/milestones`, {
        params: {
            limit,
            offset,
        },
    });
}

export function getMilestonesContent(
    id_release: number,
    { limit, offset }: ParametersRequestWithoutId,
): Promise<MilestoneContent[]> {
    return recursiveGet(`/api/v1/milestones/${encodeURIComponent(id_release)}/content`, {
        params: {
            limit,
            offset,
        },
    });
}

export function getMilestonesBacklog(
    id_release: number,
    { limit, offset }: ParametersRequestWithoutId,
): Promise<MilestoneContent[]> {
    const query = JSON.stringify({
        status: "all",
    });
    return recursiveGet(`/api/v1/milestones/${encodeURIComponent(id_release)}/backlog`, {
        params: {
            limit,
            offset,
            query,
        },
    });
}

function getPaginationSizeFromHeader(header: Headers): number {
    const pagination_size_header = header.get("X-PAGINATION-SIZE");
    if (pagination_size_header === null) {
        return 0;
    }
    return Number.parseInt(pagination_size_header, 10);
}

export async function getChartData(milestone_id: number): Promise<ArtifactMilestone> {
    const chart_data = await get(`/api/v1/artifacts/${encodeURIComponent(milestone_id)}`);
    return chart_data.json();
}

export async function getNbOfPastRelease({ project_id }: ParametersRequestWithId): Promise<number> {
    const query = JSON.stringify({
        status: "closed",
    });
    const response = await get(`/api/v1/projects/${encodeURIComponent(project_id)}/milestones`, {
        params: {
            limit: 1,
            offset: 0,
            query,
        },
    });

    return getPaginationSizeFromHeader(response.headers);
}

export async function getLastRelease(
    project_id: number,
    nb_past_releases: number,
): Promise<MilestoneData[] | null> {
    const query = JSON.stringify({
        status: "closed",
    });
    if (nb_past_releases === 0) {
        return null;
    }

    const milestones = await get(`/api/v1/projects/${encodeURIComponent(project_id)}/milestones`, {
        params: {
            limit: 1,
            offset: nb_past_releases - 1,
            query,
        },
    });

    return milestones.json();
}

export function getTestManagementCampaigns(
    release_id: number,
    { limit, offset, project_id }: ParametersRequestWithId,
): Promise<TestManagementCampaign[]> {
    const query = JSON.stringify({
        milestone_id: release_id,
    });

    return recursiveGet(
        `/api/v1/projects/${encodeURIComponent(project_id)}/testmanagement_campaigns`,
        {
            params: {
                query,
                limit,
                offset,
            },
        },
    );
}
