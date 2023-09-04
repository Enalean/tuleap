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
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { MilestoneData, StoreOptions } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";
import ReleaseOthersBadges from "./ReleaseOthersBadges.vue";
import ReleaseBadgesClosedSprints from "./ReleaseBadgesClosedSprints.vue";

let release_data: MilestoneData;
const component_options: ShallowMountOptions<ReleaseBadgesDisplayerIfOnlyClosedSprints> = {};

const project_id = 102;

describe("ReleaseBadgesDisplayerIfOnlyClosedSprints", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions,
    ): Promise<Wrapper<ReleaseBadgesDisplayerIfOnlyClosedSprints>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseBadgesDisplayerIfOnlyClosedSprints, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {
                project_id: project_id,
            },
        };

        release_data = {
            id: 2,
        } as MilestoneData;

        component_options.propsData = { release_data };
    });

    describe("Display closed sprints and others badges", () => {
        it("When the component is rendered, Then ReleaseBadgesClosedSprints and ReleaseOthersBadges are rendered", async () => {
            store_options.state.user_can_view_sub_milestones_planning = true;
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.findComponent(ReleaseBadgesClosedSprints).exists()).toBe(true);
            expect(wrapper.findComponent(ReleaseOthersBadges).exists()).toBe(true);
        });
        it("If the user can't see sprints' tracker, Then ReleaseBadgesClosedSprints is not rendered", async () => {
            store_options.state.user_can_view_sub_milestones_planning = false;
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.findComponent(ReleaseBadgesClosedSprints).exists()).toBe(false);
            expect(wrapper.findComponent(ReleaseOthersBadges).exists()).toBe(true);
        });
    });
});
