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

import { shallowMount, ShallowMountOptions, Wrapper } from "@vue/test-utils";
import ReleaseBadgesDisplayerIfOpenSprints from "./ReleaseBadgesDisplayerIfOpenSprints.vue";
import { createStoreMock } from "../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import {
    MilestoneData,
    MilestoneResourcesData,
    StoreOptions,
    TrackerProjectLabel,
} from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";
import ReleaseOthersBadges from "./ReleaseOthersBadges.vue";
import ReleaseBadgesClosedSprints from "./ReleaseBadgesClosedSprints.vue";
import ReleaseBadgesOpenSprint from "./ReleaseBadgesOpenSprint.vue";
import ReleaseBadgesAllSprints from "./ReleaseBadgesAllSprints.vue";

let release_data: MilestoneData;
const total_sprint = 10;
const component_options: ShallowMountOptions<ReleaseBadgesDisplayerIfOpenSprints> = {};

const project_id = 102;

describe("ReleaseBadgesDisplayerIfOpenSprints", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<ReleaseBadgesDisplayerIfOpenSprints>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseBadgesDisplayerIfOpenSprints, component_options);
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

        component_options.propsData = { release_data, isOpen: true };
    });

    it("When the component is rendered, Then ReleaseBasgesOthersSprints is rendered", async () => {
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains(ReleaseOthersBadges)).toBe(true);
    });

    describe("Display number of sprint", () => {
        it("When there are not sprints, Then ReleaseBadgesSprints is not rendered", async () => {
            release_data = {
                id: 2,
                total_sprint: 0,
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=badge-sprint]")).toBe(false);
        });

        it("When total_sprints is null, Then ReleaseBadgesSprints is not rendered", async () => {
            release_data = {
                id: 2,
                total_sprint: null,
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=badge-sprint]")).toBe(false);
        });

        it("When there are some open sprints, Then ReleaseBadgesSprints is rendered", async () => {
            release_data = {
                id: 2,
                total_sprint: 10,
                open_sprints: [
                    {
                        id: 10,
                    } as MilestoneData,
                ],
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

            component_options.propsData = {
                release_data,
            };

            store_options.state.user_can_view_sub_milestones_planning = true;

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=badge-sprint]")).toBe(true);
        });

        it("When there is no tracker of sprint, Then ReleasesBasgesSprints is not rendered", async () => {
            release_data = {
                id: 2,
                total_sprint: null,
                resources: {
                    milestones: {
                        accept: {
                            trackers: [] as TrackerProjectLabel[],
                        },
                    },
                } as MilestoneResourcesData,
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=badge-sprint]")).toBe(false);
        });

        it("When the user can't see the tracker, Then ReleasesBasgesAllSprints is not rendered", async () => {
            release_data = {
                id: 2,
                total_sprint: 10,
                open_sprints: [
                    {
                        id: 10,
                    } as MilestoneData,
                ],
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

            component_options.propsData = {
                release_data,
            };

            store_options.state.user_can_view_sub_milestones_planning = false;

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=badge-sprint]")).toBe(false);
        });
    });

    it("When the user clicked on sprints, Then a line is displayed", async () => {
        const wrapper = await getPersonalWidgetInstance(store_options);

        wrapper.setData({ open_sprints_details: true });
        expect(wrapper.contains("[data-test=line-displayed]")).toBe(true);
    });

    it("When the user clicked on sprints, Then ReleaseBadgesClosedSprints is rendered", async () => {
        store_options.state.user_can_view_sub_milestones_planning = true;
        component_options.propsData = { release_data, isOpen: false };

        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains(ReleaseBadgesClosedSprints)).toBe(false);
        wrapper.setData({ open_sprints_details: true });
        await wrapper.vm.$nextTick();
        expect(wrapper.contains(ReleaseBadgesClosedSprints)).toBe(true);
    });

    it("When sprints details is open, Then there is button to close sprint details", async () => {
        const wrapper = await getPersonalWidgetInstance(store_options);

        wrapper.setData({ open_sprints_details: true });
        expect(wrapper.contains("[data-test=button-to-close]")).toBe(true);

        wrapper.get("[data-test=button-to-close]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.contains("[data-test=button-to-close]")).toBe(false);
    });

    it("When component is rendered and it's the first release, Then sprints details is open", async () => {
        release_data = {
            id: 2,
            total_sprint: 10,
            open_sprints: [
                {
                    id: 10,
                } as MilestoneData,
            ],
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

        component_options.propsData = {
            release_data,
            isOpen: true,
        };

        store_options.state.user_can_view_sub_milestones_planning = true;

        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains(ReleaseBadgesOpenSprint)).toBe(true);
    });

    it("When component is rendered and it's not the first release, Then sprints details is closed", async () => {
        release_data = {
            id: 2,
            total_sprint: 10,
            open_sprints: [
                {
                    id: 10,
                } as MilestoneData,
            ],
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

        component_options.propsData = {
            release_data,
            isOpen: false,
        };

        store_options.state.user_can_view_sub_milestones_planning = true;

        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains(ReleaseBadgesOpenSprint)).toBe(false);
    });

    it("When sprints details is open, Then ReleaseBadgesOpenSprint is rendered", async () => {
        release_data = {
            id: 22,
            total_sprint: 10,
            open_sprints: [
                {
                    id: 10,
                } as MilestoneData,
            ],
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

        component_options.propsData = {
            release_data,
            isOpen: false,
        };

        store_options.state.user_can_view_sub_milestones_planning = true;

        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains(ReleaseBadgesOpenSprint)).toBe(false);
        expect(wrapper.contains(ReleaseBadgesAllSprints)).toBe(true);

        wrapper.setData({ open_sprints_details: true });
        await wrapper.vm.$nextTick();
        expect(wrapper.contains(ReleaseBadgesOpenSprint)).toBe(true);
        expect(wrapper.contains(ReleaseBadgesAllSprints)).toBe(false);
    });
});
