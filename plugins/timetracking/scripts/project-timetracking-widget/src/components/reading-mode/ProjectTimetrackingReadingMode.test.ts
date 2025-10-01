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

import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";
import { describe, it, expect } from "@jest/globals";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../tests/helpers/global-options-for-tests";
import ProjectTimetrackingReadingMode from "./ProjectTimetrackingReadingMode.vue";

describe("Given a project timetracking widget on reading mode", () => {
    const getWrapper = (is_loading: boolean, is_report_saved: boolean): VueWrapper => {
        const useStore = defineStore("project-timetracking/1", {
            state: () => ({
                is_loading,
                is_report_saved,
            }),
        });

        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(ProjectTimetrackingReadingMode, {
            global: getGlobalTestOptions(pinia),
        });
    };

    it("When the widget isn't loading, then the icon spinner is not displayed", () => {
        const wrapper = getWrapper(false, false);
        expect(wrapper.find("[data-test=icon-spinner]").exists()).toBe(false);
        expect(wrapper.find("[data-test=reading-mode-actions]").exists()).toBe(true);
    });

    it("When the widget is loading, then the icon spinner is displayed", () => {
        const wrapper = getWrapper(true, false);
        expect(wrapper.find("[data-test=icon-spinner]").exists()).toBe(true);
        expect(wrapper.find("[data-test=reading-mode-actions]").exists()).toBe(true);
    });

    it("When report is saved, then saves choice are not displayed", () => {
        const wrapper = getWrapper(false, true);
        expect(wrapper.find("[data-test=icon-spinner]").exists()).toBe(false);
        expect(wrapper.find("[data-test=reading-mode-actions]").exists()).toBe(false);
    });
});
