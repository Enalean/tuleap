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
import TaskHeader from "./TaskHeader.vue";
import type { Task } from "../../../type";
import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import type { TasksState } from "../../../store/tasks/type";

describe("TaskHeader", () => {
    it("Displays meaningful info of the task", () => {
        const task: Task = {
            id: 123,
            title: "Do this",
            xref: "task #123",
            color_name: "fiesta-red",
            progress: 1,
            progress_error_message: "",
            html_url: "/plugins/tracker?aid=123",
            start: null,
            end: null,
            dependencies: {},
            is_milestone: false,
            has_subtasks: false,
        };

        const wrapper = shallowMount(TaskHeader, {
            propsData: {
                task,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        tasks: {} as TasksState,
                    },
                    getters: {
                        "tasks/does_at_least_one_task_have_subtasks": false,
                    },
                }),
            },
        });

        expect(wrapper.text()).toContain("Do this");
        expect(wrapper.text()).toContain("task #123");
    });

    it("should display the caret if the task has subtasks", () => {
        const task: Task = {
            id: 123,
            has_subtasks: true,
        } as Task;

        const wrapper = shallowMount(TaskHeader, {
            propsData: {
                task,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        tasks: {} as TasksState,
                    },
                    getters: {
                        "tasks/does_at_least_one_task_have_subtasks": true,
                    },
                }),
            },
        });

        expect(wrapper.find("[data-test=caret]").exists()).toBeTruthy();
    });

    it("should display the caret container if at least one task in the Gantt chart has subtasks, so that text header is nicely aligned across tasks", () => {
        const task: Task = {
            id: 123,
            has_subtasks: false,
        } as Task;

        const wrapper = shallowMount(TaskHeader, {
            propsData: {
                task,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        tasks: {} as TasksState,
                    },
                    getters: {
                        "tasks/does_at_least_one_task_have_subtasks": true,
                    },
                }),
            },
        });

        expect(wrapper.find("[data-test=caret-container]").exists()).toBeTruthy();
    });

    it("should not display the caret container if no task in the Gantt chart has subtasks, so that text header does not have useless extra padding", () => {
        const task: Task = {
            id: 123,
            has_subtasks: false,
        } as Task;

        const wrapper = shallowMount(TaskHeader, {
            propsData: {
                task,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        tasks: {} as TasksState,
                    },
                    getters: {
                        "tasks/does_at_least_one_task_have_subtasks": false,
                    },
                }),
            },
        });

        expect(wrapper.find("[data-test=caret-container]").exists()).toBeFalsy();
    });
});
