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

import * as getters from "./tasks-getters";
import type {
    EmptySubtasksRow,
    ErrorRow,
    Row,
    SkeletonRow,
    SubtaskRow,
    Task,
    TaskRow,
} from "../../type";
import type { TasksState } from "./type";
import {
    SUBTASKS_ARE_EMPTY,
    SUBTASKS_ARE_IN_ERROR,
    SUBTASKS_ARE_LOADED,
    SUBTASKS_ARE_LOADING,
    SUBTASKS_WAITING_TO_BE_LOADED,
} from "../../type";

describe("tasks-getters", () => {
    describe("does_at_least_one_task_have_subtasks", () => {
        it("should return false if there isn't any task with subtasks", () => {
            const state = {
                tasks: [
                    { id: 123, has_subtasks: false } as Task,
                    { id: 124, has_subtasks: false } as Task,
                    { id: 125, has_subtasks: false } as Task,
                ],
            } as TasksState;

            expect(getters.does_at_least_one_task_have_subtasks(state)).toBe(false);
        });

        it("should return true if there are some tasks with subtasks", () => {
            const state = {
                tasks: [
                    { id: 123, has_subtasks: false } as Task,
                    { id: 124, has_subtasks: true } as Task,
                    { id: 125, has_subtasks: false } as Task,
                ],
            } as TasksState;

            expect(getters.does_at_least_one_task_have_subtasks(state)).toBe(true);
        });
    });

    describe("rows", () => {
        it("should returns a row for each task", () => {
            const task_1 = {
                id: 123,
                is_expanded: false,
                subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED,
            } as Task;

            const task_2 = {
                id: 124,
                is_expanded: false,
                subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED,
            } as Task;

            const task_3 = {
                id: 125,
                is_expanded: false,
                subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED,
            } as Task;

            const state = {
                tasks: [task_1, task_2, task_3],
            } as TasksState;

            expect(getters.rows(state)).toStrictEqual([
                { task: task_1 },
                { task: task_2 },
                { task: task_3 },
            ] as Row[]);
        });

        it("should add skeleton rows when needed", () => {
            const task_1 = {
                id: 123,
                is_expanded: false,
                subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED,
            } as Task;

            const task_2 = {
                id: 124,
                is_expanded: true,
                subtasks_loading_status: SUBTASKS_ARE_LOADING,
            } as Task;

            const task_3 = {
                id: 125,
                is_expanded: false,
                subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED,
            } as Task;

            const state = {
                tasks: [task_1, task_2, task_3],
            } as TasksState;

            expect(getters.rows(state)).toStrictEqual([
                { task: task_1 },
                { task: task_2 },
                { for_task: task_2, is_skeleton: true, is_last_one: false },
                { for_task: task_2, is_skeleton: true, is_last_one: true },
                { task: task_3 },
            ] as Row[]);
        });

        it("should not add skeleton rows when subtasks are loading but task is collapsed", () => {
            const task_1 = {
                id: 123,
                is_expanded: false,
                subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED,
            } as Task;

            const task_2 = {
                id: 124,
                is_expanded: false,
                subtasks_loading_status: SUBTASKS_ARE_LOADING,
            } as Task;

            const task_3 = {
                id: 125,
                is_expanded: false,
                subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED,
            } as Task;

            const state = {
                tasks: [task_1, task_2, task_3],
            } as TasksState;

            expect(getters.rows(state)).toStrictEqual([
                { task: task_1 },
                { task: task_2 },
                { task: task_3 },
            ] as Row[]);
        });

        it("should display subtasks when loaded and task is expanded", () => {
            const task_1 = {
                id: 123,
                is_expanded: false,
                subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED,
            } as Task;

            const task_2 = {
                id: 124,
                is_expanded: true,
                subtasks_loading_status: SUBTASKS_ARE_LOADED,
                subtasks: [{ id: 1241 }, { id: 1242 }] as Task[],
            } as Task;

            const task_3 = {
                id: 125,
                is_expanded: false,
                subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED,
            } as Task;

            const state = {
                tasks: [task_1, task_2, task_3],
            } as TasksState;

            expect(getters.rows(state)).toStrictEqual([
                { task: task_1 },
                { task: task_2 },
                { parent: task_2, subtask: { id: 1241 }, is_last_one: false },
                { parent: task_2, subtask: { id: 1242 }, is_last_one: true },
                { task: task_3 },
            ] as Row[]);
        });

        it("should display error message if retrieval of subtasks failed", () => {
            const task_1 = {
                id: 123,
                is_expanded: false,
                subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED,
            } as Task;

            const task_2 = {
                id: 124,
                is_expanded: true,
                subtasks_loading_status: SUBTASKS_ARE_IN_ERROR,
            } as Task;

            const task_3 = {
                id: 125,
                is_expanded: false,
                subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED,
            } as Task;

            const state = {
                tasks: [task_1, task_2, task_3],
            } as TasksState;

            expect(getters.rows(state)).toStrictEqual([
                { task: task_1 },
                { task: task_2 },
                { for_task: task_2, is_error: true },
                { task: task_3 },
            ] as Row[]);
        });

        it("should display empty message if subtasks collection appears to be empty", () => {
            const task_1 = {
                id: 123,
                is_expanded: false,
                subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED,
            } as Task;

            const task_2 = {
                id: 124,
                is_expanded: true,
                subtasks_loading_status: SUBTASKS_ARE_EMPTY,
            } as Task;

            const task_3 = {
                id: 125,
                is_expanded: false,
                subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED,
            } as Task;

            const state = {
                tasks: [task_1, task_2, task_3],
            } as TasksState;

            expect(getters.rows(state)).toStrictEqual([
                { task: task_1 },
                { task: task_2 },
                { for_task: task_2, is_empty: true },
                { task: task_3 },
            ] as Row[]);
        });
    });

    it("should not display subtasks when loaded but task is collapsed", () => {
        const task_1 = {
            id: 123,
            is_expanded: false,
            subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED,
        } as Task;

        const task_2 = {
            id: 124,
            is_expanded: false,
            subtasks_loading_status: SUBTASKS_ARE_LOADED,
            subtasks: [{ id: 1241 }, { id: 1242 }] as Task[],
        } as Task;

        const task_3 = {
            id: 125,
            is_expanded: false,
            subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED,
        } as Task;

        const state = {
            tasks: [task_1, task_2, task_3],
        } as TasksState;

        expect(getters.rows(state)).toStrictEqual([
            { task: task_1 },
            { task: task_2 },
            { task: task_3 },
        ] as Row[]);
    });

    describe("tasks", () => {
        it("extract task and subtasks from rows", () => {
            const task_row: TaskRow = { task: { id: 123 } as Task };
            const skeleton_row: SkeletonRow = { is_skeleton: true } as SkeletonRow;
            const empty_subtask_row: EmptySubtasksRow = { is_empty: true } as EmptySubtasksRow;
            const error_row: ErrorRow = { is_error: true } as ErrorRow;
            const subtask_row: SubtaskRow = { subtask: { id: 234 } as Task } as SubtaskRow;

            const tasks = getters.tasks(
                {},
                {
                    rows: [
                        task_row,
                        skeleton_row,
                        empty_subtask_row,
                        error_row,
                        error_row,
                        subtask_row,
                    ],
                }
            );

            expect(tasks).toStrictEqual([{ id: 123 }, { id: 234 }]);
        });
    });
});
