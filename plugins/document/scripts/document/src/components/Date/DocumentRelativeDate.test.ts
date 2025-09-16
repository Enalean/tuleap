/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import DocumentRelativeDate from "./DocumentRelativeDate.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import { DATE_TIME_FORMAT, RELATIVE_DATES_DISPLAY, USER_LOCALE } from "../../configuration-keys";

describe("DocumentRelativeDate", () => {
    it("should display a tlp-relative-date element", () => {
        const wrapper = shallowMount(DocumentRelativeDate, {
            props: {
                date: "2021-10-06",
            },
            global: {
                ...getGlobalTestOptions({}),
                stubs: {
                    "tlp-relative-date": true,
                },
                provide: {
                    [DATE_TIME_FORMAT.valueOf()]: "Y-m-d H:i",
                    [USER_LOCALE.valueOf()]: "en_US",
                    [RELATIVE_DATES_DISPLAY.valueOf()]: "relative_first-absolute_shown",
                },
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("should display a tlp-relative-date element with placement on right", () => {
        const wrapper = shallowMount(DocumentRelativeDate, {
            props: {
                date: "2021-10-06",
                relative_placement: "right",
            },
            global: {
                ...getGlobalTestOptions({}),
                stubs: {
                    "tlp-relative-date": true,
                },
                provide: {
                    [DATE_TIME_FORMAT.valueOf()]: "Y-m-d H:i",
                    [USER_LOCALE.valueOf()]: "en_US",
                    [RELATIVE_DATES_DISPLAY.valueOf()]: "relative_first-absolute_shown",
                },
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
