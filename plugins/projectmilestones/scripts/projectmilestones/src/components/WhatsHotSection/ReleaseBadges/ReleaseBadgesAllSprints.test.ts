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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ReleaseBadgesAllSprints from "./ReleaseBadgesAllSprints.vue";
import type { MilestoneData, TrackerProjectLabel } from "../../../type";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

const total_sprint = 10;
const initial_effort = 10;

describe("ReleaseBadgesAllSprints", () => {
    function getPersonalWidgetInstance(
        user_can_view_sub_milestones_planning: boolean,
        trackers: Array<TrackerProjectLabel>,
    ): VueWrapper<InstanceType<typeof ReleaseBadgesAllSprints>> {
        const release_data = {
            id: 2,
            total_sprint,
            initial_effort,
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

        return shallowMount(ReleaseBadgesAllSprints, {
            global: {
                ...getGlobalTestOptions(pinia),
            },
            propsData: { release_data, isPastRelease: false },
        });
    }

    describe("Display number of sprint", () => {
        it("When there is a tracker, Then number of sprint is displayed", () => {
            const trackers = [
                {
                    label: "Sprint1",
                },
            ];
            const wrapper = getPersonalWidgetInstance(true, trackers);

            expect(wrapper.get("[data-test=badge-sprint]").text()).toBe("10 Sprint1");
        });

        it("When there isn't tracker, Then there is no link", () => {
            const wrapper = getPersonalWidgetInstance(true, []);

            expect(wrapper.find("[data-test=badge-sprint]").exists()).toBe(false);
        });

        it("When the user can't see the tracker, Then number of sprint is not displayed", () => {
            const wrapper = getPersonalWidgetInstance(false, []);

            expect(wrapper.find("[data-test=badge-sprint]").exists()).toBe(false);
        });
    });
});
