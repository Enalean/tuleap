/*
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import SelectablePagination from "./SelectablePagination.vue";
import { describe, expect, it } from "vitest";

describe("SelectablePagination", () => {
    const getWrapper = (
        total_number: number,
        limit: number,
        offset: number,
    ): VueWrapper<InstanceType<typeof SelectablePagination>> => {
        return shallowMount(SelectablePagination, {
            global: {
                ...getGlobalTestOptions({}),
            },
            props: {
                total_number,
                limit,
                offset,
            },
        });
    };
    describe("Button page handling -", () => {
        it("disables the previous button and the first page buttons if the user is already on the first page", () => {
            const wrapper = getWrapper(10, 1, 0);

            const first_page_button = wrapper.find("[data-test=first-page-button]");
            const previous_page_button = wrapper.find("[data-test=previous-page-button]");
            const next_page_button = wrapper.find("[data-test=next-page-button]");
            const last_page_button = wrapper.find("[data-test=last-page-button]");

            expect(first_page_button.classes()).toContain("disabled");
            expect(previous_page_button.classes()).toContain("disabled");
            expect(next_page_button.classes()).not.toContain("disabled");
            expect(last_page_button.classes()).not.toContain("disabled");
        });

        it("disables the next button and the last page buttons if the user is already on the last page", () => {
            const wrapper = getWrapper(10, 1, 9);

            const first_page_button = wrapper.find("[data-test=first-page-button]");
            const previous_page_button = wrapper.find("[data-test=previous-page-button]");
            const next_page_button = wrapper.find("[data-test=next-page-button]");
            const last_page_button = wrapper.find("[data-test=last-page-button]");

            expect(first_page_button.classes()).not.toContain("disabled");
            expect(previous_page_button.classes()).not.toContain("disabled");
            expect(next_page_button.classes()).toContain("disabled");
            expect(last_page_button.classes()).toContain("disabled");
        });
    });
    describe("new-page event", () => {
        it.each([
            ["first page", 0, "first"],
            ["previous page", 10, "previous"],
            ["next page", 30, "next"],
            ["last page", 90, "last"],
        ])(
            "send the event with the new offset when the user click on %s button",
            (_button_name, expected_offset, wanted_button) => {
                const wrapper = getWrapper(100, 10, 20);

                wrapper.find(`[data-test=${wanted_button}-page-button]`).trigger("click");

                expect(wrapper.emitted()).toHaveProperty("new-page");

                const new_page_event = wrapper.emitted("new-page");
                if (new_page_event === undefined) {
                    throw Error("The 'new-page' event should be emitted");
                }
                expect(new_page_event[0][0]).toBe(expected_offset);
            },
        );
    });
    it("sends the current offset when we are already are at the last page", () => {
        const wrapper = getWrapper(100, 10, 95);

        wrapper.find(`[data-test=next-page-button]`).trigger("click");

        expect(wrapper.emitted()).toHaveProperty("new-page");

        const new_page_event = wrapper.emitted("new-page");
        if (new_page_event === undefined) {
            throw Error("The 'new-page' event should be emitted");
        }
        expect(new_page_event[0][0]).toBe(95);
    });
    it("sends the base offset (0) offset when the previous page is before the first page", () => {
        const wrapper = getWrapper(100, 10, 2);

        wrapper.find(`[data-test=previous-page-button]`).trigger("click");

        expect(wrapper.emitted()).toHaveProperty("new-page");

        const new_page_event = wrapper.emitted("new-page");
        if (new_page_event === undefined) {
            throw Error("The 'new-page' event should be emitted");
        }
        expect(new_page_event[0][0]).toBe(0);
    });
    it("displays the number 0 as the index of first element if there is no artifact", () => {
        const wrapper = getWrapper(0, 10, 2);

        expect(wrapper.find(`[data-test=selectable-pagination-number-first-element]`).text()).toBe(
            "0",
        );
    });

    it("displays the page's first element number artifact has been found", () => {
        const wrapper = getWrapper(10, 10, 2);

        expect(wrapper.find(`[data-test=selectable-pagination-number-first-element]`).text()).toBe(
            "3",
        );
    });
});
