/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, it, expect, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import PullRequestRelativeDate from "./PullRequestRelativeDate.vue";
import {
    USER_DATE_TIME_FORMAT_KEY,
    USER_LOCALE_KEY,
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
} from "../../constants";
import { PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN } from "@tuleap/tlp-relative-date";
import * as strict_inject from "@tuleap/vue-strict-inject";

vi.mock("@tuleap/vue-strict-inject");

describe("PullRequestRelativeDate", () => {
    it("should display the provided date as a tlp-relative-date element while taking into account user prefs", () => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation((key) => {
            switch (key) {
                case USER_DATE_TIME_FORMAT_KEY:
                    return "d/m/Y H:i";
                case USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY:
                    return PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN;
                case USER_LOCALE_KEY:
                    return "fr_FR";
            }
        });
        const wrapper = shallowMount(PullRequestRelativeDate, {
            global: {
                stubs: {
                    "tlp-relative-date": true,
                },
            },
            props: {
                date: "2023-02-17T11:00:00Z",
            },
        });

        const relative_date_component = wrapper.find("[data-test=pullrequest-relative-date]");

        expect(relative_date_component.attributes("date")).toBe("2023-02-17T11:00:00Z");
        expect(relative_date_component.attributes("placement")).toBe("right");
        expect(relative_date_component.attributes("preference")).toBe("relative");
        expect(relative_date_component.attributes("locale")).toBe("fr_FR");
    });
});
