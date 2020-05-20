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
import TimeTrackingOverviewTable from "../../components/TimeTrackingOverviewTable.vue";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import localVue from "../helpers/local-vue.js";

function getTimeTrackingOverviewTableInstance(store_options) {
    const store = createStoreMock(store_options);
    const component_options = {
        localVue,
        mocks: { $store: store },
    };
    return shallowMount(TimeTrackingOverviewTable, component_options);
}

describe("Given a timetracking overview widget", () => {
    let store_options;
    beforeEach(() => {
        store_options = {
            state: {
                is_loading: false,
                error_message: null,
                are_void_trackers_hidden: false,
                trackers_times: [{ tracker_id: 1 }],
                users: [1, 2],
            },
            getters: {
                can_results_be_displayed: true,
                has_error: false,
                get_formatted_total_sum: "10:20",
                is_sum_of_times_equals_zero: true,
            },
        };
    });

    it("When trackers times are available, then table is displayed", () => {
        const wrapper = getTimeTrackingOverviewTableInstance(store_options);
        expect(wrapper.find("[data-test=alert-danger]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=timetracking-loader]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=overview-table]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=empty-cell]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=table-row]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=table-action]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=user-list-component]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=tfoot]").exists()).toBeTruthy();
    });

    it("When trackers times sum not equal zero, then table with rows is displayed and an error feedback is not displayed", () => {
        store_options.getters.is_sum_of_times_equals_zero = false;
        const wrapper = getTimeTrackingOverviewTableInstance(store_options);

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=timetracking-loader]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=overview-table]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=empty-cell]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=table-row]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=table-action]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=tfoot]").exists()).toBeTruthy();
    });

    it("When trackers times sum equal zero and void trackers are hidden, then table with empty cell is displayed and an error feedback is not displayed", () => {
        store_options.getters.is_sum_of_times_equals_zero = true;
        store_options.state.are_void_trackers_hidden = true;
        const wrapper = getTimeTrackingOverviewTableInstance(store_options);

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=timetracking-loader]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=overview-table]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=empty-cell]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=table-row]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=table-action]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=tfoot]").exists()).toBeFalsy();
    });

    it("When trackers times are not available, then table is displayed and an error feedback is not displayed", () => {
        store_options.state.trackers_times = [];
        const wrapper = getTimeTrackingOverviewTableInstance(store_options);

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=timetracking-loader]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=overview-table]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=empty-cell]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=table-row]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=table-action]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=tfoot]").exists()).toBeFalsy();
    });

    it("When widget is loading, then a spinner is displayed", () => {
        store_options.state.is_loading = true;
        const wrapper = getTimeTrackingOverviewTableInstance(store_options);

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=timetracking-loader]").exists()).toBeTruthy();
    });

    it("When results can't be displayed, then table is not displayed", () => {
        store_options.getters.can_results_be_displayed = false;
        const wrapper = getTimeTrackingOverviewTableInstance(store_options);

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=overview-table]").exists()).toBeFalsy();
    });

    it("When results can't be displayed, then danger's div is displayed and table is not displayed", () => {
        store_options.state.error_message = "error";
        store_options.getters.can_results_be_displayed = false;
        store_options.getters.has_error = true;
        const wrapper = getTimeTrackingOverviewTableInstance(store_options);

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=overview-table]").exists()).toBeFalsy();
    });

    it("When no users, then user list is not displayed", () => {
        store_options.state.users = [];
        const wrapper = getTimeTrackingOverviewTableInstance(store_options);

        expect(wrapper.find("[data-test=user-list-component]").exists()).toBeFalsy();
    });
});
