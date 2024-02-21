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

import { shallowMount } from "@vue/test-utils";
import GanttBoard from "./GanttBoard.vue";
import type { Iteration, Row, Task, TaskRow } from "../../type";
import GanttTask from "./Task/GanttTask.vue";
import TimePeriodHeader from "./TimePeriod/TimePeriodHeader.vue";
import { TimePeriodMonth } from "../../helpers/time-period-month";
import TimePeriodControl from "./TimePeriod/TimePeriodControl.vue";
import ScrollingArea from "./ScrollingArea.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { RootState } from "../../store/type";
import type { TasksState } from "../../store/tasks/type";
import SubtaskSkeletonBar from "./Subtask/SubtaskSkeletonBar.vue";
import SubtaskSkeletonHeader from "./Subtask/SubtaskSkeletonHeader.vue";
import SubtaskHeader from "./Subtask/SubtaskHeader.vue";
import SubtaskMessage from "./Subtask/SubtaskMessage.vue";
import BarPopover from "./Task/BarPopover.vue";
import SubtaskMessageHeader from "./Subtask/SubtaskMessageHeader.vue";
import type { IterationsState } from "../../store/iterations/type";
import IterationsRibbon from "./Iteration/IterationsRibbon.vue";
import NoDataToShowEmptyState from "../NoDataToShowEmptyState.vue";
import * as rows_sorter from "../../helpers/rows-sorter";
import { DateTime, Settings } from "luxon";

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

