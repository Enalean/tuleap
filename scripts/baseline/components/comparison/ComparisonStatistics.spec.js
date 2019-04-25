/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

import { shallowMount } from "@vue/test-utils";
import localVue from "../../support/local-vue.js";
import ComparisonStatistics from "./ComparisonStatistics.vue";
import { createStoreMock } from "../../support/store-wrapper.spec-helper";
import store_options from "../../store/store_options";

describe("ComparisonStatistics", () => {
    const initial_effort_statistic_selector = '[data-test-type="initial-effort-statistic"]';

    let wrapper;
    let $store;

    beforeEach(() => {
        $store = createStoreMock(store_options);
        $store.state.comparison.initial_effort_difference = -1;

        wrapper = shallowMount(ComparisonStatistics, {
            localVue,
            mocks: { $store }
        });
    });

    describe("when compared initial effort is negative", () => {
        it("returns negative sign in front of initial effort", () => {
            expect(wrapper.find(initial_effort_statistic_selector).text()).toContain("- 1");
        });
    });

    describe("when compared initial effort is positive", () => {
        beforeEach(() => {
            $store = createStoreMock(store_options);
            $store.state.comparison.initial_effort_difference = 1;

            wrapper = shallowMount(ComparisonStatistics, {
                localVue,
                mocks: { $store }
            });
        });

        it("returns positive sign in front of initial effort", () => {
            expect(wrapper.find(initial_effort_statistic_selector).text()).toContain("1");
        });
    });
});
