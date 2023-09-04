/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import ReleaseBadgesClosedSprints from "./ReleaseBadgesClosedSprints.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { MilestoneData, StoreOptions, TrackerProjectLabel } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";

let release_data: MilestoneData & Required<Pick<MilestoneData, "planning">>;
const total_sprint = 10;
const component_options: ShallowMountOptions<ReleaseBadgesClosedSprints> = {};

const project_id = 102;

describe("ReleaseBadgesClosedSprints", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions,
    ): Promise<Wrapper<ReleaseBadgesClosedSprints>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseBadgesClosedSprints, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {
                project_id: project_id,
            },
        };

        component_options.propsData = { release_data };
    });

    describe("Display total of closed sprints", () => {
        it("When there are some closed sprints, Then the total is displayed", async () => {
            release_data = {
                id: 2,
                total_sprint,
                total_closed_sprint: 6,
                resources: {
                    milestones: {
                        accept: {
                            trackers: [
                                {
                                    label: "sprint",
                                },
                            ],
                        },
                    },
                },
            } as MilestoneData;

            component_options.propsData = { release_data };
            store_options.state.user_can_view_sub_milestones_planning = true;

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.find("[data-test=total-closed-sprints]").exists()).toBe(true);
        });

        it("When the total of closed sprints is null, Then the total is not displayed", async () => {
            release_data = {
                id: 2,
                total_sprint,
                total_closed_sprint: null,
                resources: {
                    milestones: {
                        accept: {
                            trackers: [
                                {
                                    label: "sprint",
                                },
                            ],
                        },
                    },
                },
            } as MilestoneData;

            component_options.propsData = { release_data };
            store_options.state.user_can_view_sub_milestones_planning = true;
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.find("[data-test=total-closed-sprints]").exists()).toBe(false);
        });

        it("When the total of closed sprints is 0, Then the total is displayed", async () => {
            release_data = {
                id: 2,
                total_sprint,
                total_closed_sprint: 0,
                resources: {
                    milestones: {
                        accept: {
                            trackers: [
                                {
                                    label: "sprint",
                                },
                            ],
                        },
                    },
                },
            } as MilestoneData;

            component_options.propsData = { release_data };
            store_options.state.user_can_view_sub_milestones_planning = true;
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.find("[data-test=total-closed-sprints]").exists()).toBe(true);
        });

        it("When there is no trackers of sprints, Then the total is not displayed", async () => {
            release_data = {
                id: 2,
                total_sprint,
                total_closed_sprint: 0,
                resources: {
                    milestones: {
                        accept: {
                            trackers: [] as TrackerProjectLabel[],
                        },
                    },
                },
            } as MilestoneData;

            component_options.propsData = { release_data };
            store_options.state.user_can_view_sub_milestones_planning = true;
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.find("[data-test=total-closed-sprints]").exists()).toBe(false);
        });

        it("When the user can't see the tracker's label, Then the total is not displayed", async () => {
            release_data = {
                id: 2,
                total_sprint,
                total_closed_sprint: 6,
                resources: {
                    milestones: {
                        accept: {
                            trackers: [
                                {
                                    label: "sprint",
                                },
                            ],
                        },
                    },
                },
            } as MilestoneData;

            component_options.propsData = { release_data };
            store_options.state.user_can_view_sub_milestones_planning = false;

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.find("[data-test=total-closed-sprints]").exists()).toBe(false);
        });
    });
});
