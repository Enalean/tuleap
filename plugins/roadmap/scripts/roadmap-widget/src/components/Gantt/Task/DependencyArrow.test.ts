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
import type { Task, TaskRow } from "../../../type";
import { TimePeriodMonth } from "../../../helpers/time-period-month";
import { getDimensionsMap } from "../../../helpers/tasks-dimensions";

describe("DependencyArrow", () => {
    function mountComponent(
        task: Task,
        dependency: Task,
        tasks: Task[],
        percentage: string,
        is_text_displayed_outside_bar: boolean,
        is_error_sign_displayed_outside_bar: boolean,
    ): Wrapper<DependencyArrow> {
        const time_period = new TimePeriodMonth(
            new Date("2020-04-09T22:00:00.000Z"),
            new Date("2020-04-24T22:00:00.000Z"),
            "en-US",
        );

        return shallowMount(DependencyArrow, {
            propsData: {
                task,
                dependency,
                dimensions_map: getDimensionsMap(
                    tasks.map((task): TaskRow => ({ task, is_shown: true })),
                    time_period,
                ),
                percentage,
                is_text_displayed_outside_bar,
                is_error_sign_displayed_outside_bar,
            },
        });
    }

    it("Displays a down right arrow", () => {
        const task_1 = {
            id: 1,
            start: new Date("2020-04-09T22:00:00.000Z"),
            end: new Date("2020-04-14T22:00:00.000Z"),
        } as Task;
        const task_2 = {
            id: 2,
            start: new Date("2020-04-19T22:00:00.000Z"),
            end: new Date("2020-04-24T22:00:00.000Z"),
        } as Task;

        const wrapper = mountComponent(task_1, task_2, [task_1, task_2], "", false, false);

        expect(wrapper).toMatchInlineSnapshot(`
            <svg class="roadmap-gantt-task-dependency" style="left: 33px; top: 4px; height: 76px; width: 47px;">
              <path d="M17 17 L25 17 Q33 17, 33 25 L33 30 Q33 38, 25 38 L9 38 Q1 38, 1 46 L1 51 Q1 59, 9 59
                        L30 59
                        L25 54
                        M30 59
                        L25 64" data-test="path" class="roadmap-gantt-task-dependency-line"></path>
            </svg>
        `);
    });

    it("Displays a down left arrow", () => {
        const task_1 = {
            id: 1,
            start: new Date("2020-04-09T22:00:00.000Z"),
            end: new Date("2020-04-14T22:00:00.000Z"),
        } as Task;
        const task_2 = {
            id: 2,
            start: new Date("2020-04-19T22:00:00.000Z"),
            end: new Date("2020-04-24T22:00:00.000Z"),
        } as Task;

        const wrapper = mountComponent(task_2, task_1, [task_2, task_1], "", false, false);

        expect(wrapper).toMatchInlineSnapshot(`
            <svg class="roadmap-gantt-task-dependency" style="left: 13px; top: 4px; height: 76px; width: 87px;">
              <path d="M70 17 L78 17 Q86 17, 86 25 L86 30 Q86 38, 78 38 L9 38 Q1 38, 1 46 L1 51 Q1 59, 9 59
                        L17 59
                        L12 54
                        M17 59
                        L12 64" data-test="path" class="roadmap-gantt-task-dependency-line roadmap-gantt-task-dependency-line-ends-after-start"></path>
            </svg>
        `);
    });

    it("Displays an up left arrow", () => {
        const task_1 = {
            id: 1,
            start: new Date("2020-04-09T22:00:00.000Z"),
            end: new Date("2020-04-14T22:00:00.000Z"),
        } as Task;
        const task_2 = {
            id: 2,
            start: new Date("2020-04-19T22:00:00.000Z"),
            end: new Date("2020-04-24T22:00:00.000Z"),
        } as Task;

        const wrapper = mountComponent(task_2, task_1, [task_1, task_2], "", false, false);

        expect(wrapper).toMatchInlineSnapshot(`
            <svg class="roadmap-gantt-task-dependency" style="left: 13px; top: -38px; height: 76px; width: 87px;">
              <path d="M70 59 L78 59 Q86 59, 86 51 L86 46 Q86 38, 78 38 L9 38 Q1 38, 1 30 L1 25 Q1 17, 9 17
                        L17 17
                        L12 12
                        M17 17
                        L12 22" data-test="path" class="roadmap-gantt-task-dependency-line roadmap-gantt-task-dependency-line-ends-after-start"></path>
            </svg>
        `);
    });

    it("Displays an up right arrow", () => {
        const task_1 = {
            id: 1,
            start: new Date("2020-04-09T22:00:00.000Z"),
            end: new Date("2020-04-14T22:00:00.000Z"),
        } as Task;
        const task_2 = {
            id: 2,
            start: new Date("2020-04-19T22:00:00.000Z"),
            end: new Date("2020-04-24T22:00:00.000Z"),
        } as Task;

        const wrapper = mountComponent(task_1, task_2, [task_2, task_1], "", false, false);

        expect(wrapper).toMatchInlineSnapshot(`
            <svg class="roadmap-gantt-task-dependency" style="left: 33px; top: -38px; height: 76px; width: 47px;">
              <path d="M17 59 L25 59 Q33 59, 33 51 L33 46 Q33 38, 25 38 L9 38 Q1 38, 1 30 L1 25 Q1 17, 9 17
                        L30 17
                        L25 12
                        M30 17
                        L25 22" data-test="path" class="roadmap-gantt-task-dependency-line"></path>
            </svg>
        `);
    });

    it("should starts the path after the progress percentage text if it is displayed outside of the bar, but does not consider that the task ends after its dependency start", async () => {
        const task_1 = {
            id: 1,
            start: new Date("2020-04-09T22:00:00.000Z"),
            end: new Date("2020-04-14T22:00:00.000Z"),
        } as Task;
        const task_2 = {
            id: 2,
            start: new Date("2020-04-15T22:00:00.000Z"),
            end: new Date("2020-04-24T22:00:00.000Z"),
        } as Task;

        const wrapper = mountComponent(task_1, task_2, [task_1, task_2], "42%", false, false);

        expect((wrapper.element as HTMLElement).style.left).toBe("33px");
        expect((wrapper.element as HTMLElement).style.width).toBe("34px");
        expect(wrapper.find("[data-test=path]").classes()).not.toContain(
            "roadmap-gantt-task-dependency-line-ends-after-start",
        );

        await wrapper.setProps({ is_text_displayed_outside_bar: true });
        expect((wrapper.element as HTMLElement).style.left).toBe("33px");
        expect((wrapper.element as HTMLElement).style.width).toBe("64px");
        expect(wrapper.find("[data-test=path]").classes()).not.toContain(
            "roadmap-gantt-task-dependency-line-ends-after-start",
        );
    });

    it("should starts the path after the progress error sign if it is displayed outside of the bar, but does not consider that the task ends after its dependency start", async () => {
        const task_1 = {
            id: 1,
            start: new Date("2020-04-09T22:00:00.000Z"),
            end: new Date("2020-04-14T22:00:00.000Z"),
            progress_error_message: "You fucked up!",
        } as Task;
        const task_2 = {
            id: 2,
            start: new Date("2020-04-15T22:00:00.000Z"),
            end: new Date("2020-04-24T22:00:00.000Z"),
        } as Task;

        const wrapper = mountComponent(task_1, task_2, [task_1, task_2], "42%", false, true);

        expect((wrapper.element as HTMLElement).style.left).toBe("33px");
        expect((wrapper.element as HTMLElement).style.width).toBe("56px");
        expect(wrapper.find("[data-test=path]").classes()).not.toContain(
            "roadmap-gantt-task-dependency-line-ends-after-start",
        );

        await wrapper.setProps({ is_text_displayed_outside_bar: true });
        expect((wrapper.element as HTMLElement).style.left).toBe("33px");
        expect((wrapper.element as HTMLElement).style.width).toBe("56px");
        expect(wrapper.find("[data-test=path]").classes()).not.toContain(
            "roadmap-gantt-task-dependency-line-ends-after-start",
        );
    });
});
