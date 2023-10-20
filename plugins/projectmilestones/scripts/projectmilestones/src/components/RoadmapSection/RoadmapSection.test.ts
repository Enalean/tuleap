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
import RoadmapSection from "./RoadmapSection.vue";
import { createReleaseWidgetLocalVue } from "../../helpers/local-vue-for-test";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";

const project_id = 102;

async function getPersonalWidgetInstance(): Promise<Wrapper<RoadmapSection>> {
    const useStore = defineStore("root", {
        state: () => ({
            is_loading: false,
            current_milestones: [],
            project_id: project_id,
            nb_backlog_items: 2,
            nb_upcoming_releases: 1,
        }),
    });
    const pinia = createTestingPinia();
    useStore(pinia);

    const component_options = {
        localVue: await createReleaseWidgetLocalVue(),
        propsData: {
            label_tracker_planning: "sprint",
        },
    };

    return shallowMount(RoadmapSection, component_options);
}

describe("RoadmapSection", () => {
    it("Given user display widget, Then a good link to top planning of the project is rendered", async () => {
        const wrapper = await getPersonalWidgetInstance();

        expect(wrapper.element).toMatchSnapshot();
    });
});
