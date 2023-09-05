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

import type { ShallowMountOptions, Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ReleaseBadgesDisplayer from "./ReleaseBadgesDisplayer.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { MilestoneData, StoreOptions } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";
import ReleaseBadgesDisplayerIfOpenSprints from "./ReleaseBadgesDisplayerIfOpenSprints.vue";
import ReleaseBadgesDisplayerIfOnlyClosedSprints from "./ReleaseBadgesDisplayerIfOnlyClosedSprints.vue";

let release_data: MilestoneData & Required<Pick<MilestoneData, "planning">>;
const component_options: ShallowMountOptions<ReleaseBadgesDisplayer> = {};

const project_id = 102;

describe("ReleaseBadgesDisplayer", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions,
    ): Promise<Wrapper<ReleaseBadgesDisplayer>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseBadgesDisplayer, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {
                project_id: project_id,
            },
        };

        component_options.propsData = { release_data };
    });

    describe("Display number of sprint", () => {
        it("When there are not open sprints, Then ReleaseBadgesDisplayerIfOpenSprints is not rendered", async () => {
            release_data = {
                id: 2,
                total_sprint: 0,
                open_sprints: [] as MilestoneData[],
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.findComponent(ReleaseBadgesDisplayerIfOpenSprints).exists()).toBe(false);
            expect(wrapper.findComponent(ReleaseBadgesDisplayerIfOnlyClosedSprints).exists()).toBe(
                true,
            );
        });

        it("When total_sprints is null, Then ReleaseBadgesDisplayerIfOpenSprints is not rendered", async () => {
            release_data = {
                id: 2,
                total_sprint: null,
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.findComponent(ReleaseBadgesDisplayerIfOpenSprints).exists()).toBe(false);
            expect(wrapper.findComponent(ReleaseBadgesDisplayerIfOnlyClosedSprints).exists()).toBe(
                true,
            );
        });

        it("When there are some open sprints, Then ReleaseBadgesDisplayerIfOpenSprints is rendered", async () => {
            release_data = {
                id: 2,
                total_sprint: 10,
                open_sprints: [
                    {
                        id: 10,
                    } as MilestoneData,
                ],
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.findComponent(ReleaseBadgesDisplayerIfOpenSprints).exists()).toBe(true);
            expect(wrapper.findComponent(ReleaseBadgesDisplayerIfOnlyClosedSprints).exists()).toBe(
                false,
            );
        });
    });
});
