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
import localVue from "../../helpers/local-vue.js";
import TimeTrackingOverviewReadingMode from "../../../components/reading-mode/TimeTrackingOverviewReadingMode.vue";
import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";

describe("Given a timetracking overview widget on reading mode", () => {
    let component_options, store_options, store;
    beforeEach(() => {
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
            localVue,
            mocks: { $store: store },
        };
    });

    it("When the widget isn't loading, then the icon spinner is not displayed", () => {
        const wrapper = shallowMount(TimeTrackingOverviewReadingMode, component_options);
        expect(wrapper.contains("[data-test=icon-spinner]")).toBeFalsy();
        expect(wrapper.contains("[data-test=reading-mode-actions]")).toBeTruthy();
    });

    it("When the widget is loading, then the icon spinner is displayed", () => {
        store.state.is_loading = true;

        const wrapper = shallowMount(TimeTrackingOverviewReadingMode, component_options);
        expect(wrapper.contains("[data-test=icon-spinner]")).toBeTruthy();
        expect(wrapper.contains("[data-test=reading-mode-actions]")).toBeTruthy();
    });

    it("When report is saved, then saves choice are not displayed", () => {
        store.state.is_report_saved = true;

        const wrapper = shallowMount(TimeTrackingOverviewReadingMode, component_options);
        expect(wrapper.contains("[data-test=icon-spinner]")).toBeFalsy();
        expect(wrapper.contains("[data-test=reading-mode-actions]")).toBeFalsy();
    });
});
