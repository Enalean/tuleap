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

import { describe, expect, it, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import { DEFAULT_LOCALE } from "@tuleap/locale";
import DocumentRelativeDate from "./DocumentRelativeDate.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import {
    DATE_FORMATTER,
    DATE_TIME_FORMATTER,
    RELATIVE_DATES_DISPLAY,
    USER_LOCALE,
} from "../../configuration-keys";

describe("DocumentRelativeDate", () => {
    const mock_formatter = {
        format: vi.fn((date: string) => date),
    };

    it("should display a tlp-relative-date element formated for hours", () => {
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
                    [DATE_FORMATTER.valueOf()]: mock_formatter,
                    [DATE_TIME_FORMATTER.valueOf()]: mock_formatter,
                    [USER_LOCALE.valueOf()]: DEFAULT_LOCALE,
                    [RELATIVE_DATES_DISPLAY.valueOf()]: "relative_first-absolute_shown",
                },
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("should display a tlp-relative-date element with placement on right formated for date time", () => {
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
                    [DATE_TIME_FORMATTER.valueOf()]: mock_formatter,
                    [USER_LOCALE.valueOf()]: DEFAULT_LOCALE,
                    [RELATIVE_DATES_DISPLAY.valueOf()]: "relative_first-absolute_shown",
                },
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
