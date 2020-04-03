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
import localVue from "../../helpers/local-vue.js";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";

function getWidgetModalTableInstance(store_options) {
    const store = createStoreMock(store_options);

    const component_options = {
        localVue,
        mocks: { $store: store },
    };
    return shallowMount(WidgetModalTable, component_options);
}

describe("Given a personal timetracking widget modal", () => {
    let store_options;
    beforeEach(() => {
        store_options = {
            state: {
                is_add_mode: false,
                current_times: [{ minutes: 660 }],
            },
            getters: {
                get_formatted_aggregated_time: () => "11:00",
            },
        };

        getWidgetModalTableInstance(store_options);
    });

    it("When add mode is false, then complete table should be displayed", () => {
        const wrapper = getWidgetModalTableInstance(store_options);
        expect(wrapper.contains("[data-test=table-body-with-row]")).toBeTruthy();
        expect(wrapper.contains("[data-test=edit-time-with-row]")).toBeFalsy();
        expect(wrapper.contains("[data-test=table-body-without-row]")).toBeFalsy();
        expect(wrapper.contains("[data-test=edit-time-without-row]")).toBeFalsy();
        expect(wrapper.contains("[data-test=table-foot]")).toBeTruthy();
    });

    it("When add mode is true, then table edit and rows should be displayed", () => {
        store_options.state.is_add_mode = true;
        const wrapper = getWidgetModalTableInstance(store_options);
        expect(wrapper.contains("[data-test=table-body-with-row]")).toBeTruthy();
        expect(wrapper.contains("[data-test=edit-time-with-row]")).toBeTruthy();
    });

    describe("Given an empty state", () => {
        beforeEach(() => {
            store_options.state.current_times[0].minutes = null;
        });

        it("When add mode is false, then empty table should be displayed", () => {
            const wrapper = getWidgetModalTableInstance(store_options);
            expect(wrapper.contains("[data-test=table-body-with-row]")).toBeFalsy();
            expect(wrapper.contains("[data-test=table-body-without-row]")).toBeTruthy();
        });

        it("When in add mode, then edit row should be displayed", () => {
            store_options.state.is_add_mode = true;
            const wrapper = getWidgetModalTableInstance(store_options);
            expect(wrapper.contains("[data-test=edit-time-without-row]")).toBeTruthy();
            expect(wrapper.contains("[data-test=table-foot]")).toBeFalsy();
        });
    });
});
