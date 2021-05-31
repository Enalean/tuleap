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

import type { SetSubtasksPayload, TasksState } from "./type";
import type { Task } from "../../type";
import {
    SUBTASKS_ARE_EMPTY,
    SUBTASKS_ARE_IN_ERROR,
    SUBTASKS_ARE_LOADED,
    SUBTASKS_ARE_LOADING,
} from "../../type";

export function setTasks(state: TasksState, tasks: Task[]): void {
    state.tasks = tasks;
}

export function expandTask(state: TasksState, task: Task): void {
    const index = findTaskIndex(state, task);
    if (index === -1) {
        return;
    }

    state.tasks.splice(index, 1, { ...state.tasks[index], is_expanded: true });
}

export function collapseTask(state: TasksState, task: Task): void {
    const index = findTaskIndex(state, task);
    if (index === -1) {
        return;
    }

    state.tasks.splice(index, 1, { ...state.tasks[index], is_expanded: false });
}

export function startLoadingSubtasks(state: TasksState, task: Task): void {
    const index = findTaskIndex(state, task);
    if (index === -1) {
        return;
    }

    state.tasks.splice(index, 1, {
        ...state.tasks[index],
        subtasks_loading_status: SUBTASKS_ARE_LOADING,
    });
}

export function finishLoadingSubtasks(state: TasksState, task: Task): void {
    const index = findTaskIndex(state, task);
    if (index === -1) {
        return;
    }

    state.tasks.splice(index, 1, {
        ...state.tasks[index],
        subtasks_loading_status: SUBTASKS_ARE_LOADED,
    });
}

export function markSubtasksAsError(state: TasksState, task: Task): void {
    const index = findTaskIndex(state, task);
    if (index === -1) {
        return;
    }

    state.tasks.splice(index, 1, {
        ...state.tasks[index],
        subtasks_loading_status: SUBTASKS_ARE_IN_ERROR,
    });
}

export function markSubtasksAsEmpty(state: TasksState, task: Task): void {
    const index = findTaskIndex(state, task);
    if (index === -1) {
        return;
    }

    state.tasks.splice(index, 1, {
        ...state.tasks[index],
        subtasks_loading_status: SUBTASKS_ARE_EMPTY,
    });
}

export function removeSubtasksDisplayForTask(state: TasksState, task: Task): void {
    const index = findTaskIndex(state, task);
    if (index === -1) {
        return;
    }

    state.tasks.splice(index, 1, {
        ...state.tasks[index],
        has_subtasks: false,
        is_expanded: false,
    });
}

export function setSubtasks(state: TasksState, payload: SetSubtasksPayload): void {
    const index = findTaskIndex(state, payload.task);
    if (index === -1) {
        return;
    }

    state.tasks.splice(index, 1, { ...state.tasks[index], subtasks: payload.subtasks });
}

function findTaskIndex(state: TasksState, task: Task): number {
    return state.tasks.findIndex((task_in_state) => task_in_state.id === task.id);
}
