/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { recursiveGet } from "@tuleap/tlp-fetch";

export type MilestonesProgressCallback<T> = (data: MilestoneRepresentation[]) => T[];

type MilestoneParentType = "projects" | "milestones";
type StatusQuery = "open" | "closed";

export interface MilestoneRepresentation {
    readonly id: number;
    readonly label: string;
}

export function getOpenTopMilestones<T>(
    project_id: number,
    callback: MilestonesProgressCallback<T>,
): Promise<T[]> {
    return getMilestones("projects", project_id, 50, "open", callback);
}

export function getOpenSubMilestones<T>(
    milestone_id: number,
    callback: MilestonesProgressCallback<T>,
): Promise<T[]> {
    return getMilestones("milestones", milestone_id, 100, "open", callback);
}

export function getClosedTopMilestones<T>(
    project_id: number,
    callback: MilestonesProgressCallback<T>,
): Promise<T[]> {
    return getMilestones("projects", project_id, 50, "closed", callback);
}

export function getClosedSubMilestones<T>(
    milestone_id: number,
    callback: MilestonesProgressCallback<T>,
): Promise<T[]> {
    return getMilestones("milestones", milestone_id, 100, "closed", callback);
}

function getMilestones<T>(
    parent_type: MilestoneParentType,
    parent_id: number,
    limit: number,
    status: StatusQuery,
    callback: MilestonesProgressCallback<T>,
): Promise<T[]> {
    return recursiveGet(encodeURI(`/api/v1/${parent_type}/${parent_id}/milestones`), {
        params: {
            limit,
            query: JSON.stringify({ status }),
            fields: "slim",
            order: "asc",
        },
        getCollectionCallback: callback,
    });
}
