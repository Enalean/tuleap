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
import TaskBar from "./TaskBar.vue";
import MilestoneBar from "./MilestoneBar.vue";

describe("TaskBar", () => {
    it("Displays a task bar", () => {
        const wrapper = shallowMount(TaskBar, {
            propsData: {
                task: {
                    color_name: "acid-green",
                    progress: 1,
                    start: new Date(2020, 3, 15),
                    end: new Date(2020, 3, 20),
                },
                left: 42,
                width: 66,
            },
        });

        expect(wrapper).toMatchInlineSnapshot(`
            <div class="roadmap-gantt-task-bar roadmap-gantt-task-bar-acid-green" style="left: 42px; width: 66px;">
              <div data-test="progress" class="roadmap-gantt-task-bar-progress" style="width: 100%;"></div>
            </div>
        `);
        expect(wrapper.findComponent(MilestoneBar).exists()).toBe(false);
    });

    it("should adapt the width of the progress bar", async () => {
        const task = {
            color_name: "acid-green",
            progress: 0.4,
            start: new Date(2020, 3, 15),
            end: new Date(2020, 3, 20),
        };

        const wrapper = shallowMount(TaskBar, {
            propsData: {
                task,
                left: 42,
                width: 66,
            },
        });

        const progress_bar = wrapper.find("[data-test=progress]");
        expect(progress_bar.element.style.width).toBe("40%");

        await wrapper.setProps({ task: { ...task, progress: 0.7 } });
        expect(progress_bar.element.style.width).toBe("70%");
    });

    it("should display a full progress bar when the task does not have any progress", () => {
        const wrapper = shallowMount(TaskBar, {
            propsData: {
                task: {
                    color_name: "acid-green",
                    progress: null,
                    start: new Date(2020, 3, 15),
                    end: new Date(2020, 3, 20),
                },
                left: 42,
                width: 66,
            },
        });

        const progress_bar = wrapper.find("[data-test=progress]");
        expect(progress_bar.element.getAttribute("style")).toBeFalsy();
    });

    it("Displays a milestone when there is no start", () => {
        const wrapper = shallowMount(TaskBar, {
            propsData: {
                task: {
                    color_name: "acid-green",
                    start: null,
                    end: new Date(2020, 3, 20),
                },
                left: 42,
                width: 21,
            },
        });

        expect(wrapper.findComponent(MilestoneBar).exists()).toBe(true);
    });

    it("Displays a milestone when there is no end", () => {
        const wrapper = shallowMount(TaskBar, {
            propsData: {
                task: {
                    color_name: "acid-green",
                    start: new Date(2020, 3, 20),
                    end: null,
                },
                left: 42,
                width: 21,
            },
        });

        expect(wrapper.findComponent(MilestoneBar).exists()).toBe(true);
    });

    it("Displays a milestone when start = end", () => {
        const wrapper = shallowMount(TaskBar, {
            propsData: {
                task: {
                    color_name: "acid-green",
                    start: new Date(2020, 3, 20),
                    end: new Date(2020, 3, 20),
                },
                left: 42,
                width: 21,
            },
        });

        expect(wrapper.findComponent(MilestoneBar).exists()).toBe(true);
    });
});
