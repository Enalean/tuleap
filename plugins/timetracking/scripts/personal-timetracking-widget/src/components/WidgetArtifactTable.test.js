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
import WidgetArtifactTable from "./WidgetArtifactTable.vue";
import localVue from "../helpers/local-vue.js";
import { createStoreMock } from "../../../../../../src/www/scripts/vue-components/store-wrapper-jest.js";

function getWidgetArtifactTableInstance(store_options) {
    const store = createStoreMock(store_options);

    const component_options = {
        localVue,
        mocks: { $store: store },
    };
    return shallowMount(WidgetArtifactTable, component_options);
}

describe("Given a personal timetracking widget", () => {
    let store_options;
    beforeEach(() => {
        store_options = {
            state: {
                error_message: "",
                times: [[{ time: "time" }]],
                is_loading: false,
            },
            getters: {
                get_formatted_total_sum: "00:00",
                has_rest_error: false,
                can_results_be_displayed: true,
                can_load_more: false,
            },
        };

        getWidgetArtifactTableInstance(store_options);
    });

    it("When no error and result can be displayed, then complete table should be displayed", () => {
        const wrapper = getWidgetArtifactTableInstance(store_options);
        expect(wrapper.contains("[data-test=alert-danger]")).toBeFalsy();
        expect(wrapper.contains("[data-test=timetracking-loader]")).toBeFalsy();
        expect(wrapper.contains("[data-test=artifact-table]")).toBeTruthy();
        expect(wrapper.contains("[data-test=load-more]")).toBeFalsy();
        expect(wrapper.contains("[data-test=table-foot]")).toBeTruthy();
    });

    it("When rest error and more times can be load, then danger message and load more button should be displayed", () => {
        store_options.getters.has_rest_error = true;
        store_options.getters.can_load_more = true;
        const wrapper = getWidgetArtifactTableInstance(store_options);
        expect(wrapper.contains("[data-test=alert-danger]")).toBeTruthy();
        expect(wrapper.contains("[data-test=load-more]")).toBeTruthy();
    });

    it("When widget is loading and result can't be displayed, then loader should be displayed but not table", () => {
        store_options.state.is_loading = true;
        store_options.getters.can_results_be_displayed = false;
        const wrapper = getWidgetArtifactTableInstance(store_options);
        expect(wrapper.contains("[data-test=timetracking-loader]")).toBeTruthy();
        expect(wrapper.contains("[data-test=artifact-table]")).toBeFalsy();
    });

    it("When no times, then table with empty tab should be displayed", () => {
        store_options.state.times = [];
        const wrapper = getWidgetArtifactTableInstance(store_options);
        expect(wrapper.contains("[data-test=empty-tab]")).toBeTruthy();
        expect(wrapper.contains("[data-test=table-foot]")).toBeFalsy();
    });
});
