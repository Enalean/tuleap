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
import { createLocalVueForTests } from "../../../tests/helpers/local-vue";
import TimeTrackingOverviewReadingMode from "./TimeTrackingOverviewReadingMode.vue";

describe("Given a timetracking overview widget on reading mode", () => {
    let is_loading, is_report_saved;

    beforeEach(() => {
        is_loading = false;
        is_report_saved = false;
    });

    const getWrapper = async () => {
        const useStore = defineStore("overview/1", {
            state: () => ({
                is_loading,
                is_report_saved,
            }),
        });

        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(TimeTrackingOverviewReadingMode, {
            localVue: await createLocalVueForTests(),
        });
    };

    it("When the widget isn't loading, then the icon spinner is not displayed", async () => {
        const wrapper = await getWrapper();
        expect(wrapper.find("[data-test=icon-spinner]").exists()).toBe(false);
        expect(wrapper.find("[data-test=reading-mode-actions]").exists()).toBe(true);
    });

    it("When the widget is loading, then the icon spinner is displayed", async () => {
        is_loading = true;

        const wrapper = await getWrapper();

        expect(wrapper.find("[data-test=icon-spinner]").exists()).toBe(true);
        expect(wrapper.find("[data-test=reading-mode-actions]").exists()).toBe(true);
    });

    it("When report is saved, then saves choice are not displayed", async () => {
        is_report_saved = true;

        const wrapper = await getWrapper();
        expect(wrapper.find("[data-test=icon-spinner]").exists()).toBe(false);
        expect(wrapper.find("[data-test=reading-mode-actions]").exists()).toBe(false);
    });
});
