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

export async function retrieveAllTasks(roadmap_id: number): Promise<Task[]> {
    const tasks = await recursiveGet<Array<unknown>, Task>(`/api/roadmaps/${roadmap_id}/tasks`);

    return tasks
        .map(
            (task: Task): Task => {
                return {
                    ...task,
                    start: task.start ? new Date(task.start) : null,
                    end: task.end ? new Date(task.end) : null,
                    is_milestone: !task.start || !task.end || task.end === task.start,
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
        });
}
