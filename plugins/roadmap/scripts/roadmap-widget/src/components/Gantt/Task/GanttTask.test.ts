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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import GanttTask from "./GanttTask.vue";
import type { Task } from "../../../type";
import BackgroundGrid from "./BackgroundGrid.vue";
import TaskBar from "./TaskBar.vue";
import { Styles } from "../../../helpers/styles";
import { TimePeriodMonth } from "../../../helpers/time-period-month";
import { TasksByNature, TasksDependencies } from "../../../type";
import DependencyArrow from "./DependencyArrow.vue";
import { getDimensionsMap } from "../../../helpers/tasks-dimensions";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { TimeperiodState } from "../../../store/timeperiod/type";
import type { RootState } from "../../../store/type";
import { DateTime, Settings } from "luxon";

Settings.defaultZone = "UTC";

describe("GanttTask", () => {
    function mountGanttTask(
        task: Task,
        tasks_by_nature: TasksByNature | null = null,
        dependencies_nature_to_display: string | null = null,
        show_closed_elements: boolean | null = false,
    ): Wrapper<GanttTask> {
        const defaults: Task = {
            id: 123,
            title: "Do this",
            xref: "task #123",
            color_name: "fiesta-red",
            html_url: "/plugins/tracker?aid=123",
            progress: null,
            progress_error_message: "",
        } as Task;

        const my_task: Task = {
            ...defaults,
            ...task,
        };

        const time_period = new TimePeriodMonth(
            DateTime.fromObject({ year: 2020, month: 4, day: 3 }),
            DateTime.fromObject({ year: 2020, month: 5, day: 3 }),
            "en-US",
        );

        const dependencies = new TasksDependencies();
        if (tasks_by_nature) {
            dependencies.set(my_task, tasks_by_nature);
        }

        return shallowMount(GanttTask, {
            propsData: {
                task: my_task,
                nb_additional_units: 2,
                dimensions_map: getDimensionsMap([{ task: my_task, is_shown: true }], time_period),
                dependencies,
                dependencies_nature_to_display,
                popover_element_id: "roadmap-gantt-bar-popover-1-123",
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        timeperiod: {} as TimeperiodState,
                        show_closed_elements: show_closed_elements,
                    } as RootState,
                    getters: {
                        "timeperiod/time_period": time_period,
                    },
                }),
            },
        });
    }

    it("Displays the grid and the bar of the task", () => {
        const wrapper = mountGanttTask({
            start: DateTime.fromObject({ year: 2020, month: 4, day: 5 }),
            end: DateTime.fromObject({ year: 2020, month: 4, day: 25 }),
        } as Task);

        expect(wrapper.findComponent(BackgroundGrid).exists()).toBe(true);

        const task_bar = wrapper.findComponent(TaskBar);
        expect(task_bar.exists()).toBe(true);
        expect(task_bar.props("width")).toBe(70);
        expect(task_bar.props("left")).toBe(13);
    });

    it("Has a minimum width", () => {
        const wrapper = mountGanttTask({
            start: DateTime.fromObject({ year: 2020, month: 4, day: 5 }),
            end: DateTime.fromObject({ year: 2020, month: 4, day: 6 }),
        } as Task);

        const task_bar = wrapper.findComponent(TaskBar);
        expect(task_bar.exists()).toBe(true);
        expect(task_bar.props("width")).toBe(Styles.TASK_BAR_MIN_WIDTH_IN_PX);
    });

    it("If start = end, it is a milestone", () => {
        const wrapper = mountGanttTask({
            start: DateTime.fromObject({ year: 2020, month: 4, day: 5 }),
            end: DateTime.fromObject({ year: 2020, month: 4, day: 5 }),
        } as Task);

        const task_bar = wrapper.findComponent(TaskBar);
        expect(task_bar.exists()).toBe(true);
        expect(task_bar.props("width")).toBe(Styles.MILESTONE_WIDTH_IN_PX);
    });

    it("If end < start, it does not display the bar", () => {
        const wrapper = mountGanttTask({
            start: DateTime.fromObject({ year: 2020, month: 4, day: 5 }),
            end: DateTime.fromObject({ year: 2020, month: 1, day: 5 }),
        } as Task);

        const task_bar = wrapper.findComponent(TaskBar);
        expect(task_bar.exists()).toBe(false);
    });

    it("Displays no arrows if no dependencies", () => {
        const wrapper = mountGanttTask({
            start: DateTime.fromObject({ year: 2020, month: 4, day: 5 }),
            end: DateTime.fromObject({ year: 2020, month: 4, day: 25 }),
        } as Task);

        expect(wrapper.findComponent(DependencyArrow).exists()).toBe(false);
    });

    describe("Displays an arrow for each possible dependency", () => {
        const dep_1: Task = { id: 124, is_open: true } as Task;
        const dep_2: Task = { id: 125, is_open: true } as Task;
        const dep_3: Task = { id: 126, is_open: true } as Task;

        it.each([
            ["depends_on", [dep_1, dep_2]],
            ["", [dep_3]],
        ])("when nature is '%s'", (nature: string, expected_displayed_dependencies: Task[]) => {
            const wrapper = mountGanttTask(
                {
                    start: DateTime.fromObject({ year: 2020, month: 4, day: 5 }),
                    end: DateTime.fromObject({ year: 2020, month: 4, day: 25 }),
                } as Task,
                new TasksByNature([
                    ["depends_on", [dep_1, dep_2]],
                    ["", [dep_3]],
                ]),
                nature,
            );

            const arrows = wrapper.findAllComponents(DependencyArrow);
            expect(arrows).toHaveLength(expected_displayed_dependencies.length);

            expected_displayed_dependencies.forEach((expected, index): void => {
                expect(arrows.at(index).props("dependency")).toBe(expected);
            });
        });
    });

    it("Don't display arrow when task is closed and closed items are not shown", () => {
        const dep_3: Task = { id: 126, is_open: false } as Task;

        const wrapper = mountGanttTask(
            {
                start: DateTime.fromObject({ year: 2020, month: 4, day: 5 }),
                end: DateTime.fromObject({ year: 2020, month: 4, day: 25 }),
            } as Task,
            new TasksByNature([["", [dep_3]]]),
            "",
        );

        const arrows = wrapper.findAllComponents(DependencyArrow);
        expect(arrows).toHaveLength(0);
    });

    it("Display arrow when task is closed and closed items are not shown", () => {
        const dep_3: Task = { id: 126, is_open: false } as Task;

        const wrapper = mountGanttTask(
            {
                start: DateTime.fromObject({ year: 2020, month: 4, day: 5 }),
                end: DateTime.fromObject({ year: 2020, month: 4, day: 25 }),
            } as Task,
            new TasksByNature([["", [dep_3]]]),
            "",
            true,
        );

        const arrows = wrapper.findAllComponents(DependencyArrow);
        expect(arrows).toHaveLength(1);
    });

    it("should display an arrow even if the dependency is displayed more than once because it is a subtask with multiple parents", () => {
        const a_parent: Task = { id: 122 } as Task;
        const another_parent: Task = { id: 123 } as Task;
        const dep_1: Task = { id: 124, parent: a_parent, is_open: true } as Task;
        const dep_2: Task = { id: 124, parent: another_parent, is_open: true } as Task;

        const wrapper = mountGanttTask(
            {
                start: DateTime.fromObject({ year: 2020, month: 4, day: 5 }),
                end: DateTime.fromObject({ year: 2020, month: 4, day: 25 }),
            } as Task,
            new TasksByNature([["depends_on", [dep_1, dep_2]]]),
            "depends_on",
        );

        const arrows = wrapper.findAllComponents(DependencyArrow);
        expect(arrows).toHaveLength(2);

        expect(arrows.at(0).props("dependency")).toStrictEqual(dep_1);
        expect(arrows.at(1).props("dependency")).toStrictEqual(dep_2);
    });

    describe("percentage", () => {
        it("should round the percentage to be displayed", () => {
            const wrapper = mountGanttTask({
                start: DateTime.fromObject({ year: 2020, month: 4, day: 5 }),
                end: DateTime.fromObject({ year: 2020, month: 4, day: 6 }),
                progress: 0.42,
                is_milestone: false,
            } as Task);

            const task_bar_props = wrapper.findComponent(TaskBar).props();
            expect(task_bar_props.percentage).toBe("42%");
        });

        it("should be displayed next to the progress bar if there are enough room", () => {
            const wrapper = mountGanttTask({
                start: DateTime.fromObject({ year: 2020, month: 4, day: 5 }),
                end: DateTime.fromObject({ year: 2020, month: 7, day: 6 }),
                progress: 0.42,
                is_milestone: false,
            } as Task);

            const task_bar_props = wrapper.findComponent(TaskBar).props();
            expect(task_bar_props.is_text_displayed_inside_progress_bar).toBe(false);
            expect(task_bar_props.is_text_displayed_outside_progress_bar).toBe(true);
            expect(task_bar_props.is_text_displayed_outside_bar).toBe(false);
        });

        it("should be displayed inside the progress bar if there are not anymore enough room", () => {
            const wrapper = mountGanttTask({
                start: DateTime.fromObject({ year: 2020, month: 4, day: 5 }),
                end: DateTime.fromObject({ year: 2020, month: 7, day: 6 }),
                progress: 0.98,
                is_milestone: false,
            } as Task);

            const task_bar_props = wrapper.findComponent(TaskBar).props();
            expect(task_bar_props.is_text_displayed_inside_progress_bar).toBe(true);
            expect(task_bar_props.is_text_displayed_outside_progress_bar).toBe(false);
            expect(task_bar_props.is_text_displayed_outside_bar).toBe(false);
        });

        it("should be displayed outside of the task bar if the latter is too small", () => {
            const wrapper = mountGanttTask({
                start: DateTime.fromObject({ year: 2020, month: 4, day: 5 }),
                end: DateTime.fromObject({ year: 2020, month: 4, day: 6 }),
                progress: 0.5,
                is_milestone: false,
            } as Task);

            const task_bar_props = wrapper.findComponent(TaskBar).props();
            expect(task_bar_props.is_text_displayed_inside_progress_bar).toBe(false);
            expect(task_bar_props.is_text_displayed_outside_progress_bar).toBe(false);
            expect(task_bar_props.is_text_displayed_outside_bar).toBe(true);
        });

        it("should display nothing if there is no progress", () => {
            const wrapper = mountGanttTask({
                start: DateTime.fromObject({ year: 2020, month: 4, day: 5 }),
                end: DateTime.fromObject({ year: 2020, month: 4, day: 6 }),
                progress: null,
                is_milestone: false,
            } as Task);

            const task_bar_props = wrapper.findComponent(TaskBar).props();
            expect(task_bar_props.percentage).toBe("");
            expect(task_bar_props.is_text_displayed_inside_progress_bar).toBe(false);
            expect(task_bar_props.is_text_displayed_outside_progress_bar).toBe(false);
            expect(task_bar_props.is_text_displayed_outside_bar).toBe(false);
        });

        it("should display nothing if the task is a milestone", () => {
            const wrapper = mountGanttTask({
                start: DateTime.fromObject({ year: 2020, month: 4, day: 6 }),
                end: DateTime.fromObject({ year: 2020, month: 4, day: 6 }),
                progress: 0.5,
                is_milestone: true,
            } as Task);

            const task_bar_props = wrapper.findComponent(TaskBar).props();
            expect(task_bar_props.is_text_displayed_inside_progress_bar).toBe(false);
            expect(task_bar_props.is_text_displayed_outside_progress_bar).toBe(false);
            expect(task_bar_props.is_text_displayed_outside_bar).toBe(false);
        });
    });

    describe("progress in error", () => {
        it("should display the error sign inside the bar", () => {
            const wrapper = mountGanttTask({
                start: DateTime.fromObject({ year: 2020, month: 4, day: 5 }),
                end: DateTime.fromObject({ year: 2020, month: 7, day: 6 }),
                progress: null,
                progress_error_message: "You fucked up!",
                is_milestone: false,
            } as Task);

            const task_bar_props = wrapper.findComponent(TaskBar).props();
            expect(task_bar_props.is_error_sign_displayed_inside_bar).toBe(true);
            expect(task_bar_props.is_error_sign_displayed_outside_bar).toBe(false);
        });

        it("should display the error sign outside the bar if the bar is too small", () => {
            const wrapper = mountGanttTask({
                start: DateTime.fromObject({ year: 2020, month: 4, day: 5 }),
                end: DateTime.fromObject({ year: 2020, month: 4, day: 6 }),
                progress: null,
                progress_error_message: "You fucked up!",
                is_milestone: false,
            } as Task);

            const task_bar_props = wrapper.findComponent(TaskBar).props();
            expect(task_bar_props.is_error_sign_displayed_inside_bar).toBe(false);
            expect(task_bar_props.is_error_sign_displayed_outside_bar).toBe(true);
        });

        it("should not display an error sign if we have a milestone because it does not have any notion of progress", () => {
            const wrapper = mountGanttTask({
                start: DateTime.fromObject({ year: 2020, month: 4, day: 5 }),
                end: DateTime.fromObject({ year: 2020, month: 4, day: 5 }),
                progress: null,
                progress_error_message: "You fucked up!",
                is_milestone: true,
            } as Task);

            const task_bar_props = wrapper.findComponent(TaskBar).props();
            expect(task_bar_props.is_error_sign_displayed_inside_bar).toBe(false);
            expect(task_bar_props.is_error_sign_displayed_outside_bar).toBe(false);
        });
    });
});
