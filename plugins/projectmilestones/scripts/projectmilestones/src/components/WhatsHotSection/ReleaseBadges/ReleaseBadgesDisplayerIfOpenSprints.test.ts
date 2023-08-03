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
import ReleaseBadgesDisplayerIfOpenSprints from "./ReleaseBadgesDisplayerIfOpenSprints.vue";
import type { MilestoneData, MilestoneResourcesData, TrackerProjectLabel } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";
import ReleaseOthersBadges from "./ReleaseOthersBadges.vue";
import ReleaseBadgesClosedSprints from "./ReleaseBadgesClosedSprints.vue";
import ReleaseBadgesOpenSprint from "./ReleaseBadgesOpenSprint.vue";
import ReleaseBadgesAllSprints from "./ReleaseBadgesAllSprints.vue";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";

let release_data: MilestoneData;
const total_sprint = 10;
const component_options: ShallowMountOptions<ReleaseBadgesDisplayerIfOpenSprints> = {};

const project_id = 102;

describe("ReleaseBadgesDisplayerIfOpenSprints", () => {
    async function getPersonalWidgetInstance(
        user_can_view_sub_milestones_planning = true,
    ): Promise<Wrapper<ReleaseBadgesDisplayerIfOpenSprints>> {
        const useStore = defineStore("root", {
            state: () => ({
                project_id: project_id,
                user_can_view_sub_milestones_planning,
            }),
        });
        const pinia = createTestingPinia();
        useStore(pinia);
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseBadgesDisplayerIfOpenSprints, component_options);
    }

    beforeEach(() => {
        release_data = {
            id: 2,
            total_sprint,
            total_closed_sprint: 1,
            open_sprints: [] as MilestoneData[],
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
        const wrapper = await getPersonalWidgetInstance();

        expect(wrapper.findComponent(ReleaseOthersBadges).exists()).toBe(true);
    });

    describe("Display number of sprint", () => {
        it("When there are not sprints, Then ReleaseBadgesSprints is not rendered", async () => {
            release_data = {
                id: 2,
                total_sprint: 0,
                open_sprints: [] as MilestoneData[],
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance();

            expect(wrapper.find("[data-test=badge-sprint]").exists()).toBe(false);
        });

        it("When total_sprints is null, Then ReleaseBadgesSprints is not rendered", async () => {
            release_data = {
                id: 2,
                total_sprint: null,
                open_sprints: [] as MilestoneData[],
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance();

            expect(wrapper.find("[data-test=badge-sprint]").exists()).toBe(false);
        });

        it("When there are some open sprints, Then ReleaseBadgesSprints is rendered", async () => {
            release_data = {
                id: 2,
                total_sprint: 10,
                open_sprints: [
                    {
                        id: 10,
                    } as MilestoneData,
                    {
                        id: 11,
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

            const wrapper = await getPersonalWidgetInstance();

            expect(wrapper.find("[data-test=badge-sprint]").exists()).toBe(true);
        });

        it("When there is no tracker of sprint, Then ReleasesBasgesSprints is not rendered", async () => {
            release_data = {
                id: 2,
                total_sprint: null,
                open_sprints: [] as MilestoneData[],
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

            const wrapper = await getPersonalWidgetInstance();

            expect(wrapper.find("[data-test=badge-sprint]").exists()).toBe(false);
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

            const wrapper = await getPersonalWidgetInstance(false);

            expect(wrapper.find("[data-test=badge-sprint]").exists()).toBe(false);
        });
    });

    it("When the user clicked on sprints, Then a line is displayed", async () => {
        const wrapper = await getPersonalWidgetInstance();

        wrapper.setData({ open_sprints_details: true });
        expect(wrapper.find("[data-test=line-displayed]").exists()).toBe(true);
    });

    it("When the user clicked on sprints, Then ReleaseBadgesClosedSprints is rendered", async () => {
        component_options.propsData = { release_data, isOpen: false };

        const wrapper = await getPersonalWidgetInstance();

        expect(wrapper.findComponent(ReleaseBadgesClosedSprints).exists()).toBe(false);
        wrapper.setData({ open_sprints_details: true });
        await wrapper.vm.$nextTick();
        expect(wrapper.findComponent(ReleaseBadgesClosedSprints).exists()).toBe(true);
    });

    it("When sprints details is open, Then there is button to close sprint details", async () => {
        const wrapper = await getPersonalWidgetInstance();

        wrapper.setData({ open_sprints_details: true });
        expect(wrapper.find("[data-test=button-to-close]").exists()).toBe(true);

        wrapper.get("[data-test=button-to-close]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=button-to-close]").exists()).toBe(false);
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

        const wrapper = await getPersonalWidgetInstance();

        expect(wrapper.findComponent(ReleaseBadgesOpenSprint).exists()).toBe(true);
    });

    it("When component is rendered and it's not the first release, Then sprints details is closed", async () => {
        release_data = {
            id: 2,
            total_sprint: 10,
            open_sprints: [
                {
                    id: 10,
                } as MilestoneData,
                {
                    id: 11,
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

        const wrapper = await getPersonalWidgetInstance();

        expect(wrapper.findComponent(ReleaseBadgesOpenSprint).exists()).toBe(false);
    });

    it("When sprints details is open, Then ReleaseBadgesOpenSprint is rendered", async () => {
        release_data = {
            id: 22,
            total_sprint: 10,
            open_sprints: [
                {
                    id: 10,
                } as MilestoneData,
                {
                    id: 11,
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

        const wrapper = await getPersonalWidgetInstance();

        expect(wrapper.findComponent(ReleaseBadgesOpenSprint).exists()).toBe(false);
        expect(wrapper.findComponent(ReleaseBadgesAllSprints).exists()).toBe(true);

        wrapper.setData({ open_sprints_details: true });
        await wrapper.vm.$nextTick();
        expect(wrapper.findComponent(ReleaseBadgesOpenSprint).exists()).toBe(true);
        expect(wrapper.findComponent(ReleaseBadgesAllSprints).exists()).toBe(false);
    });

    it("When there is only one sprint and no closed sprints and it's not the first release, Then sprints details is open", async () => {
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

        const wrapper = await getPersonalWidgetInstance();

        expect(wrapper.findComponent(ReleaseBadgesOpenSprint).exists()).toBe(true);
    });

    it("When there are only open sprint and no closed, Then ReleaseClosedSprints is not rendered", async () => {
        release_data = {
            id: 2,
            total_sprint: 10,
            open_sprints: [
                {
                    id: 10,
                } as MilestoneData,
                {
                    id: 11,
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

        const wrapper = await getPersonalWidgetInstance();

        expect(wrapper.findComponent(ReleaseBadgesOpenSprint).exists()).toBe(false);
        expect(wrapper.findComponent(ReleaseBadgesClosedSprints).exists()).toBe(false);
    });
});
