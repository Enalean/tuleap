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
import WhatsHotSection from "./WhatsHotSection.vue";
import type { MilestoneData } from "../../type";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

const project_id = 102;

function getPersonalWidgetInstance(
    current_milestones: Array<MilestoneData>,
): VueWrapper<InstanceType<typeof WhatsHotSection>> {
    const useStore = defineStore("root", {
        state: () => ({
            current_milestones,
        }),
    });
    const pinia = createTestingPinia();
    useStore(pinia);

    const component_options = {
        propsData: {
            project_id,
        },
        global: {
            ...getGlobalTestOptions(pinia),
        },
    };

    return shallowMount(WhatsHotSection, component_options);
}

describe("What'sHotSection", () => {
    it("When there are no current milestones, then ReleaseDisplayer Component is not allowed", () => {
        const wrapper = getPersonalWidgetInstance([]);

        expect(wrapper.find("[data-test=current-milestones-test]").exists()).toBe(false);
    });

    it("When there are some current_milestones, then ReleaseDisplayer Component is displayed", () => {
        const release1: MilestoneData = {
            label: "release_1",
            id: 1,
        } as MilestoneData;

        const release2: MilestoneData = {
            label: "release_2",
            id: 2,
        } as MilestoneData;

        const wrapper = getPersonalWidgetInstance([release1, release2]);

        expect(
            wrapper.find("[data-test=current-milestones-test-" + release1.label + "]").exists(),
        ).toBe(true);
        expect(
            wrapper.find("[data-test=current-milestones-test-" + release2.label + "]").exists(),
        ).toBe(true);
    });
});
