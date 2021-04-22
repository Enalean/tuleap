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
import MilestoneBar from "./MilestoneBar.vue";
import type { Task } from "../../../type";

describe("MilestoneBar", () => {
    it("should adapt the clip-path attribute to the progress", async () => {
        const task: Task = {
            color_name: "fiesta-red",
            progress: 0,
        } as Task;

        const wrapper = shallowMount(MilestoneBar, {
            propsData: {
                task,
                left: 0,
                percentage: "",
            },
        });

        const progress_bar = wrapper.find("[data-test=progress]");
        expect(progress_bar.attributes("clip-path")).toMatchInlineSnapshot(
            `"polygon(-1 -1, -1 -1, -1 23, -1 23)"`
        );

        await wrapper.setProps({ task: { ...task, progress: 0.4 } });
        expect(progress_bar.attributes("clip-path")).toMatchInlineSnapshot(
            `"polygon(-1 -1, 40% -1, 40% 23, -1 23)"`
        );

        await wrapper.setProps({ task: { ...task, progress: 0.7 } });
        expect(progress_bar.attributes("clip-path")).toMatchInlineSnapshot(
            `"polygon(-1 -1, 70% -1, 70% 23, -1 23)"`
        );

        await wrapper.setProps({ task: { ...task, progress: 1 } });
        expect(progress_bar.attributes("clip-path")).toMatchInlineSnapshot(
            `"polygon(-1 -1, 23 -1, 23 23, -1 23)"`
        );
    });

    it("should not clip at all when the task does not have any progress", () => {
        const wrapper = shallowMount(MilestoneBar, {
            propsData: {
                task: {
                    color_name: "fiesta-red",
                    progress: null,
                } as Task,
                left: 0,
                percentage: "",
            },
        });

        const progress_bar = wrapper.find("[data-test=progress]");
        expect(progress_bar.attributes("clip-path")).toBe("");
    });

    it("should display the percentage if it is given", async () => {
        const task = {
            color_name: "fiesta-red",
            progress: 0.42,
        } as Task;
        const wrapper = shallowMount(MilestoneBar, {
            propsData: {
                task,
                left: 0,
                percentage: "42%",
            },
        });

        expect(wrapper.find("[data-test=percentage]").text()).toBe("42%");

        await wrapper.setProps({ task: { ...task, progress: null }, percentage: "" });
        expect(wrapper.find("[data-test=percentage]").exists()).toBeFalsy();
    });
});
