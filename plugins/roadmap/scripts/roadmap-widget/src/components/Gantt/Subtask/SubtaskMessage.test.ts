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
import type { VueWrapper } from "@vue/test-utils";
import type { EmptySubtasksRow, ErrorRow, Task, TaskDimension } from "../../../type";
import { TaskDimensionMap } from "../../../type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import type { TasksState } from "../../../store/tasks/type";
import type { RootState } from "../../../store/type";
import SubtaskMessage from "./SubtaskMessage.vue";

describe("SubtaskMessage", () => {
    let task: Task,
        dimensions_map: TaskDimensionMap,
        nb_iterations_ribbons: number,
        retrieveSpy: jest.Mock;

    beforeEach(() => {
        retrieveSpy = jest.fn();
        task = { id: 123 } as Task;
        dimensions_map = new TaskDimensionMap();
        nb_iterations_ribbons = 2;
    });

    function getWrapper(): VueWrapper {
        dimensions_map.set(task, { index: 4 } as TaskDimension);

        return shallowMount(SubtaskMessage, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        tasks_state: {} as TasksState,
                    } as RootState,
                    modules: {
                        tasks: {
                            mutations: {
                                removeSubtasksDisplayForTask: () => retrieveSpy(task),
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
            props: {
                row: { for_task: task, is_empty: true } as ErrorRow | EmptySubtasksRow,
                nb_iterations_ribbons,
                dimensions_map,
            },
        });
    }
    it("should position itself by taking account the size of pin head + year + month + iterations ribbons + tasks above us + 1px for the border", () => {
        const wrapper = getWrapper();

        expect((wrapper.element as HTMLElement).style.top).toBe("322px");
    });

    describe("empty subtasks", () => {
        it("should display a confirmation button", async () => {
            nb_iterations_ribbons = 0;
            const wrapper = getWrapper();

            const button = wrapper.find("[data-test=button]");
            await button.trigger("click");
            expect(retrieveSpy).toHaveBeenCalledWith(task);
        });
    });
});
