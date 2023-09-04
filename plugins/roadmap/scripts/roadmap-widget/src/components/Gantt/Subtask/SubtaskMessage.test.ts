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

import type { Task, TaskDimension } from "../../../type";
import { TaskDimensionMap } from "../../../type";
import { shallowMount } from "@vue/test-utils";
import SubtaskMessage from "./SubtaskMessage.vue";
import { createRoadmapLocalVue } from "../../../helpers/local-vue-for-test";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { TasksState } from "../../../store/tasks/type";
import type { RootState } from "../../../store/type";

describe("SubtaskMessage", () => {
    it("should position itself by taking account the size of pin head + year + month + iterations ribbons + tasks above us + 1px for the border", async () => {
        const task = { id: 123 } as Task;

        const dimensions_map = new TaskDimensionMap();
        dimensions_map.set(task, { index: 4 } as TaskDimension);

        const wrapper = shallowMount(SubtaskMessage, {
            localVue: await createRoadmapLocalVue(),
            propsData: {
                row: { for_task: task, is_error: true },
                nb_iterations_ribbons: 2,
                dimensions_map,
            },
        });

        expect((wrapper.element as HTMLElement).style.top).toBe("322px");
    });

    describe("empty subtasks", () => {
        it("should display a confirmation button", async () => {
            const task = { id: 123 } as Task;

            const dimensions_map = new TaskDimensionMap();
            dimensions_map.set(task, { index: 4 } as TaskDimension);

            const wrapper = shallowMount(SubtaskMessage, {
                localVue: await createRoadmapLocalVue(),
                propsData: {
                    row: { for_task: task, is_empty: true },
                    nb_iterations_ribbons: 0,
                    dimensions_map,
                },
                mocks: {
                    $store: createStoreMock({
                        state: {
                            tasks: {} as TasksState,
                        } as RootState,
                    }),
                },
            });

            const button = wrapper.find("[data-test=button]");
            await button.trigger("click");
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "tasks/removeSubtasksDisplayForTask",
                task,
            );
        });
    });
});
