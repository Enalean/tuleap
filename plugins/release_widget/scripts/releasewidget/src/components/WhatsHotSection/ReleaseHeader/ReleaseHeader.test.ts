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

import { shallowMount, ShallowMountOptions, Wrapper } from "@vue/test-utils";
import ReleaseHeader from "./ReleaseHeader.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper-jest";
import { MilestoneData, StoreOptions } from "../../../type";
import { setUserLocale } from "../../../helpers/user-locale-helper";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";

let releaseData: MilestoneData;
let component_options: ShallowMountOptions<ReleaseHeader>;

describe("ReleaseHeader", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<ReleaseHeader>> {
        store = createStoreMock(store_options);
        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseHeader, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {}
        };

        releaseData = {
            label: "mile",
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            end_date: new Date("2019-10-05T13:42:08+02:00").toDateString(),
            number_of_artifact_by_trackers: []
        };

        component_options = {
            propsData: {
                releaseData
            }
        };
    });

    describe("Display arrow between dates", () => {
        it("When there are a start date and end date, Then an arrow is displayed", async () => {
            setUserLocale("en-US");

            const wrapper = await getPersonalWidgetInstance(store_options);
            expect(wrapper.element).toMatchSnapshot();
        });

        it("When there isn't a start date of a release, Then there isn't an arrow", async () => {
            releaseData = {
                label: "mile",
                id: 2,
                start_date: null,
                number_of_artifact_by_trackers: []
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = await getPersonalWidgetInstance(store_options);
            expect(wrapper.element).toMatchSnapshot();
        });
    });

    it("When the widget is loading, Then there is a skeleton instead of points", async () => {
        releaseData = {
            label: "mile",
            id: 2,
            start_date: null,
            number_of_artifact_by_trackers: []
        };

        component_options.propsData = {
            releaseData,
            disabled: true
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.element).toMatchSnapshot();
    });
});
