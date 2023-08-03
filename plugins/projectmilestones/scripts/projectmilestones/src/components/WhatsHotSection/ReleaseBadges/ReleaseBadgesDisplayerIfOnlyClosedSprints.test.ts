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
import ReleaseBadgesDisplayerIfOnlyClosedSprints from "./ReleaseBadgesDisplayerIfOnlyClosedSprints.vue";
import type { MilestoneData } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";
import ReleaseOthersBadges from "./ReleaseOthersBadges.vue";
import ReleaseBadgesClosedSprints from "./ReleaseBadgesClosedSprints.vue";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";

let release_data: MilestoneData;
const component_options: ShallowMountOptions<ReleaseBadgesDisplayerIfOnlyClosedSprints> = {};

const project_id = 102;

describe("ReleaseBadgesDisplayerIfOnlyClosedSprints", () => {
    async function getPersonalWidgetInstance(
        user_can_view_sub_milestones_planning: boolean,
    ): Promise<Wrapper<ReleaseBadgesDisplayerIfOnlyClosedSprints>> {
        const useStore = defineStore("root", {
            state: () => ({
                project_id: project_id,
                user_can_view_sub_milestones_planning,
            }),
        });
        const pinia = createTestingPinia();
        useStore(pinia);
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseBadgesDisplayerIfOnlyClosedSprints, component_options);
    }

    beforeEach(() => {
        release_data = {
            id: 2,
        } as MilestoneData;

        component_options.propsData = { release_data };
    });

    describe("Display closed sprints and others badges", () => {
        it("When the component is rendered, Then ReleaseBadgesClosedSprints and ReleaseOthersBadges are rendered", async () => {
            const wrapper = await getPersonalWidgetInstance(true);

            expect(wrapper.findComponent(ReleaseBadgesClosedSprints).exists()).toBe(true);
            expect(wrapper.findComponent(ReleaseOthersBadges).exists()).toBe(true);
        });
        it("If the user can't see sprints' tracker, Then ReleaseBadgesClosedSprints is not rendered", async () => {
            const wrapper = await getPersonalWidgetInstance(false);

            expect(wrapper.findComponent(ReleaseBadgesClosedSprints).exists()).toBe(false);
            expect(wrapper.findComponent(ReleaseOthersBadges).exists()).toBe(true);
        });
    });
});
