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
import * as mutations from "./tasks-mutations";
import type { Task } from "../../type";
import {
    SUBTASKS_ARE_EMPTY,
    SUBTASKS_ARE_IN_ERROR,
    SUBTASKS_ARE_LOADED,
    SUBTASKS_ARE_LOADING,
    SUBTASKS_WAITING_TO_BE_LOADED,
} from "../../type";

describe("tasks-mutations", () => {
    it("setTasks stores the tasks", () => {
        const state: TasksState = {
            tasks: [] as Task[],
        } as TasksState;

        const tasks: Task[] = [{ id: 123 } as Task, { id: 124 } as Task];
        mutations.setTasks(state, tasks);

        expect(state.tasks).toBe(tasks);
    });

    it("expandTask marks the task as being expanded", () => {
        const state: TasksState = {
            tasks: [
                { id: 123, is_expanded: false },
                { id: 124, is_expanded: false },
                { id: 125, is_expanded: false },
            ] as Task[],
        } as TasksState;

        mutations.expandTask(state, { id: 124 } as Task);

        expect(state.tasks[0].is_expanded).toBe(false);
        expect(state.tasks[1].is_expanded).toBe(true);
        expect(state.tasks[2].is_expanded).toBe(false);
    });

    it("collapseTask remove the task from being expanded", () => {
        const state: TasksState = {
            tasks: [
                { id: 123, is_expanded: true },
                { id: 124, is_expanded: true },
                { id: 125, is_expanded: true },
            ] as Task[],
        } as TasksState;

        mutations.collapseTask(state, { id: 124 } as Task);

        expect(state.tasks[0].is_expanded).toBe(true);
        expect(state.tasks[1].is_expanded).toBe(false);
        expect(state.tasks[2].is_expanded).toBe(true);
    });

    it("startLoadingSubtasks should switch the status to loading", () => {
        const state: TasksState = {
            tasks: [
                { id: 123, subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED },
                { id: 124, subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED },
                { id: 125, subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED },
            ] as Task[],
        } as TasksState;

        mutations.startLoadingSubtasks(state, { id: 124 } as Task);

        expect(state.tasks[0].subtasks_loading_status).toBe(SUBTASKS_WAITING_TO_BE_LOADED);
        expect(state.tasks[1].subtasks_loading_status).toBe(SUBTASKS_ARE_LOADING);
        expect(state.tasks[2].subtasks_loading_status).toBe(SUBTASKS_WAITING_TO_BE_LOADED);
    });

    it("finishLoadingSubtasks should switch the status to loading", () => {
        const state: TasksState = {
            tasks: [
                { id: 123, subtasks_loading_status: SUBTASKS_ARE_LOADING },
                { id: 124, subtasks_loading_status: SUBTASKS_ARE_LOADING },
                { id: 125, subtasks_loading_status: SUBTASKS_ARE_LOADING },
            ] as Task[],
        } as TasksState;

        mutations.finishLoadingSubtasks(state, { id: 124 } as Task);

        expect(state.tasks[0].subtasks_loading_status).toBe(SUBTASKS_ARE_LOADING);
        expect(state.tasks[1].subtasks_loading_status).toBe(SUBTASKS_ARE_LOADED);
        expect(state.tasks[2].subtasks_loading_status).toBe(SUBTASKS_ARE_LOADING);
    });

    it("markSubtasksAsError should switch the status to error", () => {
        const state: TasksState = {
            tasks: [
                { id: 123, subtasks_loading_status: SUBTASKS_ARE_LOADING },
                { id: 124, subtasks_loading_status: SUBTASKS_ARE_LOADING },
                { id: 125, subtasks_loading_status: SUBTASKS_ARE_LOADING },
            ] as Task[],
        } as TasksState;

        mutations.markSubtasksAsError(state, { id: 124 } as Task);

        expect(state.tasks[0].subtasks_loading_status).toBe(SUBTASKS_ARE_LOADING);
        expect(state.tasks[1].subtasks_loading_status).toBe(SUBTASKS_ARE_IN_ERROR);
        expect(state.tasks[2].subtasks_loading_status).toBe(SUBTASKS_ARE_LOADING);
    });

    it("markSubtasksAsEmpty should switch the status to empty", () => {
        const state: TasksState = {
            tasks: [
                { id: 123, subtasks_loading_status: SUBTASKS_ARE_LOADING },
                { id: 124, subtasks_loading_status: SUBTASKS_ARE_LOADING },
                { id: 125, subtasks_loading_status: SUBTASKS_ARE_LOADING },
            ] as Task[],
        } as TasksState;

        mutations.markSubtasksAsEmpty(state, { id: 124 } as Task);

        expect(state.tasks[0].subtasks_loading_status).toBe(SUBTASKS_ARE_LOADING);
        expect(state.tasks[1].subtasks_loading_status).toBe(SUBTASKS_ARE_EMPTY);
        expect(state.tasks[2].subtasks_loading_status).toBe(SUBTASKS_ARE_LOADING);
    });

    it("removeSubtasksDisplayForTask should disallow to expand the task again", () => {
        const state: TasksState = {
            tasks: [
                {
                    id: 124,
                    subtasks_loading_status: SUBTASKS_ARE_EMPTY,
                    has_subtasks: true,
                    is_expanded: true,
                },
            ] as Task[],
        } as TasksState;

        mutations.removeSubtasksDisplayForTask(state, { id: 124 } as Task);

        expect(state.tasks[0].has_subtasks).toBe(false);
        expect(state.tasks[0].is_expanded).toBe(false);
    });

    it("setSubtasks stores the subtasks of a task", () => {
        const state: TasksState = {
            tasks: [
                { id: 123, subtasks: [] as Task[] },
                { id: 124, subtasks: [] as Task[] },
                { id: 125, subtasks: [] as Task[] },
            ] as Task[],
        } as TasksState;

        const subtasks = [{ id: 125 }, { id: 126 }] as Task[];
        mutations.setSubtasks(state, { task: { id: 124 } as Task, subtasks });

        expect(state.tasks[0].subtasks).toStrictEqual([]);
        expect(state.tasks[1].subtasks).toStrictEqual(subtasks);
        expect(state.tasks[2].subtasks).toStrictEqual([]);
    });

    it("chains mutations without overriding previous ones", () => {
        const task = { id: 123, subtasks: [] as Task[], is_expanded: false } as Task;
        const state: TasksState = {
            tasks: [task],
        } as TasksState;

        const subtasks = [{ id: 124 } as Task];

        mutations.expandTask(state, task);
        mutations.setSubtasks(state, { task, subtasks });

        expect(state.tasks[0].subtasks).toStrictEqual(subtasks);
        expect(state.tasks[0].is_expanded).toBe(true);
    });
});
