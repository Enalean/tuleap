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
import TimeTrackingOverviewTableRow from "./TimeTrackingOverviewTableRow.vue";
import { createLocalVueForTests } from "../../tests/helpers/local-vue.js";

describe("Given a timetracking overview widget", () => {
    let are_void_trackers_hidden, is_tracker_total_sum_equals_zero;

    beforeEach(() => {
        are_void_trackers_hidden = false;
        is_tracker_total_sum_equals_zero = false;
    });

    const getWrapper = async () => {
        const useStore = defineStore("overview/1", {
            state: () => ({
                are_void_trackers_hidden,
            }),
            getters: {
                get_formatted_time: () => () => "10:30",
                is_tracker_total_sum_equals_zero: () => () => is_tracker_total_sum_equals_zero,
            },
        });
        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(TimeTrackingOverviewTableRow, {
            localVue: await createLocalVueForTests(),
            propsData: {
                time: {
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
                },
            },
        });
    };

    it("When tracker total sum not equal zero, then table row is displayed", async () => {
        const wrapper = await getWrapper();

        expect(wrapper.find("[data-test=timetracking-overview-table-row]").exists()).toBeTruthy();
    });

    it("When tracker total sum equal zero and void trackers displayed, then table row is displayed", async () => {
        is_tracker_total_sum_equals_zero = true;

        const wrapper = await getWrapper();

        expect(wrapper.find("[data-test=timetracking-overview-table-row]").exists()).toBeTruthy();
    });

    it("When tracker total sum not equal zero and void trackers not displayed, then table row is displayed", async () => {
        are_void_trackers_hidden = true;

        const wrapper = await getWrapper();

        expect(wrapper.find("[data-test=timetracking-overview-table-row]").exists()).toBeTruthy();
    });

    it("When tracker total sum equal zero and void trackers not displayed, then table row is not displayed", async () => {
        is_tracker_total_sum_equals_zero = true;
        are_void_trackers_hidden = true;

        const wrapper = await getWrapper();

        expect(wrapper.find("[data-test=timetracking-overview-table-row]").exists()).toBeFalsy();
    });
});
