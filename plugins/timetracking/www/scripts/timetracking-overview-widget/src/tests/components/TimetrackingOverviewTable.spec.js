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
import { createStoreMock } from "../helpers/store-wrapper.spec-helper";
import localVue from "../helpers/local-vue.js";

function getTimeTrackingOverviewTableInstance(store_options) {
    const store = createStoreMock(store_options);
    const component_options = {
        localVue,
        mocks: { $store: store }
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
                trackers_times: [{ tracker_id: 1 }]
            },
            getters: {
                can_results_be_displayed: true,
                has_error: false,
                get_formatted_total_sum: "10:20"
            }
        };
    });

    it("When trackers times are avalaible, then table is displayed", () => {
        const wrapper = getTimeTrackingOverviewTableInstance(store_options);
        expect(wrapper.contains("[data-test=alert-danger]")).toBeFalsy();
        expect(wrapper.contains("[data-test=timetracking-loader]")).toBeFalsy();
        expect(wrapper.contains("[data-test=overview-table]")).toBeTruthy();
        expect(wrapper.contains("[data-test=empty-cell]")).toBeFalsy();
        expect(wrapper.contains("[data-test=table-row]")).toBeTruthy();
        expect(wrapper.contains("[data-test=table-action]")).toBeTruthy();
        expect(wrapper.contains("[data-test=tfoot]")).toBeTruthy();
    });

    it("When trackers times are not avalaible, then table is displayed and an error feedback is not displayed", () => {
        store_options.state.trackers_times = [];
        const wrapper = getTimeTrackingOverviewTableInstance(store_options);

        expect(wrapper.contains("[data-test=alert-danger]")).toBeFalsy();
        expect(wrapper.contains("[data-test=timetracking-loader]")).toBeFalsy();
        expect(wrapper.contains("[data-test=overview-table]")).toBeTruthy();
        expect(wrapper.contains("[data-test=empty-cell]")).toBeTruthy();
        expect(wrapper.contains("[data-test=table-row]")).toBeFalsy();
        expect(wrapper.contains("[data-test=table-action]")).toBeFalsy();
        expect(wrapper.contains("[data-test=tfoot]")).toBeFalsy();
    });

    it("When widget is loading, then a spinner is displayed", () => {
        store_options.state.is_loading = true;
        const wrapper = getTimeTrackingOverviewTableInstance(store_options);

        expect(wrapper.contains("[data-test=alert-danger]")).toBeFalsy();
        expect(wrapper.contains("[data-test=timetracking-loader]")).toBeTruthy();
    });

    it("When results can't be displayed, then table is not displayed", () => {
        store_options.getters.can_results_be_displayed = false;
        const wrapper = getTimeTrackingOverviewTableInstance(store_options);

        expect(wrapper.contains("[data-test=alert-danger]")).toBeFalsy();
        expect(wrapper.contains("[data-test=overview-table]")).toBeFalsy();
    });

    it("When results can't be displayed, then danger's div is displayed and table is not displayed", () => {
        store_options.state.error_message = "error";
        store_options.getters.can_results_be_displayed = false;
        store_options.getters.has_error = true;
        const wrapper = getTimeTrackingOverviewTableInstance(store_options);

        expect(wrapper.contains("[data-test=alert-danger]")).toBeTruthy();
        expect(wrapper.contains("[data-test=overview-table]")).toBeFalsy();
    });
});
