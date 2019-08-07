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
import ReleaseHeader from "./ReleaseHeader.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper";
import { ComponentOption, MilestoneData, StoreOptions } from "../../../type";

let releaseData: MilestoneData;
let component_options: ComponentOption;

describe("ReleaseHeader", () => {
    let store_options: StoreOptions;
    let store;

    function getPersonalWidgetInstance(store_options: StoreOptions) {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };

        Vue.use(GetTextPlugin, {
            translations: {},
            silent: true
        });

        return shallowMount(ReleaseHeader, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {}
        };

        releaseData = {
            label: "mile",
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00"),
            capacity: 10
        };

        component_options = {
            propsData: {
                releaseData
            }
        };

        getPersonalWidgetInstance(store_options);
    });

    describe("Display arrow between dates", () => {
        it("When there is a start date of a release, Then an arrow is displayed", () => {
            const wrapper = getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=display-arrow]")).toBeTruthy();
        });

        it("When there isn't a start date of a release, Then there isn't an arrow", () => {
            releaseData = {
                label: "mile",
                id: 2,
                start_date: null
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=display-arrow]")).toBeFalsy();
        });
    });

    it("When the widget is rendered, Then the component ReleaseHeaderRemainingEffort is displayed", () => {
        releaseData = {
            label: "mile",
            id: 2
        };

        component_options.propsData = {
            releaseData
        };

        const wrapper = getPersonalWidgetInstance(store_options);

        expect(wrapper.contains("[data-test=display-remaining-days]")).toBeTruthy();
        expect(wrapper.contains("[data-test=display-remaining-points]")).toBeTruthy();
    });
});
