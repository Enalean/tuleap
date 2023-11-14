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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ReleaseBadgesDisplayerIfOpenSprints from "./ReleaseBadgesDisplayerIfOpenSprints.vue";
import type { MilestoneData, TrackerProjectLabel } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";
import ReleaseOthersBadges from "./ReleaseOthersBadges.vue";
import ReleaseBadgesClosedSprints from "./ReleaseBadgesClosedSprints.vue";
import ReleaseBadgesOpenSprint from "./ReleaseBadgesOpenSprint.vue";
import ReleaseBadgesAllSprints from "./ReleaseBadgesAllSprints.vue";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";

describe("ReleaseBadgesDisplayerIfOpenSprints", () => {
    async function getPersonalWidgetInstance(
        user_can_view_sub_milestones_planning: boolean,
        total_sprint: number | null,
        open_sprints: Array<MilestoneData>,
        trackers: Array<TrackerProjectLabel>,
        is_open: boolean,
    ): Promise<Wrapper<Vue, Element>> {
        const release_data = {
            id: 2,
            total_sprint,
            total_closed_sprint: 1,
            open_sprints,
            resources: {
                milestones: {
                    accept: {
                        trackers,
                    },
                },
            },
        } as MilestoneData;

        const useStore = defineStore("root", {
            state: () => ({
                user_can_view_sub_milestones_planning,
            }),
        });
        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(ReleaseBadgesDisplayerIfOpenSprints, {
            localVue: await createReleaseWidgetLocalVue(),
            propsData: {
                release_data,
                isOpen: is_open,
                isPastRelease: false,
            },
            pinia,
        });
    }

    it("When the component is rendered, Then ReleaseBasgesOthersSprints is rendered", async () => {
        const total_sprint = 10;
        const open_sprints: Array<MilestoneData> = [];
        const trackers = [
            {
                label: "Sprint1",
            },
        ];
        const wrapper = await getPersonalWidgetInstance(
            true,
            total_sprint,
            open_sprints,
            trackers,
            true,
        );

        expect(wrapper.findComponent(ReleaseOthersBadges).exists()).toBe(true);
    });

    describe("Display number of sprint", () => {
        it("When there are no sprints, Then ReleaseBadgesSprints is not rendered", async () => {
            const open_sprints: Array<MilestoneData> = [];
            const trackers = [
                {
                    label: "Sprint1",
                },
            ];
            const wrapper = await getPersonalWidgetInstance(true, 0, open_sprints, trackers, true);

            expect(wrapper.find("[data-test=badge-sprint]").exists()).toBe(false);
        });

        it("When total_sprints is null, Then ReleaseBadgesSprints is not rendered", async () => {
            const open_sprints: Array<MilestoneData> = [];
            const trackers = [
                {
                    label: "Sprint1",
                },
            ];
            const wrapper = await getPersonalWidgetInstance(
                true,
                null,
                open_sprints,
                trackers,
                true,
            );

            expect(wrapper.find("[data-test=badge-sprint]").exists()).toBe(false);
        });

        it("When there are some open sprints, Then ReleaseBadgesSprints is rendered", async () => {
            const total_sprint = 10;
            const open_sprints: Array<MilestoneData> = [
                {
                    id: 10,
                } as MilestoneData,
                {
                    id: 11,
                } as MilestoneData,
            ];
            const trackers = [
                {
                    label: "Sprint1",
                },
            ];
            const wrapper = await getPersonalWidgetInstance(
                true,
                total_sprint,
                open_sprints,
                trackers,
                false,
            );

            expect(wrapper.find("[data-test=badge-sprint]").exists()).toBe(true);
        });

        it("When there is no tracker of sprint, Then ReleasesBasgesSprints is not rendered", async () => {
            const total_sprint = null;
            const wrapper = await getPersonalWidgetInstance(true, total_sprint, [], [], true);

            expect(wrapper.find("[data-test=badge-sprint]").exists()).toBe(false);
        });

        it("When the user can't see the tracker, Then ReleasesBasgesAllSprints is not rendered", async () => {
            const total_sprint = 10;
            const open_sprints: Array<MilestoneData> = [
                {
                    id: 10,
                } as MilestoneData,
            ];
            const trackers = [
                {
                    label: "Sprint1",
                },
            ];
            const wrapper = await getPersonalWidgetInstance(
                false,
                total_sprint,
                open_sprints,
                trackers,
                true,
            );

            expect(wrapper.find("[data-test=badge-sprint]").exists()).toBe(false);
        });
    });

    it("When the user clicked on sprints, Then a line is displayed", async () => {
        const total_sprint = 10;
        const open_sprints: Array<MilestoneData> = [];
        const trackers = [
            {
                label: "Sprint1",
            },
        ];
        const wrapper = await getPersonalWidgetInstance(
            true,
            total_sprint,
            open_sprints,
            trackers,
            true,
        );

        wrapper.setData({ open_sprints_details: true });
        expect(wrapper.find("[data-test=line-displayed]").exists()).toBe(true);
    });

    it("When the user clicked on sprints, Then ReleaseBadgesClosedSprints is rendered", async () => {
        const total_sprint = 10;
        const open_sprints: Array<MilestoneData> = [];
        const trackers = [
            {
                label: "Sprint1",
            },
        ];
        const wrapper = await getPersonalWidgetInstance(
            true,
            total_sprint,
            open_sprints,
            trackers,
            false,
        );

        expect(wrapper.findComponent(ReleaseBadgesClosedSprints).exists()).toBe(false);
        wrapper.setData({ open_sprints_details: true });
        await wrapper.vm.$nextTick();
        expect(wrapper.findComponent(ReleaseBadgesClosedSprints).exists()).toBe(true);
    });

    it("When sprints details is open, Then there is button to close sprint details", async () => {
        const total_sprint = 10;
        const open_sprints: Array<MilestoneData> = [];
        const trackers = [
            {
                label: "Sprint1",
            },
        ];
        const wrapper = await getPersonalWidgetInstance(
            true,
            total_sprint,
            open_sprints,
            trackers,
            true,
        );

        wrapper.setData({ open_sprints_details: true });
        expect(wrapper.find("[data-test=button-to-close]").exists()).toBe(true);

        wrapper.get("[data-test=button-to-close]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=button-to-close]").exists()).toBe(false);
    });

    it("When component is rendered and it's the first release, Then sprints details is open", async () => {
        const total_sprint = 10;
        const open_sprints: Array<MilestoneData> = [
            {
                id: 10,
            } as MilestoneData,
        ];
        const trackers = [
            {
                label: "Sprint1",
            },
        ];
        const wrapper = await getPersonalWidgetInstance(
            true,
            total_sprint,
            open_sprints,
            trackers,
            true,
        );

        expect(wrapper.findComponent(ReleaseBadgesOpenSprint).exists()).toBe(true);
    });

    it("When component is rendered and it's not the first release, Then sprints details is closed", async () => {
        const total_sprint = 10;
        const open_sprints: Array<MilestoneData> = [
            {
                id: 10,
            } as MilestoneData,
            {
                id: 11,
            } as MilestoneData,
        ];
        const trackers = [
            {
                label: "Sprint1",
            },
        ];
        const wrapper = await getPersonalWidgetInstance(
            true,
            total_sprint,
            open_sprints,
            trackers,
            false,
        );

        expect(wrapper.findComponent(ReleaseBadgesOpenSprint).exists()).toBe(false);
    });

    it("When sprints details is open, Then ReleaseBadgesOpenSprint is rendered", async () => {
        const total_sprint = 10;
        const open_sprints: Array<MilestoneData> = [
            {
                id: 10,
            } as MilestoneData,
            {
                id: 11,
            } as MilestoneData,
        ];
        const trackers = [
            {
                label: "Sprint1",
            },
        ];
        const wrapper = await getPersonalWidgetInstance(
            true,
            total_sprint,
            open_sprints,
            trackers,
            false,
        );

        expect(wrapper.findComponent(ReleaseBadgesOpenSprint).exists()).toBe(false);
        expect(wrapper.findComponent(ReleaseBadgesAllSprints).exists()).toBe(true);

        wrapper.setData({ open_sprints_details: true });
        await wrapper.vm.$nextTick();
        expect(wrapper.findComponent(ReleaseBadgesOpenSprint).exists()).toBe(true);
        expect(wrapper.findComponent(ReleaseBadgesAllSprints).exists()).toBe(false);
    });

    it("When there is only one sprint and no closed sprints and it's not the first release, Then sprints details is open", async () => {
        const total_sprint = 10;
        const open_sprints: Array<MilestoneData> = [
            {
                id: 10,
            } as MilestoneData,
        ];
        const trackers = [
            {
                label: "Sprint1",
            },
        ];
        const wrapper = await getPersonalWidgetInstance(
            true,
            total_sprint,
            open_sprints,
            trackers,
            true,
        );

        expect(wrapper.findComponent(ReleaseBadgesOpenSprint).exists()).toBe(true);
    });

    it("When there are only open sprint and no closed, Then ReleaseClosedSprints is not rendered", async () => {
        const total_sprint = 10;
        const open_sprints: Array<MilestoneData> = [
            {
                id: 10,
            } as MilestoneData,
            {
                id: 11,
            } as MilestoneData,
        ];
        const trackers = [
            {
                label: "Sprint1",
            },
        ];
        const wrapper = await getPersonalWidgetInstance(
            true,
            total_sprint,
            open_sprints,
            trackers,
            false,
        );

        expect(wrapper.findComponent(ReleaseBadgesOpenSprint).exists()).toBe(false);
        expect(wrapper.findComponent(ReleaseBadgesClosedSprints).exists()).toBe(false);
    });
});
