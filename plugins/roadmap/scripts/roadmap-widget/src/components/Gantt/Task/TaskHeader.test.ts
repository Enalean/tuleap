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
        };

        const wrapper = shallowMount(TaskHeader, {
            propsData: {
                task,
            },
        });

        expect(wrapper.text()).toContain("Do this");
        expect(wrapper.text()).toContain("task #123");
    });
});
