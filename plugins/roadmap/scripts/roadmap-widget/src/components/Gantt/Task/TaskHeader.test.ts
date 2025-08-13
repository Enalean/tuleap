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

import { DateTime } from "luxon";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import type { TasksState } from "../../../store/tasks/type";
import type { RootState } from "../../../store/type";
import type { Task } from "../../../type";
import HeaderInvalidIcon from "../Task/HeaderInvalidIcon.vue";
import HeaderLink from "./HeaderLink.vue";
import TaskHeader from "./TaskHeader.vue";

describe("TaskHeader", () => {
    let task: Task, does_at_least_one_task_have_subtasks: boolean;
    let retrieveSpy: jest.Mock;
    beforeEach(() => {
        retrieveSpy = jest.fn();
    });

    beforeEach(() => {
        task = {
            id: 123,
            has_subtasks: false,
        } as Task;
        does_at_least_one_task_have_subtasks = false;
    });

    function getWrapper(): VueWrapper {
        return shallowMount(TaskHeader, {
            props: {
                task,
                popover_element_id: "id",
            },
            global: {
                ...getGlobalTestOptions({
                    state: {
                        tasks_state: {} as TasksState,
                    } as RootState,
                    modules: {
                        tasks: {
                            getters: {
                                does_at_least_one_task_have_subtasks: () =>
                                    does_at_least_one_task_have_subtasks,
                            },
                            actions: {
                                toggleSubtasks: () => retrieveSpy(task),
                            },
                            mutations: {
                                removeSubtasksDisplayForTask: jest.fn(),
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
        });
    }

    it("Displays link to the task", () => {
        task = {
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

        const wrapper = getWrapper();

        expect(wrapper.findComponent(HeaderLink).exists()).toBeTruthy();
    });

    it("should display a warning icon if task has end date < start date", () => {
        task = {
            id: 123,
            start: DateTime.fromISO("2020-04-14T22:00:00.000Z"),
            end: DateTime.fromISO("2020-04-10T22:00:00.000Z"),
        } as Task;

        const wrapper = getWrapper();

        expect(wrapper.findComponent(HeaderInvalidIcon).exists()).toBe(true);
    });

    it("does not need to display the project for parent tasks", () => {
        task = {
            id: 123,
            has_subtasks: true,
        } as Task;
        does_at_least_one_task_have_subtasks = true;

        const wrapper = getWrapper();

        expect(wrapper.findComponent(HeaderLink).props("should_display_project")).toBe(false);
    });

    it("should indicates that the task has subtasks", () => {
        task = {
            id: 123,
            has_subtasks: true,
        } as Task;
        does_at_least_one_task_have_subtasks = true;

        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=caret]").exists()).toBeTruthy();
        expect(wrapper.classes()).toContain("roadmap-gantt-task-header-with-subtasks");
    });

    it(`should display the caret container if at least one task in the Gantt chart has subtasks,
        so that text header is nicely aligned across tasks`, () => {
        does_at_least_one_task_have_subtasks = true;

        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=caret-container]").exists()).toBeTruthy();
    });

    it(`should not display the caret container if no task in the Gantt chart has subtasks,
        so that text header does not have useless extra padding`, () => {
        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=caret-container]").exists()).toBeFalsy();
    });

    describe("onclick", () => {
        it("should toggle the subtasks", async () => {
            task = {
                id: 123,
                has_subtasks: true,
            } as Task;

            const wrapper = getWrapper();
            await wrapper.trigger("click");

            expect(retrieveSpy).toHaveBeenCalledWith(task);
        });

        it("should not toggle the subtasks if there is no subtasks", async () => {
            const wrapper = getWrapper();
            await wrapper.trigger("click");

            expect(retrieveSpy).not.toHaveBeenCalledWith(task);
        });
    });
});
