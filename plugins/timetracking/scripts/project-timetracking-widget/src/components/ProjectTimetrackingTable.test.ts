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
import type { VueWrapper } from "@vue/test-utils";
import ProjectTimetrackingTable from "./ProjectTimetrackingTable.vue";
import { getGlobalTestOptions } from "../../tests/helpers/global-options-for-tests";
import type { TrackerWithTimes } from "@tuleap/plugin-timetracking-rest-api-types";
import type { TimetrackingUser } from "../store/state";

describe("Given a project timetracking widget", () => {
    let is_loading: boolean,
        error_message: null | string,
        are_void_trackers_hidden: boolean,
        trackers_times: TrackerWithTimes[],
        users: TimetrackingUser[],
        can_results_be_displayed: boolean,
        is_sum_of_times_equals_zero: boolean;

    beforeEach(() => {
        is_loading = false;
        error_message = null;
        are_void_trackers_hidden = false;
        trackers_times = [{ id: 1 } as TrackerWithTimes];
        users = [{ user_id: 1 } as TimetrackingUser, { user_id: 2 } as TimetrackingUser];
        can_results_be_displayed = true;
        is_sum_of_times_equals_zero = true;
    });

    const getWrapper = (): VueWrapper => {
        const useStore = defineStore("project-timetracking/1", {
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

        return shallowMount(ProjectTimetrackingTable, {
            global: getGlobalTestOptions(pinia),
        });
    };

    it("When trackers times are available, then table is displayed", () => {
        is_sum_of_times_equals_zero = false;

        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBe(false);
        expect(wrapper.find("[data-test=timetracking-loader]").exists()).toBe(false);
        expect(wrapper.find("[data-test=project-timetracking-table]").exists()).toBe(true);
        expect(wrapper.find("[data-test=empty-cell]").exists()).toBe(false);
        expect(wrapper.find("[data-test=table-row]").exists()).toBe(true);
        expect(wrapper.find("[data-test=table-action]").exists()).toBe(true);
        expect(wrapper.find("[data-test=user-list-component]").exists()).toBe(true);
        expect(wrapper.find("[data-test=tfoot]").exists()).toBe(true);
    });

    it("When trackers times sum not equal zero, then table with rows is displayed and an error feedback is not displayed", () => {
        is_sum_of_times_equals_zero = false;
        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBe(false);
        expect(wrapper.find("[data-test=timetracking-loader]").exists()).toBe(false);
        expect(wrapper.find("[data-test=project-timetracking-table]").exists()).toBe(true);
        expect(wrapper.find("[data-test=empty-cell]").exists()).toBe(false);
        expect(wrapper.find("[data-test=table-row]").exists()).toBe(true);
        expect(wrapper.find("[data-test=table-action]").exists()).toBe(true);
        expect(wrapper.find("[data-test=tfoot]").exists()).toBe(true);
    });

    it("When trackers times sum equal zero and void trackers are hidden, then table with empty cell is displayed and an error feedback is not displayed", () => {
        is_sum_of_times_equals_zero = true;
        are_void_trackers_hidden = true;

        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBe(false);
        expect(wrapper.find("[data-test=timetracking-loader]").exists()).toBe(false);
        expect(wrapper.find("[data-test=project-timetracking-table]").exists()).toBe(true);
        expect(wrapper.find("[data-test=empty-cell]").exists()).toBe(true);
        expect(wrapper.find("[data-test=table-row]").exists()).toBe(false);
        expect(wrapper.find("[data-test=table-action]").exists()).toBe(true);
        expect(wrapper.find("[data-test=tfoot]").exists()).toBe(false);
    });

    it("When trackers times are not available, then table is displayed and an error feedback is not displayed", () => {
        trackers_times = [];

        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBe(false);
        expect(wrapper.find("[data-test=timetracking-loader]").exists()).toBe(false);
        expect(wrapper.find("[data-test=project-timetracking-table]").exists()).toBe(true);
        expect(wrapper.find("[data-test=empty-cell]").exists()).toBe(true);
        expect(wrapper.find("[data-test=table-row]").exists()).toBe(false);
        expect(wrapper.find("[data-test=table-action]").exists()).toBe(false);
        expect(wrapper.find("[data-test=tfoot]").exists()).toBe(false);
    });

    it("When widget is loading, then a spinner is displayed", () => {
        is_loading = true;

        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBe(false);
        expect(wrapper.find("[data-test=timetracking-loader]").exists()).toBe(true);
    });

    it("When results can't be displayed, then table is not displayed", () => {
        can_results_be_displayed = false;

        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBe(false);
        expect(wrapper.find("[data-test=project-timetracking-table]").exists()).toBe(false);
    });

    it("When results can't be displayed, then danger's div is displayed and table is not displayed", () => {
        error_message = "error";
        can_results_be_displayed = false;

        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=alert-danger]").exists()).toBe(true);
        expect(wrapper.find("[data-test=project-timetracking-table]").exists()).toBe(false);
    });

    it("When no users, then user list is not displayed", () => {
        users = [];

        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=user-list-component]").exists()).toBe(false);
    });
});
