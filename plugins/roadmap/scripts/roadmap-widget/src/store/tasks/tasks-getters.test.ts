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
import type { Row, Task } from "../../type";
import type { TasksState } from "./type";

describe("tasks-getters", () => {
    describe("does_at_least_one_task_have_subtasks", () => {
        it("should return false if there isn't any task with subtasks", () => {
            const state = {
                rows: [
                    { task: { id: 123, has_subtasks: false } as Task },
                    { task: { id: 124, has_subtasks: false } as Task },
                    { for_task: { id: 124 } as Task, is_skeleton: true, is_last_one: true },
                    { task: { id: 125, has_subtasks: false } as Task },
                ] as Row[],
            } as TasksState;

            expect(getters.does_at_least_one_task_have_subtasks(state)).toBe(false);
        });

        it("should return true if there are some tasks with subtasks", () => {
            const state = {
                rows: [
                    { task: { id: 123, has_subtasks: false } as Task },
                    { task: { id: 124, has_subtasks: true } as Task },
                    { for_task: { id: 124 } as Task, is_skeleton: true, is_last_one: true },
                    { task: { id: 125, has_subtasks: false } as Task },
                ] as Row[],
            } as TasksState;

            expect(getters.does_at_least_one_task_have_subtasks(state)).toBe(true);
        });
    });
});
