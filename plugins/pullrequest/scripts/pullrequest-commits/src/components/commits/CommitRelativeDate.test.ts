/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import { shallowMount } from "@vue/test-utils";
import { PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN } from "@tuleap/tlp-relative-date";
import {
    USER_LOCALE_KEY,
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
    USER_TIMEZONE_KEY,
} from "../../constants";
import CommitRelativeDate from "./CommitRelativeDate.vue";

describe("CommitRelativeDate", () => {
    it("should display the provided date as a tlp-relative-date element while taking into account user prefs", () => {
        const wrapper = shallowMount(CommitRelativeDate, {
            global: {
                stubs: {
                    "tlp-relative-date": true,
                },
                provide: {
                    [USER_TIMEZONE_KEY.valueOf()]: "Europe/Paris",
                    [USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY.valueOf()]:
                        PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN,
                    [USER_LOCALE_KEY.valueOf()]: "fr_FR",
                },
            },
            props: {
                date: "2025-10-10T11:00:00Z",
            },
        });

        const relative_date_component = wrapper.find("[data-test=commit-relative-date]");

        expect(relative_date_component.attributes("date")).toBe("2025-10-10T11:00:00Z");
        expect(relative_date_component.attributes("placement")).toBe("right");
        expect(relative_date_component.attributes("preference")).toBe("relative");
        expect(relative_date_component.attributes("locale")).toBe("fr_FR");
    });
});
