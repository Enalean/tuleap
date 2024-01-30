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
import TimeTrackingOverviewTableRow from "./TimeTrackingOverviewTableRow.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

const time = {
    id: "16",
    label: "tracker",
    project: {},
    uri: "",
    time_per_user: [
        {
            user_name: "user",
            user_id: 102,
            minutes: 120,
        },
    ],
};
function getTimeTrackingOverviewTableRowInstance(store_options) {
    const store = createStoreMock(store_options);
    const component_options = {
        propsData: {
            time,
        },
        mocks: { $store: store },
    };
    return shallowMount(TimeTrackingOverviewTableRow, component_options);
}

describe("Given a timetracking overview widget", () => {
    let store_options;
    beforeEach(() => {
        store_options = {
            state: {
                are_void_trackers_hidden: false,
            },
            getters: {
                get_formatted_time: () => "10:30",
                is_tracker_total_sum_equals_zero: () => false,
            },
        };
    });

    it("When tracker total sum not equal zero, then table row is displayed", () => {
        const wrapper = getTimeTrackingOverviewTableRowInstance(store_options);
        expect(wrapper.find("[data-test=timetracking-overview-table-row]").exists()).toBeTruthy();
    });

    it("When tracker total sum equal zero and void trackers displayed, then table row is displayed", () => {
        store_options.getters.is_tracker_total_sum_equals_zero = () => true;
        const wrapper = getTimeTrackingOverviewTableRowInstance(store_options);
        expect(wrapper.find("[data-test=timetracking-overview-table-row]").exists()).toBeTruthy();
    });

    it("When tracker total sum not equal zero and void trackers not displayed, then table row is displayed", () => {
        store_options.getters.are_void_trackers_hidden = true;
        const wrapper = getTimeTrackingOverviewTableRowInstance(store_options);
        expect(wrapper.find("[data-test=timetracking-overview-table-row]").exists()).toBeTruthy();
    });

    it("When tracker total sum equal zero and void trackers not displayed, then table row is not displayed", () => {
        store_options.getters.is_tracker_total_sum_equals_zero = () => true;
        store_options.state.are_void_trackers_hidden = true;
        const wrapper = getTimeTrackingOverviewTableRowInstance(store_options);
        expect(wrapper.find("[data-test=timetracking-overview-table-row]").exists()).toBeFalsy();
    });
});
