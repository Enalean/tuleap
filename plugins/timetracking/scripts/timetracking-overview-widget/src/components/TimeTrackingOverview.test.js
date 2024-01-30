/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import TimeTrackingOverview from "./TimeTrackingOverview.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

const reportId = 8;
function getTimetrackingOverviewInstance(store_options) {
    const store = createStoreMock(store_options);
    const component_options = {
        reportId,
        mocks: { $store: store },
    };
    return shallowMount(TimeTrackingOverview, component_options);
}

describe("Given a timetracking overview widget", () => {
    let store_options;
    beforeEach(() => {
        store_options = {
            state: {
                reading_mode: true,
                success_message: null,
            },
            getters: {
                has_success_message: false,
            },
        };
        getTimetrackingOverviewInstance(store_options);
    });

    it("When reading mode is true, then writing should not be displayed", () => {
        const wrapper = getTimetrackingOverviewInstance(store_options);
        expect(wrapper.find("[data-test=report-success]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=reading-mode]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=writing-mode]").exists()).toBeFalsy();
    });

    it("When success message, then a success message is displayed", () => {
        store_options.getters.has_success_message = true;
        const wrapper = getTimetrackingOverviewInstance(store_options);

        expect(wrapper.find("[data-test=report-success]").exists()).toBeTruthy();
    });

    it("When reading mode is false, then writing should be displayed", () => {
        store_options.state.reading_mode = false;
        const wrapper = getTimetrackingOverviewInstance(store_options);

        expect(wrapper.find("[data-test=report-success]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=reading-mode]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=writing-mode]").exists()).toBeTruthy();
    });
});
