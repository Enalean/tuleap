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

let release_data: MilestoneData & Required<Pick<MilestoneData, "planning">>;
const component_options: ShallowMountOptions<ReleaseDescription> = {};
const project_id = 102;

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

        release_data = {
            id: 2,
            planning: {
                id: "100"
            },
            number_of_artifact_by_trackers: []
        };

        component_options.propsData = {
            release_data
        };
    });

    it("Given user display widget, Then a good link to top planning of the release is rendered", async () => {
        store_options.state.project_id = project_id;

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.element).toMatchSnapshot();
    });

    it("When there is a description, Then there is a tooltip to show the whole description ", async () => {
        const description =
            "This is a big description, so I write some things, stuff, foo, bar. This is a big description, so I write some things, stuff, foo, bar.";

        release_data = {
            id: 2,
            planning: {
                id: "100"
            },
            number_of_artifact_by_trackers: [],
            description
        };

        component_options.propsData = {
            release_data
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.find("[data-test=tooltip-description]").text()).toEqual(description);
    });
});
