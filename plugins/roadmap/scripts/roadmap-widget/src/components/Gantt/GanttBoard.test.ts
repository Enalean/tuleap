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
import type { Row, Task } from "../../type";
import GanttTask from "./Task/GanttTask.vue";
import TimePeriodHeader from "./TimePeriod/TimePeriodHeader.vue";
import { TimePeriodMonth } from "../../helpers/time-period-month";
import TimePeriodControl from "./TimePeriod/TimePeriodControl.vue";
import { TimePeriodQuarter } from "../../helpers/time-period-quarter";
import ScrollingArea from "./ScrollingArea.vue";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import type { RootState } from "../../store/type";
import type { TasksState } from "../../store/tasks/type";
import SubtaskSkeletonBar from "./Subtask/SubtaskSkeletonBar.vue";
import SubtaskSkeletonHeader from "./Subtask/SubtaskSkeletonHeader.vue";
import SubtaskHeader from "./Subtask/SubtaskHeader.vue";
import SubtaskMessage from "./Subtask/SubtaskMessage.vue";
import BarPopover from "./Task/BarPopover.vue";
import SubtaskMessageHeader from "./Subtask/SubtaskMessageHeader.vue";

window.ResizeObserver =
    window.ResizeObserver ||
    jest.fn().mockImplementation(() => ({
        disconnect: jest.fn(),
        observe: jest.fn(),
        unobserve: jest.fn(),
    }));

