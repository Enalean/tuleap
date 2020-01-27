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
import ReleaseDescription from "./ReleaseDescription.vue";
import { createStoreMock } from "../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { MilestoneData, StoreOptions } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";
import BurndownChart from "./Chart/BurndownChart.vue";

let release_data: MilestoneData;
const component_options: ShallowMountOptions<ReleaseDescription> = {};

describe("ReleaseDescription", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<ReleaseDescription>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseDescription, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {
                label_tracker_planning: "Releases"
            }
        };

        component_options.propsData = {
            release_data
        };
    });

    it("When there is a description, Then there is a tooltip to show the whole description", async () => {
        const description =
            "This is a big description, so I write some things, stuff, foo, bar. This is a big description, so I write some things, stuff, foo, bar.";

        release_data = {
            id: 2,
            description,
            resources: {
                burndown: null
            }
        } as MilestoneData;

        component_options.propsData = {
            release_data
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.find("[data-test=tooltip-description]").text()).toEqual(description);
    });

    it("When there isn't any burndown, Then the BurndownChart is not rendered", async () => {
        release_data = {
            id: 2,
            resources: {
                burndown: null
            }
        } as MilestoneData;

        component_options.propsData = {
            release_data
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.contains(BurndownChart)).toBe(false);
    });

    it("When there is a burndown, Then the BurndownChart is rendered", async () => {
        release_data = {
            id: 2,
            resources: {
                burndown: {
                    uri: "/burndown"
                }
            }
        } as MilestoneData;

        component_options.propsData = {
            release_data
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.contains(BurndownChart)).toBe(true);
    });
});
