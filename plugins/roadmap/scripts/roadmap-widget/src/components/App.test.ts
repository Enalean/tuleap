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

import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import App from "./App.vue";
import NoDataToShowEmptyState from "./NoDataToShowEmptyState.vue";
import SomethingWentWrongEmptyState from "./SomethingWentWrongEmptyState.vue";
import GanttBoard from "./Gantt/GanttBoard.vue";
import type { Task } from "../type";
import LoadingState from "./LoadingState.vue";
import type { RootState } from "../store/type";
import type { TasksState } from "../store/tasks/type";
import { DateTime } from "luxon";

describe("App", () => {
    function mountComponent(
        tasks: TasksState = {} as TasksState,
        root: RootState = {} as RootState,
    ): VueWrapper {
        return shallowMount(App, {
            props: {
                roadmap_id: 123,
                visible_natures: new Map(),
            },
            global: {
                ...getGlobalTestOptions({
                    state: {
                        ...root,
                        tasks_state: tasks,
                    } as RootState,
                    actions: {
                        loadRoadmap: jest.fn(),
                    },
                }),
            },
        });
    }

    it("Displays a loading state", () => {
        const wrapper = mountComponent(
            {
                tasks: [],
            },
            {
                is_loading: true,
                should_display_empty_state: false,
                should_display_error_state: false,
                error_message: "",
            } as RootState,
        );

        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(SomethingWentWrongEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(GanttBoard).exists()).toBe(false);
        expect(wrapper.findComponent(LoadingState).exists()).toBe(true);
    });

    it("Displays an empty state", () => {
        const wrapper = mountComponent(
            {
                tasks: [],
            },
            {
                is_loading: false,
                should_display_empty_state: true,
                should_display_error_state: false,
                error_message: "",
            } as RootState,
        );

        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(true);
        expect(wrapper.findComponent(SomethingWentWrongEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(GanttBoard).exists()).toBe(false);
        expect(wrapper.findComponent(LoadingState).exists()).toBe(false);
    });

    it("Displays an error state with a message", () => {
        const wrapper = mountComponent(
            {
                tasks: [],
            },
            {
                is_loading: false,
                should_display_empty_state: false,
                should_display_error_state: true,
                error_message: "Missing timeframe",
            } as RootState,
        );

        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(LoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(GanttBoard).exists()).toBe(false);

        const error_state = wrapper.findComponent(SomethingWentWrongEmptyState);
        expect(error_state.exists()).toBe(true);
        expect(error_state.props("message")).toBe("Missing timeframe");
    });

    it("Displays an error state with a message even if there is no error message", () => {
        const wrapper = mountComponent(
            {
                tasks: [],
            },
            {
                is_loading: false,
                should_display_empty_state: false,
                should_display_error_state: true,
                error_message: "",
            } as RootState,
        );

        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(LoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(GanttBoard).exists()).toBe(false);

        const error_state = wrapper.findComponent(SomethingWentWrongEmptyState);
        expect(error_state.exists()).toBe(true);
        expect(error_state.props("message")).toBe("");
    });

    it("Displays a gantt board with tasks", () => {
        const wrapper = mountComponent(
            {
                tasks: [
                    {
                        id: 1,
                        start: DateTime.fromObject({ year: 2020, month: 4, day: 15 }),
                        end: null,
                    } as Task,
                    {
                        id: 2,
                        start: DateTime.fromObject({ year: 2020, month: 5, day: 15 }),
                        end: null,
                    } as Task,
                ],
            },
            {
                is_loading: false,
                should_display_empty_state: false,
                should_display_error_state: false,
                error_message: "",
            } as RootState,
        );

        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(LoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(SomethingWentWrongEmptyState).exists()).toBe(false);

        const gantt_board = wrapper.findComponent(GanttBoard);
        expect(gantt_board.exists()).toBe(true);
    });
});
