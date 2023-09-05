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

import type { ShallowMountOptions, Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import PastSection from "./PastSection.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { MilestoneData, StoreOptions } from "../../type";
import ReleaseDisplayer from "../WhatsHotSection/ReleaseDisplayer.vue";
import { createReleaseWidgetLocalVue } from "../../helpers/local-vue-for-test";

const project_id = 102;
const component_options: ShallowMountOptions<PastSection> = {};

async function getPersonalWidgetInstance(
    store_options: StoreOptions,
): Promise<Wrapper<PastSection>> {
    const store = createStoreMock(store_options);

    component_options.propsData = {
        label_tracker_planning: "sprint",
    };

    component_options.mocks = { $store: store };
    component_options.localVue = await createReleaseWidgetLocalVue();

    return shallowMount(PastSection, component_options);
}

describe("PastSection", () => {
    let store_options: StoreOptions;
    beforeEach(() => {
        store_options = {
            state: {
                is_loading: false,
                current_milestones: [],
                project_id: project_id,
                nb_past_releases: 4,
            },
            getters: {
                has_rest_error: false,
            },
        };

        getPersonalWidgetInstance(store_options);
    });

    it("Given user display widget, Then a good link to done releases of the project is rendered", async () => {
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.get("[data-test=past-releases-link]").attributes("href")).toContain(
            "/plugins/agiledashboard/?action=show-top&group_id=" +
                encodeURIComponent(project_id) +
                "&pane=topplanning-v2&load-all=1",
        );
    });

    it("When there is no last_milestone, then ReleaseDisplayer Component is not displayed", async () => {
        store_options.state.last_release = null;
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.findComponent(ReleaseDisplayer).exists()).toBe(false);
    });

    it("When there is one last_milestone, then ReleaseDisplayer Component is displayed", async () => {
        store_options.state.last_release = {
            id: 1,
        } as MilestoneData;
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.findComponent(ReleaseDisplayer).exists()).toBe(true);
    });
});
