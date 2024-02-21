/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ChartError from "./ChartError.vue";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

const message_error_duration = "'duration' field is empty or invalid.";
const message_error_start_date = "'start_date' field is empty or invalid.";
const message_error_under_calculation =
    "Burndown is under calculation. It will be available in a few minutes.";

describe("ChartError", () => {
    function getPersonalWidgetInstance(
        has_error_duration: boolean,
        has_error_start_date: boolean,
        is_under_calculation: boolean,
    ): VueWrapper<InstanceType<typeof ChartError>> {
        return shallowMount(ChartError, {
            propsData: {
                has_error_duration,
                has_error_start_date,
                is_under_calculation,
                message_error_duration,
                message_error_start_date,
                message_error_under_calculation,
            },
            global: {
                ...getGlobalTestOptions(),
            },
        });
    }

    it.each([
        [true, true, true, true, true, false],
        [false, true, true, false, true, false],
        [true, false, true, true, false, false],
        [true, true, false, true, true, false],
        [false, false, false, false, false, false],
    ])(
        `Error message %s`,
        (
            has_error_duration: boolean,
            has_error_start_date: boolean,
            is_under_calculation: boolean,
            display_error_duration: boolean,
            display_start_date_error: boolean,
            display_calculation_error: boolean,
        ) => {
            const wrapper = getPersonalWidgetInstance(
                has_error_duration,
                has_error_start_date,
                is_under_calculation,
            );

            expect(wrapper.find("[data-test=error-duration]").exists()).toBe(
                display_error_duration,
            );
            expect(wrapper.find("[data-test=error-calculation]").exists()).toBe(
                display_calculation_error,
            );
            expect(wrapper.find("[data-test=error-start-date]").exists()).toBe(
                display_start_date_error,
            );
        },
    );
});
