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
import type { Row, Task } from "../../type";

const NB_SKELETONS_FOR_SUBTASKS = 2;

export function setIsLoading(state: TasksState, is_loading: boolean): void {
    state.is_loading = is_loading;
}

export function setShouldDisplayEmptyState(
    state: TasksState,
    should_display_empty_state: boolean
): void {
    state.should_display_empty_state = should_display_empty_state;
}

export function setErrorMessage(state: TasksState, error_message: string): void {
    state.error_message = error_message;
}

export function setShouldDisplayErrorState(
    state: TasksState,
    should_display_error_state: boolean
): void {
    state.should_display_error_state = should_display_error_state;
}

export function setRows(state: TasksState, rows: Row[]): void {
    state.rows = rows;
}

export function activateIsLoadingSubtasks(state: TasksState, task: Task): void {
    const index = findTaskIndex(state, task);
    if (index === -1) {
        return;
    }

    const task_with_skeletons: Row[] = [{ task: { ...task, is_loading_subtasks: true } }];
    for (let i = 0; i < NB_SKELETONS_FOR_SUBTASKS; i++) {
        const is_last_one = i === NB_SKELETONS_FOR_SUBTASKS - 1;
        task_with_skeletons.push({ for_task: task, is_skeleton: true, is_last_one });
    }

    state.rows.splice(index, 1, ...task_with_skeletons);
}

export function deactivateIsLoadingSubtasks(state: TasksState, task: Task): void {
    const index = findTaskIndex(state, task);
    if (index === -1) {
        return;
    }

    state.rows.splice(index, 1 + NB_SKELETONS_FOR_SUBTASKS, {
        task: { ...task, is_loading_subtasks: false },
    });
}

function findTaskIndex(state: TasksState, task: Task): number {
    return state.rows.findIndex((row) => "task" in row && row.task.id === task.id);
}
