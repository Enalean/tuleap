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

describe("SubtaskMessage", () => {
    it("should position itself by taking account the size of pin head + year + month + tasks above us + 1px for the border", async () => {
        const task = { id: 123 } as Task;

        const dimensions_map = new TaskDimensionMap();
        dimensions_map.set(task, { index: 4 } as TaskDimension);

        const wrapper = shallowMount(SubtaskMessage, {
            localVue: await createRoadmapLocalVue(),
            propsData: {
                row: { for_task: task, is_error: true },
                dimensions_map,
            },
        });

        expect(wrapper.element.style.top).toBe("274px");
    });
});
