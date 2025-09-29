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
import { getGlobalTestOptions } from "../../tests/helpers/global-options-for-tests";
import ProjectTimetracking from "./ProjectTimetracking.vue";

const report_id = 8;
const noop = (): void => {
    // Do nothing
};

describe("Given a project timetracking widget", () => {
    let reading_mode: boolean, success_message: null | string;

    beforeEach(() => {
        reading_mode = true;
        success_message = null;
    });

    const getWrapper = (): VueWrapper => {
        const useStore = defineStore("project-timetracking/8", {
            state: () => ({
                reading_mode,
                success_message,
            }),
            getters: {
                has_success_message: () => success_message !== null,
            },
            actions: {
                setReportId: noop,
                initUserId: noop,
                setDisplayVoidTrackers: noop,
                initWidgetWithReport: noop,
                getProjects: noop,
            },
        });
        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(ProjectTimetracking, {
            global: getGlobalTestOptions(pinia),
            props: { report_id: report_id, user_id: 101, are_void_trackers_hidden: true },
        });
    };

    it("When reading mode is true, then writing should not be displayed", () => {
        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=report-success]").exists()).toBe(false);
        expect(wrapper.find("[data-test=reading-mode]").exists()).toBe(true);
        expect(wrapper.find("[data-test=writing-mode]").exists()).toBe(false);
    });

    it("When success message, then a success message is displayed", () => {
        success_message = "Great success!";

        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=report-success]").exists()).toBe(true);
    });

    it("When reading mode is false, then writing should be displayed", () => {
        reading_mode = false;

        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=report-success]").exists()).toBe(false);
        expect(wrapper.find("[data-test=reading-mode]").exists()).toBe(false);
        expect(wrapper.find("[data-test=writing-mode]").exists()).toBe(true);
    });
});
