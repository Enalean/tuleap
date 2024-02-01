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
import { createLocalVueForTests } from "../../../tests/helpers/local-vue.js";
import TimeTrackingOverviewReadingMode from "./TimeTrackingOverviewReadingMode.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("Given a timetracking overview widget on reading mode", () => {
    let component_options, store_options, store;
    beforeEach(async () => {
        store_options = {
            state: {
                is_loading: false,
                is_report_saved: false,
            },
            getters: {
                has_success_message: false,
            },
        };
        store = createStoreMock(store_options);

        component_options = {
            localVue: await createLocalVueForTests(),
            mocks: { $store: store },
        };
    });

    it("When the widget isn't loading, then the icon spinner is not displayed", () => {
        const wrapper = shallowMount(TimeTrackingOverviewReadingMode, component_options);
        expect(wrapper.find("[data-test=icon-spinner]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=reading-mode-actions]").exists()).toBeTruthy();
    });

    it("When the widget is loading, then the icon spinner is displayed", () => {
        store.state.is_loading = true;

        const wrapper = shallowMount(TimeTrackingOverviewReadingMode, component_options);
        expect(wrapper.find("[data-test=icon-spinner]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=reading-mode-actions]").exists()).toBeTruthy();
    });

    it("When report is saved, then saves choice are not displayed", () => {
        store.state.is_report_saved = true;

        const wrapper = shallowMount(TimeTrackingOverviewReadingMode, component_options);
        expect(wrapper.find("[data-test=icon-spinner]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=reading-mode-actions]").exists()).toBeFalsy();
    });
});
