/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import WidgetQueryDisplayer from "./WidgetQueryDisplayer.vue";
import { getGlobalTestOptions } from "../../tests/global-options-for-tests";

describe("Given a timetracking management widget query displayer", () => {
    const start_date_test = "2024-05-10";
    const end_date_test = "2024-05-20";

    function getWidgetQueryDisplayerInstance(): VueWrapper {
        return shallowMount(WidgetQueryDisplayer, {
            props: {
                start_date: start_date_test,
                end_date: end_date_test,
            },
            global: {
                ...getGlobalTestOptions(),
            },
        });
    }

    describe("When query is displaying", () => {
        it("Then it should display the start date", () => {
            const wrapper = getWidgetQueryDisplayerInstance();

            const start_date = wrapper.find("[data-test=start-date]");

            expect(start_date.text()).equals(start_date_test);
        });

        it("Then it should display the end date", () => {
            const wrapper = getWidgetQueryDisplayerInstance();

            const end_date = wrapper.find("[data-test=end-date]");

            expect(end_date.text()).equals(end_date_test);
        });
    });
});
