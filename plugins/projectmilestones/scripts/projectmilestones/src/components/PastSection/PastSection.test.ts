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
import PastSection from "./PastSection.vue";
import type { MilestoneData } from "../../type";
import ReleaseDisplayer from "../WhatsHotSection/ReleaseDisplayer.vue";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

const project_id = 102;

function getPersonalWidgetInstance(
    last_release: MilestoneData | null = null,
): VueWrapper<InstanceType<typeof PastSection>> {
    const useStore = defineStore("root", {
        state: () => ({
            project_id: project_id,
            last_release,
            nb_past_releases: 10,
        }),
    });
    const pinia = createTestingPinia();
    useStore(pinia);

    const component_options = {
        propsData: {
            label_tracker_planning: "sprint",
        },
        global: {
            ...getGlobalTestOptions(pinia),
        },
    };

    return shallowMount(PastSection, component_options);
}

describe("PastSection", () => {
    it("Given user display widget, Then a good link to done releases of the project is rendered", () => {
        const wrapper = getPersonalWidgetInstance();

        expect(wrapper.get("[data-test=past-releases-link]").attributes("href")).toContain(
            "/plugins/agiledashboard/?action=show-top&group_id=" +
                encodeURIComponent(project_id) +
                "&pane=topplanning-v2&load-all=1",
        );
    });

    it("When there is no last_milestone, then ReleaseDisplayer Component is not displayed", () => {
        const wrapper = getPersonalWidgetInstance(null);

        expect(wrapper.findComponent(ReleaseDisplayer).exists()).toBe(false);
    });

    it("When there is one last_milestone, then ReleaseDisplayer Component is displayed", () => {
        const last_release = {
            id: 1,
        } as MilestoneData;
        const wrapper = getPersonalWidgetInstance(last_release);

        expect(wrapper.findComponent(ReleaseDisplayer).exists()).toBe(true);
    });
});
