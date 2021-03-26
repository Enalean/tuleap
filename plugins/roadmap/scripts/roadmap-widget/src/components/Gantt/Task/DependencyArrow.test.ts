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

import DependencyArrow from "./DependencyArrow.vue";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { Task } from "../../../type";
import { TimePeriodMonth } from "../../../helpers/time-period-month";

describe("DependencyArrow", () => {
    function mountComponent(task: Task, dependency: Task, tasks: Task[]): Wrapper<DependencyArrow> {
        return shallowMount(DependencyArrow, {
            propsData: {
                task,
                dependency,
                tasks,
                time_period: new TimePeriodMonth(
                    new Date(2020, 3, 10),
                    new Date(2020, 3, 25),
                    new Date(2020, 3, 7),
                    "en_US"
                ),
            },
        });
    }

    it("Displays a down right arrow", () => {
        const task_1 = {
            id: 1,
            start: new Date(2020, 3, 10),
            end: new Date(2020, 3, 15),
        } as Task;
        const task_2 = {
            id: 2,
            start: new Date(2020, 3, 20),
            end: new Date(2020, 3, 25),
        } as Task;

        const wrapper = mountComponent(task_1, task_2, [task_1, task_2]);

        expect(wrapper).toMatchInlineSnapshot(`
            <svg class="roadmap-gantt-task-dependency" style="left: 23px; top: -4px; height: 88px; width: 64px;">
              <path d="M24 24 L32 24 Q40 24, 40 32 L40 36 Q40 44, 32 44 L16 44 Q8 44, 8 52 L8 56 Q8 64, 16 64
                        L40 64
                        L32 56
                        M40 64
                        L32 72" class="roadmap-gantt-task-dependency-line"></path>
            </svg>
        `);
    });

    it("Displays a down left arrow", () => {
        const task_1 = {
            id: 1,
            start: new Date(2020, 3, 10),
            end: new Date(2020, 3, 15),
        } as Task;
        const task_2 = {
            id: 2,
            start: new Date(2020, 3, 20),
            end: new Date(2020, 3, 25),
        } as Task;

        const wrapper = mountComponent(task_2, task_1, [task_2, task_1]);

        expect(wrapper).toMatchInlineSnapshot(`
            <svg class="roadmap-gantt-task-dependency" style="left: 6px; top: -4px; height: 88px; width: 98px;">
              <path d="M74 24 L82 24 Q90 24, 90 32 L90 36 Q90 44, 82 44 L16 44 Q8 44, 8 52 L8 56 Q8 64, 16 64
                        L24 64
                        L16 56
                        M24 64
                        L16 72" class="roadmap-gantt-task-dependency-line"></path>
            </svg>
        `);
    });

    it("Displays an up left arrow", () => {
        const task_1 = {
            id: 1,
            start: new Date(2020, 3, 10),
            end: new Date(2020, 3, 15),
        } as Task;
        const task_2 = {
            id: 2,
            start: new Date(2020, 3, 20),
            end: new Date(2020, 3, 25),
        } as Task;

        const wrapper = mountComponent(task_2, task_1, [task_1, task_2]);

        expect(wrapper).toMatchInlineSnapshot(`
            <svg class="roadmap-gantt-task-dependency" style="left: 6px; top: -44px; height: 88px; width: 98px;">
              <path d="M74 64 L82 64 Q90 64, 90 56 L90 52 Q90 44, 82 44 L16 44 Q8 44, 8 36 L8 32 Q8 24, 16 24
                        L24 24
                        L16 16
                        M24 24
                        L16 32" class="roadmap-gantt-task-dependency-line"></path>
            </svg>
        `);
    });

    it("Displays an up right arrow", () => {
        const task_1 = {
            id: 1,
            start: new Date(2020, 3, 10),
            end: new Date(2020, 3, 15),
        } as Task;
        const task_2 = {
            id: 2,
            start: new Date(2020, 3, 20),
            end: new Date(2020, 3, 25),
        } as Task;

        const wrapper = mountComponent(task_1, task_2, [task_2, task_1]);

        expect(wrapper).toMatchInlineSnapshot(`
            <svg class="roadmap-gantt-task-dependency" style="left: 23px; top: -44px; height: 88px; width: 64px;">
              <path d="M24 64 L32 64 Q40 64, 40 56 L40 52 Q40 44, 32 44 L16 44 Q8 44, 8 36 L8 32 Q8 24, 16 24
                        L40 24
                        L32 16
                        M40 24
                        L32 32" class="roadmap-gantt-task-dependency-line"></path>
            </svg>
        `);
    });
});
