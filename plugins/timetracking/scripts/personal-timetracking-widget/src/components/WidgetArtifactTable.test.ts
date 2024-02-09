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

import { describe, beforeEach, it, expect, vi } from "vitest";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import WidgetArtifactTable from "./WidgetArtifactTable.vue";
import { createLocalVueForTests } from "../helpers/local-vue.js";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";
import type { PersonalTime } from "@tuleap/plugin-timetracking-rest-api-types";
import type Vue from "vue";

describe("Given a personal timetracking widget", () => {
    let times: PersonalTime[][];
    let is_loading: boolean;
    let has_rest_error: boolean;
    let can_load_more: boolean;
    let can_results_be_displayed: boolean;

    async function getWidgetArtifactTableInstance(): Promise<Wrapper<Vue>> {
        const useStore = defineStore("root", {
            state: () => ({
                error_message: "",
                times,
                is_loading,
                is_loaded: true,
            }),
            getters: {
                has_rest_error: () => has_rest_error,
                can_load_more: () => can_load_more,
                can_results_be_displayed: () => can_results_be_displayed,
                get_formatted_total_sum: () => "00:00",
            },
            actions: {
                loadFirstBatchOfTimes() {},
            },
        });
        const pinia = createTestingPinia({ createSpy: vi.fn });
        useStore(pinia);

        const component_options = {
            localVue: await createLocalVueForTests(),
            pinia,
        };
        return shallowMount(WidgetArtifactTable, component_options);
    }

    beforeEach(() => {
        times = [[{ step: "time" }]] as PersonalTime[][];
        is_loading = false;
        has_rest_error = false;
        can_load_more = false;
        can_results_be_displayed = true;
    });
    it("When no error and result can be displayed, then complete table should be displayed", async () => {
        const wrapper = await getWidgetArtifactTableInstance();
        expect(wrapper.find("[data-test=alert-danger]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=timetracking-loader]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=artifact-table]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=load-more]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=table-foot]").exists()).toBeTruthy();
    });

    it("When rest error and more times can be load, then danger message and load more button should be displayed", async () => {
        has_rest_error = true;
        can_load_more = true;
        const wrapper = await getWidgetArtifactTableInstance();
        expect(wrapper.find("[data-test=alert-danger]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=load-more]").exists()).toBeTruthy();
    });

    it("When widget is loading and result can't be displayed, then loader should be displayed but not table", async () => {
        is_loading = true;
        can_results_be_displayed = false;
        const wrapper = await getWidgetArtifactTableInstance();
        expect(wrapper.find("[data-test=timetracking-loader]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=artifact-table]").exists()).toBeFalsy();
    });

    it("When no times, then table with empty tab should be displayed", async () => {
        times = [];
        const wrapper = await getWidgetArtifactTableInstance();
        expect(wrapper.find("[data-test=empty-tab]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=table-foot]").exists()).toBeFalsy();
    });
});
