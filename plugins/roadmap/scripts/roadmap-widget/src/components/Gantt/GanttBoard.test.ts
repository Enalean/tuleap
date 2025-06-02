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

import { DateTime, Settings } from "luxon";
import { shallowMount } from "@vue/test-utils";
import type { Wrapper } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Iteration, Row, Task, TaskRow } from "../../type";
import { createRoadmapLocalVue } from "../../helpers/local-vue-for-test";
import { TimePeriodMonth } from "../../helpers/time-period-month";
import * as rows_sorter from "../../helpers/rows-sorter";
import type { RootState } from "../../store/type";
import type { TasksState } from "../../store/tasks/type";
import type { IterationsState } from "../../store/iterations/type";
import NoDataToShowEmptyState from "../NoDataToShowEmptyState.vue";
import SubtaskSkeletonHeader from "./Subtask/SubtaskSkeletonHeader.vue";
import SubtaskMessageHeader from "./Subtask/SubtaskMessageHeader.vue";
import SubtaskSkeletonBar from "./Subtask/SubtaskSkeletonBar.vue";
import SubtaskMessage from "./Subtask/SubtaskMessage.vue";
import SubtaskHeader from "./Subtask/SubtaskHeader.vue";
import TimePeriodControl from "./TimePeriod/TimePeriodControl.vue";
import TimePeriodHeader from "./TimePeriod/TimePeriodHeader.vue";
import IterationsRibbon from "./Iteration/IterationsRibbon.vue";
import BarPopover from "./Task/BarPopover.vue";
import GanttTask from "./Task/GanttTask.vue";
import ScrollingArea from "./ScrollingArea.vue";
import GanttBoard from "./GanttBoard.vue";

Settings.defaultZone = "UTC";

window.ResizeObserver =
    window.ResizeObserver ||
    jest.fn().mockImplementation(() => ({
        disconnect: jest.fn(),
        observe: jest.fn(),
        unobserve: jest.fn(),
    }));

function getRootState(): RootState {
    return {
        now: DateTime.fromObject({ year: 2020, month: 4, day: 15 }),
        locale_bcp47: "en-US",
        should_load_lvl1_iterations: false,
        should_load_lvl2_iterations: false,
        should_display_error_state: false,
        should_display_empty_state: false,
        is_loading: false,
        error_message: "",
        iterations: {} as IterationsState,
        tasks: {} as TasksState,
        timeperiod: {
            timescale: "month",
        },
    } as RootState;
}

const task_a = { id: 1, dependencies: {} } as Task;
const task_b = { id: 2, dependencies: {} } as Task;
const task_c = { id: 3, dependencies: {} } as Task;
const task_k = { id: 11, dependencies: {} } as Task;

const task_row_a = { task: task_a, is_shown: true } as TaskRow;
const task_row_b = { task: task_b, is_shown: true } as TaskRow;
const task_row_c = { task: task_c, is_shown: true } as TaskRow;

