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
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { TasksState } from "../../../store/tasks/type";
import HeaderLink from "./HeaderLink.vue";
import HeaderInvalidIcon from "../Task/HeaderInvalidIcon.vue";
import { DateTime } from "luxon";

describe("TaskHeader", () => {
    it("Displays link to the task", () => {
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
        } as Task;

        const wrapper = shallowMount(TaskHeader, {
            propsData: {
                task,
                popover_element_id: "id",
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

        expect(wrapper.findComponent(HeaderLink).exists()).toBeTruthy();
    });

    it("should display a warning icon if task has end date < start date", () => {
        const task: Task = {
            id: 123,
            start: DateTime.fromISO("2020-04-14T22:00:00.000Z"),
            end: DateTime.fromISO("2020-04-10T22:00:00.000Z"),
        } as Task;

        const wrapper = shallowMount(TaskHeader, {
            propsData: {
                task,
                popover_element_id: "id",
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

        expect(wrapper.findComponent(HeaderInvalidIcon).exists()).toBe(true);
    });

    it("does not need to display the project for parent tasks", () => {
        const task: Task = {
            id: 123,
            has_subtasks: true,
        } as Task;

        const wrapper = shallowMount(TaskHeader, {
            propsData: {
                task,
                popover_element_id: "id",
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

        expect(wrapper.findComponent(HeaderLink).props("should_display_project")).toBe(false);
    });

    it("should indicates that the task has subtasks", () => {
        const task: Task = {
            id: 123,
            has_subtasks: true,
        } as Task;

        const wrapper = shallowMount(TaskHeader, {
            propsData: {
                task,
                popover_element_id: "id",
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
        expect(wrapper.classes()).toContain("roadmap-gantt-task-header-with-subtasks");
    });

    it("should display the caret container if at least one task in the Gantt chart has subtasks, so that text header is nicely aligned across tasks", () => {
        const task: Task = {
            id: 123,
            has_subtasks: false,
        } as Task;

        const wrapper = shallowMount(TaskHeader, {
            propsData: {
                task,
                popover_element_id: "id",
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
                popover_element_id: "id",
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

    describe("onclick", () => {
        it("should toggle the subtasks", async () => {
            const task = {
                id: 123,
                has_subtasks: true,
            } as Task;
            const wrapper = shallowMount(TaskHeader, {
                propsData: {
                    task,
                    popover_element_id: "id",
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

            await wrapper.trigger("click");

            expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("tasks/toggleSubtasks", task);
        });

        it("should not toggle the subtasks if there is no subtasks", async () => {
            const task = {
                id: 123,
                has_subtasks: false,
            } as Task;
            const wrapper = shallowMount(TaskHeader, {
                propsData: {
                    task,
                    popover_element_id: "id",
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

            await wrapper.trigger("click");

            expect(wrapper.vm.$store.dispatch).not.toHaveBeenCalledWith(
                "tasks/toggleSubtasks",
                task,
            );
        });
    });
});
