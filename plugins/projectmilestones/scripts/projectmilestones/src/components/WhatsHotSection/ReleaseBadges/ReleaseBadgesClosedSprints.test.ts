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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ReleaseBadgesClosedSprints from "./ReleaseBadgesClosedSprints.vue";
import type { MilestoneData, TrackerProjectLabel } from "../../../type";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

const total_sprint = 10;

describe("ReleaseBadgesClosedSprints", () => {
    function getPersonalWidgetInstance(
        user_can_view_sub_milestones_planning: boolean,
        release_data: MilestoneData,
    ): VueWrapper<InstanceType<typeof ReleaseBadgesClosedSprints>> {
        const useStore = defineStore("root", {
            state: () => ({
                user_can_view_sub_milestones_planning,
            }),
        });
        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(ReleaseBadgesClosedSprints, {
            global: {
                ...getGlobalTestOptions(pinia),
            },
            propsData: { release_data },
        });
    }

    describe("Display total of closed sprints", () => {
        it("When there are some closed sprints, Then the total is displayed", () => {
            const release_data = {
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

            const wrapper = getPersonalWidgetInstance(true, release_data);

            expect(wrapper.find("[data-test=total-closed-sprints]").exists()).toBe(true);
        });

        it("When the total of closed sprints is null, Then the total is not displayed", () => {
            const release_data = {
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

            const wrapper = getPersonalWidgetInstance(true, release_data);

            expect(wrapper.find("[data-test=total-closed-sprints]").exists()).toBe(false);
        });

        it("When the total of closed sprints is 0, Then the total is displayed", () => {
            const release_data = {
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

            const wrapper = getPersonalWidgetInstance(true, release_data);

            expect(wrapper.find("[data-test=total-closed-sprints]").exists()).toBe(true);
        });

        it("When there is no trackers of sprints, Then the total is not displayed", () => {
            const release_data = {
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

            const wrapper = getPersonalWidgetInstance(true, release_data);

            expect(wrapper.find("[data-test=total-closed-sprints]").exists()).toBe(false);
        });

        it("When the user can't see the tracker's label, Then the total is not displayed", () => {
            const release_data = {
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

            const wrapper = getPersonalWidgetInstance(false, release_data);

            expect(wrapper.find("[data-test=total-closed-sprints]").exists()).toBe(false);
        });
    });
});
