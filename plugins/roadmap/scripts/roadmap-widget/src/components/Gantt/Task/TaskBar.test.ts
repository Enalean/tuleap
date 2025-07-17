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

import { mount } from "@vue/test-utils";
import type { Wrapper } from "@vue/test-utils";
import type { Task } from "../../../type";
import TaskBarMilestone from "./TaskBarMilestone.vue";
import TaskBarProgress from "./TaskBarProgress.vue";
import TaskBar from "./TaskBar.vue";

describe("TaskBar", () => {
    let is_milestone: boolean;

    beforeEach(() => {
        is_milestone = false;
    });

    function getWrapper(): Wrapper<Vue> {
        return mount(TaskBar, {
            propsData: {
                task: {
                    is_milestone,
                    progress_error_message: "",
                } as Task,
                left: 42,
                width: 66,
                percentage: "",
                is_text_displayed_inside_progress_bar: false,
                is_text_displayed_outside_progress_bar: false,
                is_text_displayed_outside_bar: false,
                is_error_sign_displayed_outside_bar: false,
                is_error_sign_displayed_inside_bar: false,
                popover_element_id: "10",
            },
        });
    }

    it("should display TaskBarMilestone", () => {
        is_milestone = true;
        const wrapper = getWrapper();

        expect(wrapper.findComponent(TaskBarMilestone).exists()).toBe(true);
        expect(wrapper.findComponent(TaskBarProgress).exists()).toBe(false);
    });

    it("should display TaskBarProgress", () => {
        const wrapper = getWrapper();

        expect(wrapper.findComponent(TaskBarMilestone).exists()).toBe(false);
        expect(wrapper.findComponent(TaskBarProgress).exists()).toBe(true);
    });
});
