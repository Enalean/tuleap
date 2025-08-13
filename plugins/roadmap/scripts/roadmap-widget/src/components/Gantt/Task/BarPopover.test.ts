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

import { DateTime } from "luxon";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import type { UseMutationObserverReturn } from "@vueuse/core";
import * as vueuse from "@vueuse/core";
import * as tooltip from "@tuleap/tooltip";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import type { Task } from "../../../type";
import type { RootState } from "../../../store/type";
import BarPopover from "./BarPopover.vue";
import { buildVueDompurifyHTMLDirective } from "vue-dompurify-html";

jest.mock("@vueuse/core");
jest.mock("@tuleap/tooltip", () => ({
    retrieveTooltipData: jest.fn(),
}));

jest.useFakeTimers();

describe("BarPopover", () => {
    let is_milestone: boolean;

    beforeEach(() => {
        is_milestone = false;
    });

    function getWrapper(): VueWrapper {
        return shallowMount(BarPopover, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        locale_bcp47: "en-US",
                    } as RootState,
                }),
            },
            directives: {
                "dompurify-html": buildVueDompurifyHTMLDirective(),
            },
            props: {
                task: {
                    xref: "art #123",
                    title: "Create button",
                    start: DateTime.fromISO("2020-01-12T15:00:00.000Z"),
                    end: DateTime.fromISO("2020-01-30T15:00:00.000Z"),
                    progress: null,
                    progress_error_message: "",
                    is_milestone,
                    time_period_error_message: "",
                } as Task,
            },
        });
    }

    it("should display the title of the task", () => {
        const wrapper = getWrapper();

        expect(wrapper.classes()).not.toContain("roadmap-gantt-task-milestone-popover");
        expect(wrapper.text()).toContain("art #123");
        expect(wrapper.text()).toContain("Create button");
    });

    it("should add special appearance for a milestone", () => {
        is_milestone = true;
        const wrapper = getWrapper();

        expect(wrapper.classes()).toContain("roadmap-gantt-task-milestone-popover");
    });

    it("should fetch the body of the tooltip for the task", async () => {
        let mutation_callback: MutationCallback = jest.fn();

        const observer: UseMutationObserverReturn = {
            stop: jest.fn(),
            isSupported: true,
        };

        jest.spyOn(vueuse, "useMutationObserver").mockImplementation(
            (target, callback): UseMutationObserverReturn => {
                mutation_callback = callback;

                return observer;
            },
        );

        jest.spyOn(tooltip, "retrieveTooltipData").mockResolvedValue({
            accent_color: "fiesta-red",
            title_as_html: "the title",
            body_as_html: "the retrieved body",
        });

        const wrapper = getWrapper();

        expect(wrapper.text()).not.toContain("the retrieved body");

        wrapper.element.classList.add("tlp-popover-shown");
        mutation_callback(
            [{ target: wrapper.element } as unknown as MutationRecord],
            {} as MutationObserver,
        );

        await jest.runOnlyPendingTimersAsync();

        expect(observer.stop).toHaveBeenCalled();
        expect(wrapper.text()).toContain("the retrieved body");
    });
});
