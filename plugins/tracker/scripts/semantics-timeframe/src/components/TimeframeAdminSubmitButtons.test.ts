/*
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

import { describe, it, expect } from "vitest";
import { shallowMount } from "@vue/test-utils";
import TimeframeAdminSubmitButtons from "./TimeframeAdminSubmitButtons.vue";
import { createGettext } from "vue3-gettext";

describe("TimeframeAdminSubmitButtons", () => {
    it("should not render the reset button when the semantic is not configured", () => {
        const wrapper = shallowMount(TimeframeAdminSubmitButtons, {
            global: { plugins: [createGettext({ silent: true })] },
            propsData: {
                start_date_field_id: "",
                end_date_field_id: "",
                duration_field_id: "",
                implied_from_tracker_id: "",
                has_other_trackers_implying_their_timeframes: false,
                has_tracker_charts: false,
            },
        });

        expect(wrapper.find("[data-test=reset-button]").exists()).toBe(false);
    });

    it("should disable the reset button when some trackers inherit their own semantic timeframe from the current one", () => {
        const wrapper = shallowMount(TimeframeAdminSubmitButtons, {
            global: { plugins: [createGettext({ silent: true })] },
            propsData: {
                start_date_field_id: 1001,
                end_date_field_id: 1002,
                duration_field_id: "",
                implied_from_tracker_id: "",
                has_other_trackers_implying_their_timeframes: true,
                has_tracker_charts: false,
            },
        });

        const reset_button = wrapper.find("[data-test=reset-button]").element;

        expect(reset_button.hasAttribute("disabled")).toBe(true);
        if (!(reset_button instanceof HTMLButtonElement)) {
            throw new Error("Reset button is not a button");
        }
        expect(reset_button.title).toBe(
            "You cannot reset this semantic because some trackers inherit their own semantics timeframe from this one.",
        );
    });

    it("should disable the reset button when some charts are using the semantic", () => {
        const wrapper = shallowMount(TimeframeAdminSubmitButtons, {
            global: { plugins: [createGettext({ silent: true })] },
            propsData: {
                start_date_field_id: 1001,
                end_date_field_id: 1002,
                duration_field_id: "",
                implied_from_tracker_id: "",
                has_other_trackers_implying_their_timeframes: false,
                has_tracker_charts: true,
            },
        });

        const reset_button = wrapper.find("[data-test=reset-button]").element;

        expect(reset_button.hasAttribute("disabled")).toBe(true);
        if (!(reset_button instanceof HTMLButtonElement)) {
            throw new Error("Reset button is not a button");
        }
        expect(reset_button.title).toBe(
            "You cannot reset this semantic because this tracker has a burnup, burndown or another chart rendered by an external plugin",
        );
    });
});