describe("GanttBoard", () => {
    const windowResizeObserver = window.ResizeObserver;
    let has_at_least_one_row_shown: boolean,
        rows: Row[],
        lvl1_iterations_to_display: Iteration[],
        lvl2_iterations_to_display: Iteration[],
        tasks: Task[];

    beforeEach(() => {
        jest.spyOn(rows_sorter, "sortRows").mockImplementation((rows) => [...rows]);

        has_at_least_one_row_shown = true;
        rows = [];
        lvl1_iterations_to_display = [];
        lvl2_iterations_to_display = [];
        tasks = [];
    });

    afterEach(() => {
        window.ResizeObserver = windowResizeObserver;
    });

    async function getWrapper(): Promise<Wrapper<Vue>> {
        return shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            localVue: await createRoadmapLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: getRootState(),
                    getters: {
                        "tasks/has_at_least_one_row_shown": has_at_least_one_row_shown,
                        "tasks/rows": rows,
                        "tasks/tasks": tasks,
                        "iterations/lvl1_iterations_to_display": lvl1_iterations_to_display,
                        "iterations/lvl2_iterations_to_display": lvl2_iterations_to_display,
                        "timeperiod/time_period": new TimePeriodMonth(
                            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
                            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
                            "en-US",
                        ),
                    },
                }),
            },
        });
    }

    it("Displays an empty state if there is no rows", async () => {
        has_at_least_one_row_shown = false;
        const wrapper = await getWrapper();

        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(true);

        rows.push(task_row_a);
        wrapper.vm.$store.getters["tasks/has_at_least_one_row_shown"] = true;
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(false);
    });

    it("Displays no iterations if there isn't any", async () => {
        rows = [task_row_a];

        const wrapper = await getWrapper();

        expect(wrapper.findAllComponents(IterationsRibbon)).toHaveLength(0);
        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(false);
    });

    it("Displays level 1 iterations", async () => {
        rows = [task_row_a];
        lvl1_iterations_to_display = [{ id: 1 } as Iteration];

        const wrapper = await getWrapper();

        expect(wrapper.findAllComponents(IterationsRibbon)).toHaveLength(1);
    });

    it("Displays level 2 iterations", async () => {
        rows = [task_row_a];
        lvl2_iterations_to_display = [{ id: 1 } as Iteration];

        const wrapper = await getWrapper();

        expect(wrapper.findAllComponents(IterationsRibbon)).toHaveLength(1);
    });

    it("Displays levels 1 & 2 iterations", async () => {
        rows = [task_row_a];
        lvl1_iterations_to_display = [{ id: 1 } as Iteration];
        lvl2_iterations_to_display = [{ id: 2 } as Iteration];

        const wrapper = await getWrapper();

        expect(wrapper.findAllComponents(IterationsRibbon)).toHaveLength(2);
    });

    it("Displays all tasks", async () => {
        tasks = [task_a, task_b, task_c];
        rows = [task_row_a, task_row_b, task_row_c];

        const wrapper = await getWrapper();

        expect(wrapper.findAllComponents(GanttTask)).toHaveLength(3);
    });

    it("Displays subtasks skeleton", async () => {
        tasks = [task_a, task_c];
        rows = [
            task_row_a,
            {
                for_task: task_a,
                is_skeleton: true,
                is_last_one: true,
            },
            task_row_c,
        ] as Row[];

        const wrapper = await getWrapper();

        expect(wrapper.findAllComponents(GanttTask)).toHaveLength(2);
        expect(wrapper.findAllComponents(SubtaskSkeletonBar)).toHaveLength(1);
        expect(wrapper.findAllComponents(SubtaskSkeletonHeader)).toHaveLength(1);
    });

    it("Displays subtasks", async () => {
        tasks = [task_a, task_k, task_c];
        rows = [
            task_row_a,
            {
                parent: task_a,
                subtask: task_k,
                is_last_one: true,
            },
            task_row_c,
        ] as Row[];

        const wrapper = await getWrapper();

        expect(wrapper.findAllComponents(GanttTask)).toHaveLength(3);
        expect(wrapper.findAllComponents(SubtaskHeader)).toHaveLength(1);
    });

    it("Displays subtasks that can have multiple parents", async () => {
        tasks = [task_a, task_k, task_c, task_k];
        rows = [
            task_row_a,
            {
                parent: task_a,
                subtask: task_k,
                is_last_one: true,
            },
            task_row_c,
            {
                parent: task_c,
                subtask: task_k,
                is_last_one: true,
            },
        ] as Row[];

        const wrapper = await getWrapper();

        expect(wrapper.findAllComponents(GanttTask)).toHaveLength(4);
        expect(wrapper.findAllComponents(SubtaskHeader)).toHaveLength(2);

        const popover_ids = wrapper
            .findAllComponents(BarPopover)
            .wrappers.map((wrapper) => wrapper.element.id);
        const unique_popover_ids = popover_ids.filter(
            (id, index, ids) => ids.indexOf(id) === index,
        );
        expect(unique_popover_ids).toHaveLength(4);
    });

    it("Displays subtasks error message if retrieval failed", async () => {
        tasks = [task_a, task_c];
        rows = [
            task_row_a,
            {
                for_task: task_a,
                is_error: true,
                is_shown: true,
            },
            task_row_c,
        ] as Row[];

        const wrapper = await getWrapper();

        expect(wrapper.findAllComponents(GanttTask)).toHaveLength(2);
        expect(wrapper.findAllComponents(SubtaskMessageHeader)).toHaveLength(1);
        expect(wrapper.findAllComponents(SubtaskMessage)).toHaveLength(1);
    });

    it("Displays subtasks empty message if retrieval returned no subtasks", async () => {
        tasks = [task_a, task_c];
        rows = [
            task_row_a,
            {
                for_task: task_a,
                is_empty: true,
                is_shown: true,
            },
            task_row_c,
        ] as Row[];

        const wrapper = await getWrapper();

        expect(wrapper.findAllComponents(GanttTask)).toHaveLength(2);
        expect(wrapper.findAllComponents(SubtaskMessageHeader)).toHaveLength(1);
        expect(wrapper.findAllComponents(SubtaskMessage)).toHaveLength(1);
    });

    it("Observes the resize of the time period", async () => {
        const observe = jest.fn();
        const mockResizeObserver = jest.fn();
        mockResizeObserver.mockReturnValue({
            observe,
        });
        window.ResizeObserver = mockResizeObserver;

        const task_1 = {
            id: 1,
            start: DateTime.fromObject({ year: 2020, month: 4, day: 15 }),
            dependencies: {},
        } as Task;
        const task_2 = {
            id: 2,
            start: DateTime.fromObject({ year: 2020, month: 4, day: 20 }),
            dependencies: {},
        } as Task;

        tasks = [task_1, task_2];
        rows = [
            { task: task_1, is_shown: true },
            { task: task_2, is_shown: true },
        ];

        const wrapper = await getWrapper();

        const time_period = wrapper.findComponent(TimePeriodHeader);
        expect(time_period.exists()).toBe(true);
        expect(observe).toHaveBeenCalledWith(time_period.element);
    });

    it(`Given there are only closed artifacts displayed in the gantt chart
        When the user wants to show only open artifacts
        Then the gantt chart does not display anymore the timeperiod, but an empty state instead,
        And if the user wats to show back closed artifacts
        Then the timperiod is displayed again
        And the widget should make sure that it observes its resize`, async () => {
        const observe = jest.fn();
        const mockResizeObserver = jest.fn();
        mockResizeObserver.mockReturnValue({
            observe,
        });
        window.ResizeObserver = mockResizeObserver;

        rows = [task_row_a] as Row[];
        const wrapper = await getWrapper();

        const time_period = wrapper.findComponent(TimePeriodHeader);
        expect(time_period.exists()).toBe(true);
        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(false);

        // User hides closed elements
        rows.pop();
        wrapper.vm.$store.getters["tasks/has_at_least_one_row_shown"] = false;
        await wrapper.vm.$nextTick();
        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(true);

        // User shows closed elements
        rows.push(task_row_a);
        wrapper.vm.$store.getters["tasks/has_at_least_one_row_shown"] = true;
        await wrapper.vm.$nextTick();
        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(false);

        expect(observe).toHaveBeenNthCalledWith(2, time_period.element);
    });

    it("Fills the empty space of additional months if the user resize the viewport", async () => {
        const observe = jest.fn();
        const mockResizeObserver = jest.fn();
        mockResizeObserver.mockReturnValue({
            observe,
        });
        window.ResizeObserver = mockResizeObserver;

        const task_1 = {
            id: 1,
            start: DateTime.fromObject({ year: 2020, month: 4, day: 15 }),
            dependencies: {},
        } as Task;
        const task_2 = {
            id: 2,
            start: DateTime.fromObject({ year: 2020, month: 4, day: 20 }),
            dependencies: {},
        } as Task;

        tasks = [task_1, task_2];
        rows = [
            { task: task_1, is_shown: true },
            { task: task_2, is_shown: true },
        ];

        const wrapper = await getWrapper();

        const time_period_header = wrapper.findComponent(TimePeriodHeader);
        expect(time_period_header.exists()).toBe(true);
        expect(time_period_header.props("nb_additional_units")).toBe(0);

        const observerCallback = mockResizeObserver.mock.calls[0][0];
        await observerCallback([
            {
                contentRect: { width: 550 } as DOMRectReadOnly,
                target: time_period_header.element,
            } as unknown as ResizeObserverEntry,
        ]);

        expect(time_period_header.props("nb_additional_units")).toBe(2);
    });

    it("Use a different time period if user chose a different timescale", async () => {
        tasks = [task_a, task_b, task_c];
        rows = [task_row_a, task_row_b, task_row_c];

        const wrapper = await getWrapper();

        await wrapper.findComponent(TimePeriodControl).vm.$emit("input", "quarter");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("timeperiod/setTimescale", "quarter");
    });

    it("switch is-scrolling class on header so that user is knowing that some data is hidden behind the header", async () => {
        tasks = [task_a, task_b, task_c];
        rows = [task_row_a, task_row_b, task_row_c];

        const wrapper = await getWrapper();

        const header = wrapper.find("[data-test=gantt-header]");
        expect(header.classes()).not.toContain("roadmap-gantt-header-is-scrolling");

        await wrapper.findComponent(ScrollingArea).vm.$emit("is_scrolling", true);
        expect(header.classes()).toContain("roadmap-gantt-header-is-scrolling");

        await wrapper.findComponent(ScrollingArea).vm.$emit("is_scrolling", false);
        expect(header.classes()).not.toContain("roadmap-gantt-header-is-scrolling");
    });
});
