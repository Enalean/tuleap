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

import { describe, beforeEach, it, expect } from "@jest/globals";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";
import { shallowMount } from "@vue/test-utils";
import TimeTrackingOverviewTable from "./TimeTrackingOverviewTable.vue";
import { createLocalVueForTests } from "../../tests/helpers/local-vue.js";

describe("Given a timetracking overview widget", () => {
    let is_loading,
        error_message,
        are_void_trackers_hidden,
        trackers_times,
        users,
        can_results_be_displayed,
        is_sum_of_times_equals_zero;

    beforeEach(() => {
        is_loading = false;
        error_message = null;
        are_void_trackers_hidden = false;
        trackers_times = [{ tracker_id: 1 }];
        users = [1, 2];
        can_results_be_displayed = true;
        is_sum_of_times_equals_zero = true;
    });

    const getWrapper = async () => {
        const useStore = defineStore("overview/1", {
            state: () => ({
                is_loading,
                error_message,
                are_void_trackers_hidden,
                trackers_times,
                users,
            }),
            getters: {
                can_results_be_displayed: () => can_results_be_displayed,
                has_error: () => error_message !== null,
                get_formatted_total_sum: () => "10:20",
                is_sum_of_times_equals_zero: () => is_sum_of_times_equals_zero,
            },
        });

        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(TimeTrackingOverviewTable, {
            localVue: await createLocalVueForTests(),
        });
    };

    it("When trackers times are available, then table is displayed", async () => {
        is_sum_of_times_equals_zero = false;

        const wrapper = await getWrapper();

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBe(false);
        expect(wrapper.find("[data-test=timetracking-loader]").exists()).toBe(false);
        expect(wrapper.find("[data-test=overview-table]").exists()).toBe(true);
        expect(wrapper.find("[data-test=empty-cell]").exists()).toBe(false);
        expect(wrapper.find("[data-test=table-row]").exists()).toBe(true);
        expect(wrapper.find("[data-test=table-action]").exists()).toBe(true);
        expect(wrapper.find("[data-test=user-list-component]").exists()).toBe(true);
        expect(wrapper.find("[data-test=tfoot]").exists()).toBe(true);
    });

    it("When trackers times sum not equal zero, then table with rows is displayed and an error feedback is not displayed", async () => {
        is_sum_of_times_equals_zero = false;
        const wrapper = await getWrapper();

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBe(false);
        expect(wrapper.find("[data-test=timetracking-loader]").exists()).toBe(false);
        expect(wrapper.find("[data-test=overview-table]").exists()).toBe(true);
        expect(wrapper.find("[data-test=empty-cell]").exists()).toBe(false);
        expect(wrapper.find("[data-test=table-row]").exists()).toBe(true);
        expect(wrapper.find("[data-test=table-action]").exists()).toBe(true);
        expect(wrapper.find("[data-test=tfoot]").exists()).toBe(true);
    });

    it("When trackers times sum equal zero and void trackers are hidden, then table with empty cell is displayed and an error feedback is not displayed", async () => {
        is_sum_of_times_equals_zero = true;
        are_void_trackers_hidden = true;

        const wrapper = await getWrapper();

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBe(false);
        expect(wrapper.find("[data-test=timetracking-loader]").exists()).toBe(false);
        expect(wrapper.find("[data-test=overview-table]").exists()).toBe(true);
        expect(wrapper.find("[data-test=empty-cell]").exists()).toBe(true);
        expect(wrapper.find("[data-test=table-row]").exists()).toBe(false);
        expect(wrapper.find("[data-test=table-action]").exists()).toBe(true);
        expect(wrapper.find("[data-test=tfoot]").exists()).toBe(false);
    });

    it("When trackers times are not available, then table is displayed and an error feedback is not displayed", async () => {
        trackers_times = [];

        const wrapper = await getWrapper();

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBe(false);
        expect(wrapper.find("[data-test=timetracking-loader]").exists()).toBe(false);
        expect(wrapper.find("[data-test=overview-table]").exists()).toBe(true);
        expect(wrapper.find("[data-test=empty-cell]").exists()).toBe(true);
        expect(wrapper.find("[data-test=table-row]").exists()).toBe(false);
        expect(wrapper.find("[data-test=table-action]").exists()).toBe(false);
        expect(wrapper.find("[data-test=tfoot]").exists()).toBe(false);
    });

    it("When widget is loading, then a spinner is displayed", async () => {
        is_loading = true;

        const wrapper = await getWrapper();

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBe(false);
        expect(wrapper.find("[data-test=timetracking-loader]").exists()).toBe(true);
    });

    it("When results can't be displayed, then table is not displayed", async () => {
        can_results_be_displayed = false;

        const wrapper = await getWrapper();

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBe(false);
        expect(wrapper.find("[data-test=overview-table]").exists()).toBe(false);
    });

    it("When results can't be displayed, then danger's div is displayed and table is not displayed", async () => {
        error_message = "error";
        can_results_be_displayed = false;

        const wrapper = await getWrapper();

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBe(true);
        expect(wrapper.find("[data-test=overview-table]").exists()).toBe(false);
    });

    it("When no users, then user list is not displayed", async () => {
        users = [];

        const wrapper = await getWrapper();

        expect(wrapper.find("[data-test=user-list-component]").exists()).toBe(false);
    });
});
