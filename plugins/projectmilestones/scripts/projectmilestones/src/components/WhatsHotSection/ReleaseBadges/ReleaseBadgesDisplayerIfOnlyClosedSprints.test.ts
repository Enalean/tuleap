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
import ReleaseBadgesDisplayerIfOnlyClosedSprints from "./ReleaseBadgesDisplayerIfOnlyClosedSprints.vue";
import type { MilestoneData } from "../../../type";
import ReleaseOthersBadges from "./ReleaseOthersBadges.vue";
import ReleaseBadgesClosedSprints from "./ReleaseBadgesClosedSprints.vue";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

describe("ReleaseBadgesDisplayerIfOnlyClosedSprints", () => {
    function getPersonalWidgetInstance(
        user_can_view_sub_milestones_planning: boolean,
    ): VueWrapper<InstanceType<typeof ReleaseBadgesDisplayerIfOnlyClosedSprints>> {
        const useStore = defineStore("root", {
            state: () => ({
                user_can_view_sub_milestones_planning,
            }),
        });
        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(ReleaseBadgesDisplayerIfOnlyClosedSprints, {
            global: {
                ...getGlobalTestOptions(pinia),
            },
            propsData: {
                release_data: {
                    id: 2,
                } as MilestoneData,
            },
        });
    }

    describe("Display closed sprints and others badges", () => {
        it("When the component is rendered, Then ReleaseBadgesClosedSprints and ReleaseOthersBadges are rendered", () => {
            const wrapper = getPersonalWidgetInstance(true);

            expect(wrapper.findComponent(ReleaseBadgesClosedSprints).exists()).toBe(true);
            expect(wrapper.findComponent(ReleaseOthersBadges).exists()).toBe(true);
        });
        it("If the user can't see sprints' tracker, Then ReleaseBadgesClosedSprints is not rendered", () => {
            const wrapper = getPersonalWidgetInstance(false);

            expect(wrapper.findComponent(ReleaseBadgesClosedSprints).exists()).toBe(false);
            expect(wrapper.findComponent(ReleaseOthersBadges).exists()).toBe(true);
        });
    });
});
