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

import { TasksByNature, TasksDependencies } from "../type";
import type { Task } from "../type";
import { doesTaskHaveEndDateGreaterOrEqualToStartDate } from "./task-has-valid-dates";

export function getTasksDependencies(tasks: Task[]): TasksDependencies {
    const map = new TasksDependencies();
    if (tasks.length < 1) {
        return map;
    }

    for (const task of tasks) {
        addTaskDependenciesToTheMap(task, tasks, map);
    }

    return map;
}

function addTaskDependenciesToTheMap(task: Task, tasks: Task[], map: TasksDependencies): void {
    if (!doesTaskHaveEndDateGreaterOrEqualToStartDate(task)) {
        return;
    }

    for (const nature of Object.keys(task.dependencies)) {
        const dependent_tasks = getAllTasksMatchingIds(tasks, task.dependencies[nature]);
        if (dependent_tasks.length === 0) {
            continue;
        }

        const by_nature = map.get(task) || new TasksByNature();
        by_nature.set(nature, dependent_tasks);
        map.set(task, by_nature);
    }
}

function getAllTasksMatchingIds(tasks: Task[], ids: number[]): Task[] {
    return tasks.filter((task) => {
        return ids.includes(task.id) && doesTaskHaveEndDateGreaterOrEqualToStartDate(task);
    });
}
