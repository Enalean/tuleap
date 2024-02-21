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
import type { Task } from "../../../type";
import { DateTime } from "luxon";

describe("TaskBar", () => {
    it("should adapt the width of the progress bar", async () => {
        const task = {
            color_name: "acid-green",
            progress: 0.4,
            progress_error_message: "",
            start: DateTime.fromObject({ year: 2020, month: 4, day: 15 }),
            end: DateTime.fromObject({ year: 2020, month: 4, day: 20 }),
            is_milestone: false,
        } as Task;

        const wrapper = shallowMount(TaskBar, {
            propsData: {
                task,
                left: 42,
                width: 66,
                percentage: "40%",
                is_text_displayed_inside_progress_bar: true,
                is_text_displayed_outside_progress_bar: false,
                is_text_displayed_outside_bar: false,
                is_error_sign_displayed_outside_bar: false,
                is_error_sign_displayed_inside_bar: false,
            },
        });

        const progress_bar = wrapper.find("[data-test=progress]");
        expect((progress_bar.element as HTMLElement).style.width).toBe("40%");

        await wrapper.setProps({ task: { ...task, progress: 0.7 } });
        expect((progress_bar.element as HTMLElement).style.width).toBe("70%");
    });

    it("should display a full progress bar when the task does not have any progress", () => {
        const wrapper = shallowMount(TaskBar, {
            propsData: {
                task: {
                    color_name: "acid-green",
                    progress: null,
                    progress_error_message: "",
                    start: DateTime.fromObject({ year: 2020, month: 4, day: 15 }),
                    end: DateTime.fromObject({ year: 2020, month: 4, day: 20 }),
                    is_milestone: false,
                } as Task,
                left: 42,
                width: 66,
                percentage: "",
                is_text_displayed_inside_progress_bar: false,
                is_text_displayed_outside_progress_bar: false,
                is_text_displayed_outside_bar: false,
                is_error_sign_displayed_outside_bar: false,
                is_error_sign_displayed_inside_bar: false,
            },
        });

        const progress_bar = wrapper.find("[data-test=progress]");
        expect(progress_bar.element.getAttribute("style")).toBeFalsy();
    });

    it("Displays a milestone", () => {
        const wrapper = shallowMount(TaskBar, {
            propsData: {
                task: {
                    color_name: "acid-green",
                    start: null,
                    end: DateTime.fromObject({ year: 2020, month: 4, day: 20 }),
                    progress_error_message: "",
                    is_milestone: true,
                } as Task,
                left: 42,
                width: 21,
                percentage: "",
                is_text_displayed_inside_progress_bar: false,
                is_text_displayed_outside_progress_bar: false,
                is_text_displayed_outside_bar: false,
                is_error_sign_displayed_outside_bar: false,
                is_error_sign_displayed_inside_bar: false,
            },
        });

        expect(wrapper.findComponent(MilestoneBar).exists()).toBe(true);
    });

    describe("percentage", () => {
        it("should be displayed next to the progress bar if there are enough room", () => {
            const wrapper = shallowMount(TaskBar, {
                propsData: {
                    task: {
                        color_name: "acid-green",
                        start: DateTime.fromObject({ year: 2020, month: 3, day: 20 }),
                        end: DateTime.fromObject({ year: 2020, month: 4, day: 20 }),
                        progress: 0.42,
                        progress_error_message: "",
                        is_milestone: false,
                    } as Task,
                    left: 42,
                    width: 100,
                    percentage: "42%",
                    is_text_displayed_inside_progress_bar: false,
                    is_text_displayed_outside_progress_bar: true,
                    is_text_displayed_outside_bar: false,
                    is_error_sign_displayed_outside_bar: false,
                    is_error_sign_displayed_inside_bar: false,
                },
            });

            expect(
                wrapper
                    .find("[data-test=container] > [data-test=bar] > [data-test=percentage]")
                    .text(),
            ).toBe("42%");
        });

        it("should be displayed inside the progress bar if there are not anymore enough room", () => {
            const wrapper = shallowMount(TaskBar, {
                propsData: {
                    task: {
                        color_name: "acid-green",
                        start: DateTime.fromObject({ year: 2020, month: 3, day: 20 }),
                        end: DateTime.fromObject({ year: 2020, month: 4, day: 20 }),
                        progress: 0.98,
                        progress_error_message: "",
                        is_milestone: false,
                    } as Task,
                    left: 42,
                    width: 100,
                    percentage: "98%",
                    is_text_displayed_inside_progress_bar: true,
                    is_text_displayed_outside_progress_bar: false,
                    is_text_displayed_outside_bar: false,
                    is_error_sign_displayed_outside_bar: false,
                    is_error_sign_displayed_inside_bar: false,
                },
            });

            expect(
                wrapper
                    .find(
                        "[data-test=container] > [data-test=bar] > [data-test=progress] > [data-test=percentage]",
                    )
                    .text(),
            ).toBe("98%");
        });

        it("should be displayed outside of the task bar if the latter is too small", () => {
            const wrapper = shallowMount(TaskBar, {
                propsData: {
                    task: {
                        color_name: "acid-green",
                        start: DateTime.fromObject({ year: 2020, month: 3, day: 20 }),
                        end: DateTime.fromObject({ year: 2020, month: 4, day: 20 }),
                        progress: 0.5,
                        progress_error_message: "",
                        is_milestone: false,
                    } as Task,
                    left: 42,
                    width: 10,
                    percentage: "50%",
                    is_text_displayed_inside_progress_bar: false,
                    is_text_displayed_outside_progress_bar: false,
                    is_text_displayed_outside_bar: true,
                    is_error_sign_displayed_outside_bar: false,
                    is_error_sign_displayed_inside_bar: false,
                },
            });

            expect(wrapper.find("[data-test=container] > [data-test=percentage]").text()).toBe(
                "50%",
            );
        });
    });

    describe("progress in error", () => {
        it("should display the error sign inside the bar", () => {
            const wrapper = shallowMount(TaskBar, {
                propsData: {
                    task: {
                        color_name: "acid-green",
                        start: DateTime.fromObject({ year: 2020, month: 3, day: 20 }),
                        end: DateTime.fromObject({ year: 2020, month: 4, day: 20 }),
                        progress: null,
                        progress_error_message: "You fucked up!",
                        is_milestone: false,
                    } as Task,
                    left: 42,
                    width: 10,
                    percentage: "50%",
                    is_text_displayed_inside_progress_bar: false,
                    is_text_displayed_outside_progress_bar: false,
                    is_text_displayed_outside_bar: true,
                    is_error_sign_displayed_outside_bar: false,
                    is_error_sign_displayed_inside_bar: true,
                },
            });

            expect(
                wrapper
                    .find(
                        "[data-test=container] > [data-test=bar] > [data-test=progress-error-sign]",
                    )
                    .exists(),
            ).toBe(true);
            expect(
                wrapper.find("[data-test=container] > [data-test=progress-error-sign]").exists(),
            ).toBe(false);
            expect(wrapper.find("[data-test=progress]").exists()).toBe(false);
        });

        it("should display the error sign outside the bar", () => {
            const wrapper = shallowMount(TaskBar, {
                propsData: {
                    task: {
                        color_name: "acid-green",
                        start: DateTime.fromObject({ year: 2020, month: 3, day: 20 }),
                        end: DateTime.fromObject({ year: 2020, month: 4, day: 20 }),
                        progress: null,
                        progress_error_message: "You fucked up!",
                        is_milestone: false,
                    } as Task,
                    left: 42,
                    width: 10,
                    percentage: "50%",
                    is_text_displayed_inside_progress_bar: false,
                    is_text_displayed_outside_progress_bar: false,
                    is_text_displayed_outside_bar: true,
                    is_error_sign_displayed_outside_bar: true,
                    is_error_sign_displayed_inside_bar: false,
                },
            });

            expect(
                wrapper
                    .find(
                        "[data-test=container] > [data-test=bar] > [data-test=progress-error-sign]",
                    )
                    .exists(),
            ).toBe(false);
            expect(
                wrapper.find("[data-test=container] > [data-test=progress-error-sign]").exists(),
            ).toBe(true);
            expect(wrapper.find("[data-test=progress]").exists()).toBe(false);
        });
    });
});