describe("GanttBoard", () => {
    const windowResizeObserver = window.ResizeObserver;

    afterEach(() => {
        window.ResizeObserver = windowResizeObserver;
    });

    it("Displays all tasks", () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                        tasks: {} as TasksState,
                    } as RootState,
                    getters: {
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} } as Task },
                            { task: { id: 2, dependencies: {} } as Task },
                            { task: { id: 3, dependencies: {} } as Task },
                        ],
                    },
                }),
            },
        });

        expect(wrapper.findAllComponents(GanttTask).length).toBe(3);
    });

    it("Displays subtasks skeleton", () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                        tasks: {} as TasksState,
                    } as RootState,
                    getters: {
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} } as Task },
                            {
                                for_task: { id: 1, dependencies: {} } as Task,
                                is_skeleton: true,
                                is_last_one: true,
                            },
                            { task: { id: 3, dependencies: {} } as Task },
                        ],
                    },
                }),
            },
        });

        expect(wrapper.findAllComponents(GanttTask).length).toBe(2);
        expect(wrapper.findAllComponents(SubtaskSkeletonBar).length).toBe(1);
        expect(wrapper.findAllComponents(SubtaskSkeletonHeader).length).toBe(1);
    });

    it("Displays subtasks", () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                        tasks: {} as TasksState,
                    } as RootState,
                    getters: {
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} } as Task },
                            {
                                parent: { id: 1, dependencies: {} } as Task,
                                subtask: { id: 11, dependencies: {} } as Task,
                                is_last_one: true,
                            },
                            { task: { id: 3, dependencies: {} } as Task },
                        ] as Row[],
                    },
                }),
            },
        });

        expect(wrapper.findAllComponents(GanttTask).length).toBe(3);
        expect(wrapper.findAllComponents(SubtaskHeader).length).toBe(1);
    });

    it("Displays subtasks that can have multiple parents", () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                        tasks: {} as TasksState,
                    } as RootState,
                    getters: {
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} } as Task },
                            {
                                parent: { id: 1, dependencies: {} } as Task,
                                subtask: { id: 11, dependencies: {} } as Task,
                                is_last_one: true,
                            },
                            { task: { id: 3, dependencies: {} } as Task },
                            {
                                parent: { id: 3, dependencies: {} } as Task,
                                subtask: { id: 11, dependencies: {} } as Task,
                                is_last_one: true,
                            },
                        ] as Row[],
                    },
                }),
            },
        });

        expect(wrapper.findAllComponents(GanttTask).length).toBe(4);
        expect(wrapper.findAllComponents(SubtaskHeader).length).toBe(2);

        const popover_ids = wrapper
            .findAllComponents(BarPopover)
            .wrappers.map((wrapper) => wrapper.element.id);
        const unique_popover_ids = popover_ids.filter(
            (id, index, ids) => ids.indexOf(id) === index
        );
        expect(unique_popover_ids.length).toBe(4);
    });

    it("Displays subtasks error message if retrieval failed", () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                        tasks: {} as TasksState,
                    } as RootState,
                    getters: {
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} } as Task },
                            {
                                for_task: { id: 1, dependencies: {} } as Task,
                                is_error: true,
                            },
                            { task: { id: 3, dependencies: {} } as Task },
                        ] as Row[],
                    },
                }),
            },
        });

        expect(wrapper.findAllComponents(GanttTask).length).toBe(2);
        expect(wrapper.findAllComponents(SubtaskMessageHeader).length).toBe(1);
        expect(wrapper.findAllComponents(SubtaskMessage).length).toBe(1);
    });

    it("Displays subtasks empty message if retrieval returned no subtasks", () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                        tasks: {} as TasksState,
                    } as RootState,
                    getters: {
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} } as Task },
                            {
                                for_task: { id: 1, dependencies: {} } as Task,
                                is_empty: true,
                            },
                            { task: { id: 3, dependencies: {} } as Task },
                        ] as Row[],
                    },
                }),
            },
        });

        expect(wrapper.findAllComponents(GanttTask).length).toBe(2);
        expect(wrapper.findAllComponents(SubtaskMessageHeader).length).toBe(1);
        expect(wrapper.findAllComponents(SubtaskMessage).length).toBe(1);
    });

    it("Displays months according to tasks", async () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                        tasks: {} as TasksState,
                    } as RootState,
                    getters: {
                        "tasks/rows": [
                            {
                                task: {
                                    id: 1,
                                    start: new Date(2020, 3, 15),
                                    dependencies: {},
                                } as Task,
                            },
                            {
                                task: {
                                    id: 2,
                                    start: new Date(2020, 3, 20),
                                    dependencies: {},
                                } as Task,
                            },
                        ],
                    },
                }),
            },
        });

        wrapper.setData({
            now: new Date(2020, 3, 15),
        });
        await wrapper.vm.$nextTick();

        const time_period_header = wrapper.findComponent(TimePeriodHeader);
        expect(time_period_header.exists()).toBe(true);
        expect(
            time_period_header.props("time_period").units.map((month: Date) => month.toDateString())
        ).toStrictEqual(["Sun Mar 01 2020", "Wed Apr 01 2020", "Fri May 01 2020"]);
        expect(time_period_header.props("nb_additional_units")).toBe(0);
    });

    it("Observes the resize of the time period", () => {
        const observe = jest.fn();
        const mockResizeObserver = jest.fn();
        mockResizeObserver.mockReturnValue({
            observe,
        });
        window.ResizeObserver = mockResizeObserver;

        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                        tasks: {} as TasksState,
                    } as RootState,
                    getters: {
                        "tasks/rows": [
                            {
                                task: {
                                    id: 1,
                                    start: new Date(2020, 3, 15),
                                    dependencies: {},
                                } as Task,
                            },
                            {
                                task: {
                                    id: 2,
                                    start: new Date(2020, 3, 20),
                                    dependencies: {},
                                } as Task,
                            },
                        ],
                    },
                }),
            },
        });

        const time_period = wrapper.findComponent(TimePeriodHeader);
        expect(time_period.exists()).toBe(true);
        expect(observe).toHaveBeenCalledWith(time_period.element);
    });

    it("Fills the empty space of additional months if the user resize the viewport", async () => {
        const observe = jest.fn();
        const mockResizeObserver = jest.fn();
        mockResizeObserver.mockReturnValue({
            observe,
        });
        window.ResizeObserver = mockResizeObserver;

        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                        tasks: {} as TasksState,
                    } as RootState,
                    getters: {
                        "tasks/rows": [
                            {
                                task: {
                                    id: 1,
                                    start: new Date(2020, 3, 15),
                                    dependencies: {},
                                } as Task,
                            },
                            {
                                task: {
                                    id: 2,
                                    start: new Date(2020, 3, 20),
                                    dependencies: {},
                                } as Task,
                            },
                        ],
                    },
                }),
            },
        });

        wrapper.setData({
            now: new Date(2020, 3, 15),
        });
        await wrapper.vm.$nextTick();

        const time_period_header = wrapper.findComponent(TimePeriodHeader);
        expect(time_period_header.exists()).toBe(true);
        expect(
            time_period_header.props("time_period").units.map((month: Date) => month.toDateString())
        ).toStrictEqual(["Sun Mar 01 2020", "Wed Apr 01 2020", "Fri May 01 2020"]);
        expect(time_period_header.props("nb_additional_units")).toBe(0);

        const observerCallback = mockResizeObserver.mock.calls[0][0];
        await observerCallback([
            ({
                contentRect: { width: 450 } as DOMRectReadOnly,
                target: time_period_header.element,
            } as unknown) as ResizeObserverEntry,
        ]);

        expect(time_period_header.props("nb_additional_units")).toBe(1);
    });

    it("Use a different time period if user chose a different timescale", async () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                        tasks: {} as TasksState,
                    } as RootState,
                    getters: {
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} } as Task },
                            { task: { id: 2, dependencies: {} } as Task },
                            { task: { id: 3, dependencies: {} } as Task },
                        ],
                    },
                }),
            },
        });

        expect(wrapper.findComponent(TimePeriodHeader).props("time_period")).toBeInstanceOf(
            TimePeriodMonth
        );
        await wrapper.findComponent(TimePeriodControl).vm.$emit("input", "quarter");
        expect(wrapper.findComponent(TimePeriodHeader).props("time_period")).toBeInstanceOf(
            TimePeriodQuarter
        );
    });

    it("switch is-scrolling class on header so that user is knowing that some data is hidden behind the header", async () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                        tasks: {} as TasksState,
                    } as RootState,
                    getters: {
                        "tasks/rows": [
                            { task: { id: 1, dependencies: {} } as Task },
                            { task: { id: 2, dependencies: {} } as Task },
                            { task: { id: 3, dependencies: {} } as Task },
                        ],
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
