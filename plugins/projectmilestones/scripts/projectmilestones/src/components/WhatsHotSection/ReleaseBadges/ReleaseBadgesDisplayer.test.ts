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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ReleaseBadgesDisplayer from "./ReleaseBadgesDisplayer.vue";
import type { MilestoneData } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";
import ReleaseBadgesDisplayerIfOpenSprints from "./ReleaseBadgesDisplayerIfOpenSprints.vue";
import ReleaseBadgesDisplayerIfOnlyClosedSprints from "./ReleaseBadgesDisplayerIfOnlyClosedSprints.vue";

describe("ReleaseBadgesDisplayer", () => {
    async function getPersonalWidgetInstance(
        release_data: MilestoneData,
    ): Promise<Wrapper<Vue, Element>> {
        return shallowMount(ReleaseBadgesDisplayer, {
            localVue: await createReleaseWidgetLocalVue(),
            propsData: { release_data, isOpen: true, isPastRelease: false },
        });
    }

    describe("Display number of sprint", () => {
        it("When there are not open sprints, Then ReleaseBadgesDisplayerIfOpenSprints is not rendered", async () => {
            const release_data = {
                id: 2,
                total_sprint: 0,
                open_sprints: [] as MilestoneData[],
            } as MilestoneData;

            const wrapper = await getPersonalWidgetInstance(release_data);

            expect(wrapper.findComponent(ReleaseBadgesDisplayerIfOpenSprints).exists()).toBe(false);
            expect(wrapper.findComponent(ReleaseBadgesDisplayerIfOnlyClosedSprints).exists()).toBe(
                true,
            );
        });

        it("When total_sprints is null, Then ReleaseBadgesDisplayerIfOpenSprints is not rendered", async () => {
            const release_data = {
                id: 2,
                total_sprint: null,
            } as MilestoneData;

            const wrapper = await getPersonalWidgetInstance(release_data);

            expect(wrapper.findComponent(ReleaseBadgesDisplayerIfOpenSprints).exists()).toBe(false);
            expect(wrapper.findComponent(ReleaseBadgesDisplayerIfOnlyClosedSprints).exists()).toBe(
                true,
            );
        });

        it("When there are some open sprints, Then ReleaseBadgesDisplayerIfOpenSprints is rendered", async () => {
            const release_data = {
                id: 2,
                total_sprint: 10,
                open_sprints: [
                    {
                        id: 10,
                    } as MilestoneData,
                ],
            } as MilestoneData;

            const wrapper = await getPersonalWidgetInstance(release_data);

            expect(wrapper.findComponent(ReleaseBadgesDisplayerIfOpenSprints).exists()).toBe(true);
            expect(wrapper.findComponent(ReleaseBadgesDisplayerIfOnlyClosedSprints).exists()).toBe(
                false,
            );
        });
    });
});
