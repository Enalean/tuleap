/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { Task } from "../../../type";
import TaskBarProgress from "./TaskBarProgress.vue";

describe("TaskBarProgress", () => {
    let task: Task,
        progress: number | null,
        progress_error_message: string,
        start: DateTime | null,
        is_milestone: boolean,
        width: number,
        percentage: string,
        is_text_displayed_inside_progress_bar: boolean,
        is_text_displayed_outside_progress_bar: boolean,
        is_text_displayed_outside_bar: boolean,
        is_error_sign_displayed_outside_bar: boolean,
        is_error_sign_displayed_inside_bar: boolean;

    beforeEach(() => {
        task = {} as Task;
        progress = null;
        progress_error_message = "";
        start = DateTime.fromObject({ year: 2020, month: 4, day: 15 });
        is_milestone = false;
        width = 66;
        percentage = "";
        is_text_displayed_inside_progress_bar = false;
        is_text_displayed_outside_progress_bar = false;
        is_text_displayed_outside_bar = false;
        is_error_sign_displayed_outside_bar = false;
        is_error_sign_displayed_inside_bar = false;
    });

    function getWrapper(): VueWrapper {
        task = {
            color_name: "acid-green",
            progress,
            progress_error_message,
            start,
            end: DateTime.fromObject({ year: 2020, month: 4, day: 20 }),
            is_milestone,
        } as Task;

        return shallowMount(TaskBarProgress, {
            props: {
                left: 42,
                width,
                task,
                percentage,
                is_text_displayed_inside_progress_bar,
                is_text_displayed_outside_progress_bar,
                is_text_displayed_outside_bar,
                is_error_sign_displayed_outside_bar,
                is_error_sign_displayed_inside_bar,
                popover_element_id: "10",
                container_classes: "",
            },
        });
    }

    it("should adapt the width of the progress bar", async () => {
        progress = 0.4;
        const wrapper = getWrapper();

        const progress_bar = wrapper.find("[data-test=progress]");
        expect((progress_bar.element as HTMLElement).style.width).toBe("40%");

        await wrapper.setProps({ task: { ...task, progress: 0.7 } });
        expect((progress_bar.element as HTMLElement).style.width).toBe("70%");
    });

    it("should display a full progress bar when the task does not have any progress", () => {
        progress = null;

        const progress_bar = getWrapper().find("[data-test=progress]");
        expect(progress_bar.element.getAttribute("style")).toBeFalsy();
    });

    describe("percentage", () => {
        it("should be displayed next to the progress bar if there are enough room", () => {
            progress = 0.42;
            width = 100;
            percentage = "42%";
            is_text_displayed_outside_progress_bar = true;

            expect(
                getWrapper()
                    .find("[data-test=container] > [data-test=bar] > [data-test=percentage]")
                    .text(),
            ).toBe("42%");
        });

        it("should be displayed inside the progress bar if there are not anymore enough room", () => {
            progress = 0.98;
            width = 100;
            percentage = "98%";
            is_text_displayed_inside_progress_bar = true;

            expect(
                getWrapper()
                    .find(
                        "[data-test=container] > [data-test=bar] > [data-test=progress] > [data-test=percentage]",
                    )
                    .text(),
            ).toBe("98%");
        });

        it("should be displayed outside of the task bar if the latter is too small", () => {
            progress = 0.5;
            width = 10;
            percentage = "50%";
            is_text_displayed_outside_bar = true;

            expect(getWrapper().find("[data-test=container] > [data-test=percentage]").text()).toBe(
                "50%",
            );
        });
    });

    describe("progress in error", () => {
        beforeEach(() => {
            progress = null;
            progress_error_message = "You fucked up!";
            width = 10;
            percentage = "50%";
            is_text_displayed_outside_bar = true;
        });

        it("should display the error sign inside the bar", () => {
            is_error_sign_displayed_inside_bar = true;
            const wrapper = getWrapper();

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
            is_error_sign_displayed_outside_bar = true;
            const wrapper = getWrapper();

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
