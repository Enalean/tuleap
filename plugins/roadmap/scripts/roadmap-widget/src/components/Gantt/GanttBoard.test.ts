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
import { mount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import type { Iteration, Row, Task, TaskRow } from "../../type";
import { NaturesLabels } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { TimePeriodMonth } from "../../helpers/time-period-month";
import * as rows_sorter from "../../helpers/rows-sorter";
import type { RootState } from "../../store/type";
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
    const windowIntersectionObserver = window.IntersectionObserver;
    const elementScrollTo = (Element.prototype.scrollTo = (): void => {
        // mock implementation
    });

    let has_at_least_one_row_shown: boolean,
        rows: Row[],
        lvl1_iterations_to_display: Iteration[],
        lvl2_iterations_to_display: Iteration[],
        tasks: Task[],
        setTimescaleSpy: jest.Mock,
        timescale: string;

    beforeEach(() => {
        setTimescaleSpy = jest.fn();
        jest.spyOn(rows_sorter, "sortRows").mockImplementation((rows) => [...rows]);

        has_at_least_one_row_shown = true;
        rows = [];
        lvl1_iterations_to_display = [];
        lvl2_iterations_to_display = [];
        tasks = [];
        timescale = "week";

        const mockIntersectionObserver = jest.fn();
        mockIntersectionObserver.mockReturnValue({
            observe: jest.fn(),
        });
        window.IntersectionObserver = mockIntersectionObserver;
    });

    afterEach(() => {
        window.ResizeObserver = windowResizeObserver;
        window.IntersectionObserver = windowIntersectionObserver;
        Element.prototype.scrollTo = elementScrollTo;
    });

    function getWrapper(): VueWrapper {
        return mount(GanttBoard, {
            shallow: true,
            props: {
                visible_natures: new NaturesLabels([
                    ["", "Linked to"],
                    ["depends_on", "Depends on"],
                ]),
            },
            global: {
                ...getGlobalTestOptions({
                    state: getRootState(),
                    modules: {
                        tasks: {
                            getters: {
                                has_at_least_one_row_shown: () => has_at_least_one_row_shown,
                                rows: () => rows,
                                tasks: () => tasks,
                            },
                            namespaced: true,
                        },
                        iterations: {
                            getters: {
                                lvl1_iterations_to_display: () => lvl1_iterations_to_display,
                                lvl2_iterations_to_display: () => lvl2_iterations_to_display,
                            },
                            namespaced: true,
                        },
                        timeperiod: {
                            getters: {
                                time_period: () =>
                                    new TimePeriodMonth(
                                        DateTime.fromISO("2020-03-31T22:00:00.000Z"),
                                        DateTime.fromISO("2020-04-30T22:00:00.000Z"),
                                        "en-US",
                                    ),
                            },
                            mutations: {
                                setTimescale: () => setTimescaleSpy(timescale),
                            },
                            namespaced: true,
                        },
                    },
                }),
                stubs: {
                    ScrollingArea: false,
                    IntersectionObserver: false,
                },
            },
        });
    }

    it("Displays an empty state if there is no rows", () => {
        has_at_least_one_row_shown = false;
        expect(getWrapper().findComponent(NoDataToShowEmptyState).exists()).toBe(true);

        has_at_least_one_row_shown = true;
        expect(getWrapper().findComponent(NoDataToShowEmptyState).exists()).toBe(false);
    });

    it("Displays no iterations if there isn't any", () => {
        const wrapper = getWrapper();

        expect(wrapper.findAllComponents(IterationsRibbon)).toHaveLength(0);
        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(false);
    });

    it("Displays level 1 iterations", () => {
        lvl1_iterations_to_display = [{ id: 1 } as Iteration];
        const wrapper = getWrapper();

        expect(wrapper.findAllComponents(IterationsRibbon)).toHaveLength(1);
    });

    it("Displays level 2 iterations", () => {
        rows = [task_row_a];
        lvl2_iterations_to_display = [{ id: 1 } as Iteration];

        const wrapper = getWrapper();

        expect(wrapper.findAllComponents(IterationsRibbon)).toHaveLength(1);
    });

    it("Displays levels 1 & 2 iterations", () => {
        rows = [task_row_a];
        lvl1_iterations_to_display = [{ id: 1 } as Iteration];
        lvl2_iterations_to_display = [{ id: 2 } as Iteration];

        const wrapper = getWrapper();

        expect(wrapper.findAllComponents(IterationsRibbon)).toHaveLength(2);
    });

    it("Displays all tasks", () => {
        tasks = [task_a, task_b, task_c];
        rows = [task_row_a, task_row_b, task_row_c];

        const wrapper = getWrapper();

        expect(wrapper.findAllComponents(GanttTask)).toHaveLength(3);
    });

    it("Displays subtasks skeleton", () => {
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

        const wrapper = getWrapper();

        expect(wrapper.findAllComponents(GanttTask)).toHaveLength(2);
        expect(wrapper.findAllComponents(SubtaskSkeletonBar)).toHaveLength(1);
        expect(wrapper.findAllComponents(SubtaskSkeletonHeader)).toHaveLength(1);
    });

    it("Displays subtasks", () => {
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

        const wrapper = getWrapper();

        expect(wrapper.findAllComponents(GanttTask)).toHaveLength(3);
        expect(wrapper.findAllComponents(SubtaskHeader)).toHaveLength(1);
    });

    it("Displays subtasks that can have multiple parents", () => {
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

        const wrapper = getWrapper();

        expect(wrapper.findAllComponents(GanttTask)).toHaveLength(4);
        expect(wrapper.findAllComponents(SubtaskHeader)).toHaveLength(2);

        const popover_ids = wrapper
            .findAllComponents(BarPopover)
            .map((wrapper) => wrapper.element.id);
        const unique_popover_ids = popover_ids.filter(
            (id, index, ids) => ids.indexOf(id) === index,
        );
        expect(unique_popover_ids).toHaveLength(4);
    });

    it("Displays subtasks error message if retrieval failed", () => {
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

        const wrapper = getWrapper();

        expect(wrapper.findAllComponents(GanttTask)).toHaveLength(2);
        expect(wrapper.findAllComponents(SubtaskMessageHeader)).toHaveLength(1);
        expect(wrapper.findAllComponents(SubtaskMessage)).toHaveLength(1);
    });

    it("Displays subtasks empty message if retrieval returned no subtasks", () => {
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

        const wrapper = getWrapper();

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

        tasks = [task_1, task_2];
        rows = [
            { task: task_1, is_shown: true },
            { task: task_2, is_shown: true },
        ];

        const wrapper = getWrapper();

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

        const wrapper = getWrapper();

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

    it("Use a different time period if user chose a different timescale", () => {
        timescale = "quarter";
        const wrapper = getWrapper();

        wrapper.findComponent(TimePeriodControl).vm.$emit("input", timescale);
        expect(setTimescaleSpy).toHaveBeenCalledWith(timescale);
    });

    it("switch is-scrolling class on header so that user is knowing that some data is hidden behind the header", async () => {
        const wrapper = getWrapper();

        const header = wrapper.find("[data-test=gantt-header]");
        expect(header.classes()).not.toContain("roadmap-gantt-header-is-scrolling");

        await wrapper.findComponent(ScrollingArea).vm.$emit("is_scrolling", true);
        expect(header.classes()).toContain("roadmap-gantt-header-is-scrolling");

        await wrapper.findComponent(ScrollingArea).vm.$emit("is_scrolling", false);
        expect(header.classes()).not.toContain("roadmap-gantt-header-is-scrolling");
    });
});
