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
import WidgetModalTable from "./WidgetModalTable.vue";
import { createLocalVueForTests } from "../../helpers/local-vue.js";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";

describe("Given a personal timetracking widget modal", () => {
    let is_add_mode;
    let current_times;
    let get_formatted_aggregated_time;

    async function getWidgetModalTableInstance() {
        const useStore = defineStore("root", {
            state: () => ({
                is_add_mode,
                current_times,
            }),
            getters: {
                get_formatted_aggregated_time: () => () => get_formatted_aggregated_time,
            },
        });
        const pinia = createTestingPinia();
        useStore(pinia);

        const component_options = {
            localVue: await createLocalVueForTests(),
            pinia,
        };
        return shallowMount(WidgetModalTable, component_options);
    }

    beforeEach(() => {
        is_add_mode = false;
        current_times = [{ minutes: 660 }];
        get_formatted_aggregated_time = "11:00";
    });

    it("When add mode is false, then complete table should be displayed", async () => {
        const wrapper = await getWidgetModalTableInstance();
        expect(wrapper.find("[data-test=table-body-with-row]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=edit-time-with-row]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=table-body-without-row]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=edit-time-without-row]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=table-foot]").exists()).toBeTruthy();
    });

    it("When add mode is true, then table edit and rows should be displayed", async () => {
        is_add_mode = true;
        const wrapper = await getWidgetModalTableInstance();
        expect(wrapper.find("[data-test=table-body-with-row]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=edit-time-with-row]").exists()).toBeTruthy();
    });

    describe("Given an empty state", () => {
        beforeEach(() => {
            current_times.length = 0;
        });

        it("When add mode is false, then empty table should be displayed", async () => {
            const wrapper = await getWidgetModalTableInstance();
            expect(wrapper.find("[data-test=table-body-with-row]").exists()).toBeFalsy();
            expect(wrapper.find("[data-test=table-body-without-row]").exists()).toBeTruthy();
        });

        it("When in add mode, then edit row should be displayed", async () => {
            is_add_mode = true;
            const wrapper = await getWidgetModalTableInstance();
            expect(wrapper.find("[data-test=edit-time-without-row]").exists()).toBeTruthy();
            expect(wrapper.find("[data-test=table-foot]").exists()).toBeFalsy();
        });
    });
});
