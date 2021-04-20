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

describe("GanttTask", () => {
    function mountGanttTask(
        start: Date | null,
        end: Date | null,
        tasks_by_nature: TasksByNature | null = null,
        dependencies_nature_to_display: string | null = null
    ): Wrapper<GanttTask> {
        const task: Task = {
            id: 123,
            title: "Do this",
            xref: "task #123",
            color_name: "fiesta-red",
            html_url: "/plugins/tracker?aid=123",
            start,
            end,
        } as Task;

        const time_period = new TimePeriodMonth(
            new Date(2020, 3, 3),
            new Date(2020, 4, 3),
            "en_US"
        );

        const dependencies = new TasksDependencies();
        if (tasks_by_nature) {
            dependencies.set(task, tasks_by_nature);
        }

        return shallowMount(GanttTask, {
            propsData: {
                task,
                time_period,
                nb_additional_units: 2,
                dimensions_map: getDimensionsMap([task], time_period),
                dependencies,
                dependencies_nature_to_display,
                locale: "en_US",
                popover_element_id: "roadmap-gantt-bar-popover-1-123",
            },
        });
    }

    it("Displays the grid and the bar of the task", () => {
        const wrapper = mountGanttTask(new Date(2020, 3, 5), new Date(2020, 3, 25));

        expect(wrapper.findComponent(BackgroundGrid).exists()).toBe(true);

        const task_bar = wrapper.findComponent(TaskBar);
        expect(task_bar.exists()).toBe(true);
        expect(task_bar.props("width")).toBe(70);
        expect(task_bar.props("left")).toBe(13);
    });

    it("Has a minimum width", () => {
        const wrapper = mountGanttTask(new Date(2020, 3, 5), new Date(2020, 3, 6));

        const task_bar = wrapper.findComponent(TaskBar);
        expect(task_bar.exists()).toBe(true);
        expect(task_bar.props("width")).toBe(Styles.TASK_BAR_MIN_WIDTH_IN_PX);
    });

    it("If start = end, it is a milestone", () => {
        const wrapper = mountGanttTask(new Date(2020, 3, 5), new Date(2020, 3, 5));

        const task_bar = wrapper.findComponent(TaskBar);
        expect(task_bar.exists()).toBe(true);
        expect(task_bar.props("width")).toBe(Styles.MILESTONE_WIDTH_IN_PX);
    });

    it("Doesn't know yet where to put a task without start and end date, so it puts it at the beginning of the period", () => {
        const wrapper = mountGanttTask(null, null);

        const task_bar = wrapper.findComponent(TaskBar);
        expect(task_bar.exists()).toBe(true);
        expect(task_bar.props("width")).toBe(Styles.MILESTONE_WIDTH_IN_PX);
        expect(task_bar.props("left")).toBe(0);
    });

    it("Consider a task without a start date as a milestone", () => {
        const wrapper = mountGanttTask(null, new Date(2020, 3, 25));

        const task_bar = wrapper.findComponent(TaskBar);
        expect(task_bar.exists()).toBe(true);
        expect(task_bar.props("width")).toBe(Styles.MILESTONE_WIDTH_IN_PX);
        expect(task_bar.props("left")).toBe(80);
    });

    it("Consider a task without an end date as a milestone", () => {
        const wrapper = mountGanttTask(new Date(2020, 3, 25), null);

        const task_bar = wrapper.findComponent(TaskBar);
        expect(task_bar.exists()).toBe(true);
        expect(task_bar.props("width")).toBe(Styles.MILESTONE_WIDTH_IN_PX);
        expect(task_bar.props("left")).toBe(80);
    });

    it("Displays no arrows if no dependencies", () => {
        const wrapper = mountGanttTask(new Date(2020, 3, 5), new Date(2020, 3, 25));

        expect(wrapper.findComponent(DependencyArrow).exists()).toBe(false);
    });

    describe("Displays an arrow for each possible dependency", () => {
        const dep_1: Task = { id: 124 } as Task;
        const dep_2: Task = { id: 125 } as Task;
        const dep_3: Task = { id: 126 } as Task;

        it.each([
            ["depends_on", [dep_1, dep_2]],
            ["", [dep_3]],
        ])("when nature is '%s'", (nature: string, expected_displayed_dependencies: Task[]) => {
            const wrapper = mountGanttTask(
                new Date(2020, 3, 5),
                new Date(2020, 3, 25),
                new TasksByNature([
                    ["depends_on", [dep_1, dep_2]],
                    ["", [dep_3]],
                ]),
                nature
            );

            const arrows = wrapper.findAllComponents(DependencyArrow);
            expect(arrows.length).toBe(expected_displayed_dependencies.length);

            expected_displayed_dependencies.forEach((expected, index): void => {
                expect(arrows.at(index).props("dependency")).toBe(expected);
            });
        });
    });
});
