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

import type { TasksState } from "./type";
import type { Row, Task, SubtaskRow, TaskRow } from "../../type";
import {
    SUBTASKS_ARE_EMPTY,
    SUBTASKS_ARE_IN_ERROR,
    SUBTASKS_ARE_LOADED,
    SUBTASKS_ARE_LOADING,
} from "../../type";

const NB_SKELETONS_FOR_SUBTASKS = 2;

export const does_at_least_one_task_have_subtasks = (state: TasksState): boolean => {
    return state.tasks.some((task) => task.has_subtasks);
};

export const rows = (state: TasksState): Row[] => {
    return state.tasks.reduce((rows: Row[], task): Row[] => {
        rows.push({ task: task });
        if (!task.is_expanded) {
            return rows;
        }

        if (task.subtasks_loading_status === SUBTASKS_ARE_LOADING) {
            for (let i = 0; i < NB_SKELETONS_FOR_SUBTASKS; i++) {
                const is_last_one = i === NB_SKELETONS_FOR_SUBTASKS - 1;
                rows.push({ for_task: task, is_skeleton: true, is_last_one });
            }

            return rows;
        }

        if (task.subtasks_loading_status === SUBTASKS_ARE_IN_ERROR) {
            rows.push({ for_task: task, is_error: true });

            return rows;
        }

        if (task.subtasks_loading_status === SUBTASKS_ARE_EMPTY) {
            rows.push({ for_task: task, is_empty: true });

            return rows;
        }

        if (task.subtasks_loading_status === SUBTASKS_ARE_LOADED) {
            const nb_subtasks = task.subtasks.length;
            task.subtasks.forEach((subtask, index) => {
                const is_last_one = index === nb_subtasks - 1;
                rows.push({ subtask, parent: task, is_last_one });
            });
        }

        return rows;
    }, []);
};

export const tasks = (state: unknown, { rows }: { rows: Row[] }): Task[] => {
    return rows.reduce((tasks: Task[], row: Row) => {
        if (isTaskRow(row)) {
            tasks.push(row.task);
        }

        if (isSubtaskRow(row)) {
            tasks.push(row.subtask);
        }

        return tasks;
    }, []);
};

function isTaskRow(row: Row): row is TaskRow {
    return "task" in row;
}

function isSubtaskRow(row: Row): row is SubtaskRow {
    return "subtask" in row;
}
