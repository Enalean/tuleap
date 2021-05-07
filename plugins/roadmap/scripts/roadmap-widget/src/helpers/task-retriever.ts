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

import type { Task } from "../type";
import { recursiveGet } from "tlp";
import { SUBTASKS_WAITING_TO_BE_LOADED } from "../type";

export function retrieveAllTasks(roadmap_id: number): Promise<Task[]> {
    return retrieveAll(`/api/roadmaps/${roadmap_id}/tasks`);
}

export function retrieveAllSubtasks(task: Task): Promise<Task[]> {
    return retrieveAll("/api/" + task.subtasks_uri);
}

export async function retrieveAll(url: string): Promise<Task[]> {
    const tasks = await recursiveGet<Array<unknown>, Task>(url);

    return tasks
        .map(
            (task: Task): Task => {
                const has_subtasks =
                    task.dependencies &&
                    "_is_child" in task.dependencies &&
                    task.dependencies._is_child.length > 0;

                return {
                    ...task,
                    start: task.start ? new Date(task.start) : null,
                    end: task.end ? new Date(task.end) : null,
                    is_milestone: !task.start || !task.end || task.end === task.start,
                    has_subtasks,
                    subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED,
                    subtasks: [],
                    is_expanded: false,
                };
            }
        )
        .filter((task: Task): boolean => {
            if (!task.start && !task.end) {
                return false;
            }

            if (task.start && task.end && task.end < task.start) {
                return false;
            }

            return true;
        })
        .sort((a: Task, b: Task) => {
            const start_of_a = a.start ? a.start : a.end;
            const start_of_b = b.start ? b.start : b.end;

            if (!start_of_a || !start_of_b) {
                // should not happen, according to the filter above
                return -1;
            }

            return start_of_a.getTime() - start_of_b.getTime();
        });
}
