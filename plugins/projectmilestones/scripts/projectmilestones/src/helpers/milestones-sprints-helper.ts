/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { MilestoneData } from "../type";

export function openSprintsExist(release_data: MilestoneData): boolean {
    if (typeof release_data.total_sprint !== "number") {
        return false;
    }

    if (!release_data.open_sprints) {
        return false;
    }

    return release_data.open_sprints.length > 0;
}

export function closedSprintsExists(release_data: MilestoneData): boolean {
    if (typeof release_data.total_sprint !== "number") {
        return false;
    }

    if (!release_data.total_closed_sprint) {
        return false;
    }

    return release_data.total_closed_sprint > 0;
}

export function getSortedSprints(
    sprints: MilestoneData[]
): { open_sprints: MilestoneData[]; closed_sprints: MilestoneData[] } {
    const open_sprints: MilestoneData[] = [];
    const closed_sprints: MilestoneData[] = [];

    sprints.forEach((sprint) => {
        if (sprint.semantic_status === "open") {
            open_sprints.push(sprint);
        } else {
            closed_sprints.push(sprint);
        }
    });

    open_sprints.sort((sprint_1, sprint_2) => sprint_2.id - sprint_1.id);

    return { open_sprints, closed_sprints };
}