describe("GanttBoard", () => {
    const windowResizeObserver = window.ResizeObserver;

    beforeEach(() => {
        jest.spyOn(rows_sorter, "sortRows").mockImplementation((rows) => [...rows]);
    });

    afterEach(() => {
        window.ResizeObserver = windowResizeObserver;
    });

    it("Displays an empty state if there is no rows", async () => {
        const rows = [] as Row[];
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: getRootState(),
                    getters: {
                        "tasks/has_at_least_one_row_shown": false,
                        "tasks/rows": rows,
                        "tasks/tasks": [],
                        "iterations/lvl1_iterations_to_display": [],
                        "iterations/lvl2_iterations_to_display": [],
                        "timeperiod/time_period": new TimePeriodMonth(
                            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
                            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
                            "en-US",
                        ),
                    },
                }),
            },
        });

        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(true);

        rows.push({ task: { id: 1, dependencies: {} } as Task, is_shown: true });
        wrapper.vm.$store.getters["tasks/has_at_least_one_row_shown"] = true;
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(false);
    });

    it("Displays no iterations if there isn't any", () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: getRootState(),
                    getters: {
                        "tasks/has_at_least_one_row_shown": true,
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} }, is_shown: true } as TaskRow,
                        ],
                        "tasks/tasks": [],
                        "iterations/lvl1_iterations_to_display": [],
                        "iterations/lvl2_iterations_to_display": [],
                        "timeperiod/time_period": new TimePeriodMonth(
                            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
                            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
                            "en-US",
                        ),
                    },
                }),
            },
        });

        expect(wrapper.findAllComponents(IterationsRibbon)).toHaveLength(0);
        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(false);
    });

    it("Displays level 1 iterations", () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: getRootState(),
                    getters: {
                        "tasks/has_at_least_one_row_shown": true,
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} }, is_shown: true } as TaskRow,
                        ],
                        "tasks/tasks": [],
                        "iterations/lvl1_iterations_to_display": [{ id: 1 } as Iteration],
                        "iterations/lvl2_iterations_to_display": [],
                        "timeperiod/time_period": new TimePeriodMonth(
                            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
                            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
                            "en-US",
                        ),
                    },
                }),
            },
        });

        expect(wrapper.findAllComponents(IterationsRibbon)).toHaveLength(1);
    });

    it("Displays level 2 iterations", () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: getRootState(),
                    getters: {
                        "tasks/has_at_least_one_row_shown": true,
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} }, is_shown: true } as TaskRow,
                        ],
                        "tasks/tasks": [],
                        "iterations/lvl1_iterations_to_display": [],
                        "iterations/lvl2_iterations_to_display": [{ id: 1 } as Iteration],
                        "timeperiod/time_period": new TimePeriodMonth(
                            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
                            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
                            "en-US",
                        ),
                    },
                }),
            },
        });

        expect(wrapper.findAllComponents(IterationsRibbon)).toHaveLength(1);
    });

    it("Displays levels 1 & 2 iterations", () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: getRootState(),
                    getters: {
                        "tasks/has_at_least_one_row_shown": true,
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} }, is_shown: true } as TaskRow,
                        ],
                        "tasks/tasks": [],
                        "iterations/lvl1_iterations_to_display": [{ id: 1 } as Iteration],
                        "iterations/lvl2_iterations_to_display": [{ id: 2 } as Iteration],
                        "timeperiod/time_period": new TimePeriodMonth(
                            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
                            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
                            "en-US",
                        ),
                    },
                }),
            },
        });

        expect(wrapper.findAllComponents(IterationsRibbon)).toHaveLength(2);
    });

    it("Displays all tasks", () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: getRootState(),
                    getters: {
                        "tasks/has_at_least_one_row_shown": true,
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} }, is_shown: true } as TaskRow,
                            { task: { id: 2, dependencies: {} }, is_shown: true } as TaskRow,
                            { task: { id: 3, dependencies: {} }, is_shown: true } as TaskRow,
                        ],
                        "tasks/tasks": [
                            { id: 1, dependencies: {} } as Task,
                            { id: 2, dependencies: {} } as Task,
                            { id: 3, dependencies: {} } as Task,
                        ],
                        "timeperiod/time_period": new TimePeriodMonth(
                            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
                            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
                            "en-US",
                        ),
                        "iterations/lvl1_iterations_to_display": [],
                        "iterations/lvl2_iterations_to_display": [],
                    },
                }),
            },
        });

        expect(wrapper.findAllComponents(GanttTask)).toHaveLength(3);
    });

    it("Displays subtasks skeleton", () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: getRootState(),
                    getters: {
                        "tasks/has_at_least_one_row_shown": true,
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} }, is_shown: true } as TaskRow,
                            {
                                for_task: { id: 1, dependencies: {} } as Task,
                                is_skeleton: true,
                                is_last_one: true,
                            },
                            { task: { id: 3, dependencies: {} }, is_shown: true } as TaskRow,
                        ],
                        "tasks/tasks": [
                            { id: 1, dependencies: {} } as Task,
                            { id: 3, dependencies: {} } as Task,
                        ],
                        "timeperiod/time_period": new TimePeriodMonth(
                            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
                            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
                            "en-US",
                        ),
                        "iterations/lvl1_iterations_to_display": [],
                        "iterations/lvl2_iterations_to_display": [],
                    },
                }),
            },
        });

        expect(wrapper.findAllComponents(GanttTask)).toHaveLength(2);
        expect(wrapper.findAllComponents(SubtaskSkeletonBar)).toHaveLength(1);
        expect(wrapper.findAllComponents(SubtaskSkeletonHeader)).toHaveLength(1);
    });

    it("Displays subtasks", () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: getRootState(),
                    getters: {
                        "tasks/has_at_least_one_row_shown": true,
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} }, is_shown: true } as TaskRow,
                            {
                                parent: { id: 1, dependencies: {} } as Task,
                                subtask: { id: 11, dependencies: {} } as Task,
                                is_last_one: true,
                            },
                            { task: { id: 3, dependencies: {} }, is_shown: true } as TaskRow,
                        ] as Row[],
                        "tasks/tasks": [
                            { id: 1, dependencies: {} } as Task,
                            { id: 11, dependencies: {} } as Task,
                            { id: 3, dependencies: {} } as Task,
                        ],
                        "timeperiod/time_period": new TimePeriodMonth(
                            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
                            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
                            "en-US",
                        ),
                        "iterations/lvl1_iterations_to_display": [],
                        "iterations/lvl2_iterations_to_display": [],
                    },
                }),
            },
        });

        expect(wrapper.findAllComponents(GanttTask)).toHaveLength(3);
        expect(wrapper.findAllComponents(SubtaskHeader)).toHaveLength(1);
    });

    it("Displays subtasks that can have multiple parents", () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: getRootState(),
                    getters: {
                        "tasks/has_at_least_one_row_shown": true,
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} }, is_shown: true } as TaskRow,
                            {
                                parent: { id: 1, dependencies: {} } as Task,
                                subtask: { id: 11, dependencies: {} } as Task,
                                is_last_one: true,
                            },
                            { task: { id: 3, dependencies: {} }, is_shown: true } as TaskRow,
                            {
                                parent: { id: 3, dependencies: {} } as Task,
                                subtask: { id: 11, dependencies: {} } as Task,
                                is_last_one: true,
                            },
                        ] as Row[],
                        "tasks/tasks": [
                            { id: 1, dependencies: {} } as Task,
                            { id: 11, dependencies: {} } as Task,
                            { id: 3, dependencies: {} } as Task,
                            { id: 11, dependencies: {} } as Task,
                        ],
                        "timeperiod/time_period": new TimePeriodMonth(
                            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
                            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
                            "en-US",
                        ),
                        "iterations/lvl1_iterations_to_display": [],
                        "iterations/lvl2_iterations_to_display": [],
                    },
                }),
            },
        });

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

    it("Displays subtasks error message if retrieval failed", () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: getRootState(),
                    getters: {
                        "tasks/has_at_least_one_row_shown": true,
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} }, is_shown: true } as TaskRow,
                            {
                                for_task: { id: 1, dependencies: {} } as Task,
                                is_error: true,
                                is_shown: true,
                            },
                            { task: { id: 3, dependencies: {} }, is_shown: true } as TaskRow,
                        ] as Row[],
                        "tasks/tasks": [
                            { id: 1, dependencies: {} } as Task,
                            { id: 3, dependencies: {} } as Task,
                        ],
                        "timeperiod/time_period": new TimePeriodMonth(
                            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
                            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
                            "en-US",
                        ),
                        "iterations/lvl1_iterations_to_display": [],
                        "iterations/lvl2_iterations_to_display": [],
                    },
                }),
            },
        });

        expect(wrapper.findAllComponents(GanttTask)).toHaveLength(2);
        expect(wrapper.findAllComponents(SubtaskMessageHeader)).toHaveLength(1);
        expect(wrapper.findAllComponents(SubtaskMessage)).toHaveLength(1);
    });

    it("Displays subtasks empty message if retrieval returned no subtasks", () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: getRootState(),
                    getters: {
                        "tasks/has_at_least_one_row_shown": true,
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} }, is_shown: true } as TaskRow,
                            {
                                for_task: { id: 1, dependencies: {} } as Task,
                                is_empty: true,
                                is_shown: true,
                            },
                            { task: { id: 3, dependencies: {} }, is_shown: true } as TaskRow,
                        ] as Row[],
                        "tasks/tasks": [
                            { id: 1, dependencies: {} } as Task,
                            { id: 3, dependencies: {} } as Task,
                        ],
                        "timeperiod/time_period": new TimePeriodMonth(
                            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
                            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
                            "en-US",
                        ),
                        "iterations/lvl1_iterations_to_display": [],
                        "iterations/lvl2_iterations_to_display": [],
                    },
                }),
            },
        });

        expect(wrapper.findAllComponents(GanttTask)).toHaveLength(2);
        expect(wrapper.findAllComponents(SubtaskMessageHeader)).toHaveLength(1);
        expect(wrapper.findAllComponents(SubtaskMessage)).toHaveLength(1);
    });

    it("Observes the resize of the time period", () => {
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
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: getRootState(),
                    getters: {
                        "tasks/has_at_least_one_row_shown": true,
                        "tasks/rows": [
                            {
                                task: task_1,
                                is_shown: true,
                            },
                            {
                                task: task_2,
                                is_shown: true,
                            },
                        ],
                        "tasks/tasks": [task_1, task_2],
                        "timeperiod/time_period": new TimePeriodMonth(
                            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
                            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
                            "en-US",
                        ),
                        "iterations/lvl1_iterations_to_display": [],
                        "iterations/lvl2_iterations_to_display": [],
                    },
                }),
            },
        });

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

        const rows = [{ task: { id: 1, dependencies: {} }, is_shown: true } as TaskRow] as Row[];
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: getRootState(),
                    getters: {
                        "tasks/has_at_least_one_row_shown": true,
                        "tasks/rows": rows,
                        "tasks/tasks": [],
                        "iterations/lvl1_iterations_to_display": [],
                        "iterations/lvl2_iterations_to_display": [],
                        "timeperiod/time_period": new TimePeriodMonth(
                            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
                            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
                            "en-US",
                        ),
                    },
                }),
            },
        });

        const time_period = wrapper.findComponent(TimePeriodHeader);
        expect(time_period.exists()).toBe(true);
        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(false);

        // User hide closed elements
        rows.pop();
        wrapper.vm.$store.getters["tasks/has_at_least_one_row_shown"] = false;
        await wrapper.vm.$nextTick();
        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(true);

        // User show closed elements
        rows.push({ task: { id: 1, dependencies: {} } as Task, is_shown: true });
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
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: getRootState(),
                    getters: {
                        "tasks/has_at_least_one_row_shown": true,
                        "tasks/rows": [
                            {
                                task: task_1,
                                is_shown: true,
                            },
                            {
                                task: task_2,
                                is_shown: true,
                            },
                        ],
                        "tasks/tasks": [task_1, task_2],
                        "timeperiod/time_period": new TimePeriodMonth(
                            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
                            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
                            "en-US",
                        ),
                        "iterations/lvl1_iterations_to_display": [],
                        "iterations/lvl2_iterations_to_display": [],
                    },
                }),
            },
        });

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
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: getRootState(),
                    getters: {
                        "tasks/has_at_least_one_row_shown": true,
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} }, is_shown: true } as TaskRow,
                            { task: { id: 2, dependencies: {} }, is_shown: true } as TaskRow,
                            { task: { id: 3, dependencies: {} }, is_shown: true } as TaskRow,
                        ],
                        "tasks/tasks": [
                            { id: 1, dependencies: {} } as Task,
                            { id: 2, dependencies: {} } as Task,
                            { id: 3, dependencies: {} } as Task,
                        ],
                        "timeperiod/time_period": new TimePeriodMonth(
                            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
                            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
                            "en-US",
                        ),
                        "iterations/lvl1_iterations_to_display": [],
                        "iterations/lvl2_iterations_to_display": [],
                    },
                }),
            },
        });

        await wrapper.findComponent(TimePeriodControl).vm.$emit("input", "quarter");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("timeperiod/setTimescale", "quarter");
    });

    it("switch is-scrolling class on header so that user is knowing that some data is hidden behind the header", async () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: getRootState(),
                    getters: {
                        "tasks/has_at_least_one_row_shown": true,
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} }, is_shown: true } as TaskRow,
                            { task: { id: 2, dependencies: {} }, is_shown: true } as TaskRow,
                            { task: { id: 3, dependencies: {} }, is_shown: true } as TaskRow,
                        ],
                        "tasks/tasks": [
                            { id: 1, dependencies: {} } as Task,
                            { id: 2, dependencies: {} } as Task,
                            { id: 3, dependencies: {} } as Task,
                        ],
                        "timeperiod/time_period": new TimePeriodMonth(
                            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
                            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
                            "en-US",
                        ),
                        "iterations/lvl1_iterations_to_display": [],
                        "iterations/lvl2_iterations_to_display": [],
                    },
                }),
            },
        });

        const header = wrapper.find("[data-test=gantt-header]");
        expect(header.classes()).not.toContain("roadmap-gantt-header-is-scrolling");

        await wrapper.findComponent(ScrollingArea).vm.$emit("is_scrolling", true);
        expect(header.classes()).toContain("roadmap-gantt-header-is-scrolling");

        await wrapper.findComponent(ScrollingArea).vm.$emit("is_scrolling", false);
        expect(header.classes()).not.toContain("roadmap-gantt-header-is-scrolling");
    });
});
