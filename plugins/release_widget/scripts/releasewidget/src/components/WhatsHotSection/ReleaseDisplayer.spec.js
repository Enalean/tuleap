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

import Vue from "vue";
import GetTextPlugin from "vue-gettext";
import { shallowMount } from "@vue/test-utils";
import ReleaseDisplayer from "./ReleaseDisplayer.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";
import ReleaseHeader from "./ReleaseHeader.vue";

let releaseData = {};
let component_options = {};

describe("ReleaseDisplayer", () => {
    let store_options;
    let store;

    function getPersonalWidgetInstance(store_options) {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };

        Vue.use(GetTextPlugin, {
            translations: {},
            silent: true
        });

        return shallowMount(ReleaseDisplayer, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {}
        };

        releaseData = {
            label: "mile",
            id: 2,
            start_date: Date("2017-01-22T13:42:08+02:00"),
            capacity: 10,
            total_sprint: 20,
            initial_effort: 10
        };

        component_options = {
            propsData: {
                releaseData
            },
            data() {
                return {
                    is_open: false
                };
            }
        };

        getPersonalWidgetInstance(store_options);
    });

    it("When the user toggle twice a release, the content widget is displayed first and hidden after", () => {
        const wrapper = getPersonalWidgetInstance(store_options);

        wrapper.find(ReleaseHeader).vm.$emit("toggleReleaseDetails");
        expect(wrapper.contains("[data-test=toggle_open]")).toBeTruthy();

        wrapper.find(ReleaseHeader).vm.$emit("toggleReleaseDetails");
        expect(wrapper.contains("[data-test=toggle_open]")).toBeFalsy();
    });
});
