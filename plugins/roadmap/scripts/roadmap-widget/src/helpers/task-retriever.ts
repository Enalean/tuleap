/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import type { RestTask, Task } from "../type";
import { recursiveGet } from "@tuleap/tlp-fetch";
import { SUBTASKS_WAITING_TO_BE_LOADED } from "../type";
import { doesTaskHaveEndDateGreaterOrEqualToStartDate } from "./task-has-valid-dates";
import { DateTime } from "luxon";

export function retrieveAllTasks(roadmap_id: number): Promise<Task[]> {
    return retrieveAll(`/api/roadmaps/${roadmap_id}/tasks`, {});
}

export function retrieveAllSubtasks(task: Task): Promise<Task[]> {
    return retrieveAll("/api/" + task.subtasks_uri, { parent: task });
}

function hasTimePeriodError(a: Task): boolean {
    return a.time_period_error_message.length > 0;
}

export async function retrieveAll(
    url: string,
    additional_defaults: Partial<Task>,
): Promise<Task[]> {
    const tasks = await recursiveGet<Array<unknown>, RestTask>(url);

    return tasks
        .map((task: RestTask): Task => {
            const has_subtasks =
                task.dependencies &&
                "_is_child" in task.dependencies &&
                task.dependencies._is_child.length > 0 &&
                doesTaskHaveEndDateGreaterOrEqualToStartDate(task);

            return {
                ...task,
                start: task.start ? DateTime.fromISO(task.start) : null,
                end: task.end ? DateTime.fromISO(task.end) : null,
                is_milestone: !task.start || !task.end || task.end === task.start,
                has_subtasks,
                subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED,
                subtasks: [],
                is_expanded: false,
                ...additional_defaults,
            };
        })
        .filter((task: Task): boolean => {
            if (hasTimePeriodError(task)) {
                return true;
            }

            return Boolean(task.start || task.end);
        })
        .sort((a: Task, b: Task) => {
            const start_of_a = a.start ? a.start : a.end;
            const start_of_b = b.start ? b.start : b.end;
            const end_of_a = a.end;
            const end_of_b = b.end;

            if (!start_of_a || !start_of_b) {
                if (hasTimePeriodError(a)) {
                    return 1;
                }

                if (hasTimePeriodError(b)) {
                    return -1;
                }

                // should not happen. Either:
                // - both dates are null because of a time period error
                // - or both dates are defined
                return -1;
            }

            if (end_of_a && end_of_a < start_of_a) {
                if (end_of_b && end_of_b < start_of_b) {
                    // both tasks a and b are invalid, switch to alphabetical sort
                    return a.title.localeCompare(b.title, undefined, { numeric: true });
                }
                // put invalid task a at the end
                return 1;
            }

            if (end_of_b && end_of_b < start_of_b) {
                // put invalid task b at the end
                return -1;
            }

            return start_of_a.diff(start_of_b).milliseconds;
        });
}
