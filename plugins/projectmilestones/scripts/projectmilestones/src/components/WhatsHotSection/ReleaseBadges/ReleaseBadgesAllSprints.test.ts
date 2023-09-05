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
import ReleaseBadgesAllSprints from "./ReleaseBadgesAllSprints.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { MilestoneData, StoreOptions, TrackerProjectLabel } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";

let release_data: MilestoneData & Required<Pick<MilestoneData, "planning">>;
const total_sprint = 10;
const initial_effort = 10;
const component_options: ShallowMountOptions<ReleaseBadgesAllSprints> = {};

const project_id = 102;

describe("ReleaseBadgesAllSprints", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions,
    ): Promise<Wrapper<ReleaseBadgesAllSprints>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseBadgesAllSprints, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {
                project_id: project_id,
            },
        };

        release_data = {
            id: 2,
            total_sprint,
            initial_effort,
            resources: {
                milestones: {
                    accept: {
                        trackers: [
                            {
                                label: "Sprint1",
                            },
                        ],
                    },
                },
            },
        } as MilestoneData;

        component_options.propsData = { release_data };
    });

    describe("Display number of sprint", () => {
        it("When there is a tracker, Then number of sprint is displayed", async () => {
            store_options.state.user_can_view_sub_milestones_planning = true;
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.get("[data-test=badge-sprint]").text()).toBe("10 Sprint1");
        });

        it("When there isn't tracker, Then there is no link", async () => {
            release_data = {
                id: 2,
                total_sprint,
                initial_effort,
                resources: {
                    milestones: {
                        accept: {
                            trackers: [] as TrackerProjectLabel[],
                        },
                    },
                },
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };
            store_options.state.user_can_view_sub_milestones_planning = true;
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.find("[data-test=badge-sprint]").exists()).toBe(false);
        });

        it("When the user can't see the tracker, Then number of sprint is not displayed", async () => {
            store_options.state.user_can_view_sub_milestones_planning = false;
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.find("[data-test=badge-sprint]").exists()).toBe(false);
        });
    });
});
