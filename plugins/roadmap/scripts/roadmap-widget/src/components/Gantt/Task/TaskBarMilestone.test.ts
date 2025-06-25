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
import TaskBarMilestone from "./TaskBarMilestone.vue";
import type { Task } from "../../../type";

describe("TaskBarMilestone", () => {
    it("should display a svg diamond at given position", () => {
        const wrapper = shallowMount(TaskBarMilestone, {
            propsData: {
                task: {
                    color_name: "fiesta-red",
                    progress: 0,
                    progress_error_message: "",
                } as Task,
                left: 123,
                popover_element_id: "10",
            },
        });

        expect((wrapper.element as HTMLElement).style.left).toBe("123px");
        expect(wrapper.element.children[0].tagName.toLowerCase()).toBe("svg");
    });
});
