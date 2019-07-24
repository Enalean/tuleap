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

import { shallowMount } from "@vue/test-utils";
import RoadmapSection from "./RoadmapSection.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";
import Vue from "vue";
import GetTextPlugin from "vue-gettext";

const project_id = "102";
function getPersonalWidgetInstance(store_options) {
    const store = createStoreMock(store_options);
    const component_options = {
        mocks: { $store: store }
    };
    Vue.use(GetTextPlugin, {
        translations: {},
        silent: true
    });

    return shallowMount(RoadmapSection, component_options);
}

describe("RoadmapSection", () => {
    let store_options;
    beforeEach(() => {
        store_options = {
            state: {
                is_loading: false,
                current_milestones: []
            },
            getters: {
                has_rest_error: false
            }
        };

        getPersonalWidgetInstance(store_options);
    });

    it("Given user display widget, Then a good link to top planning of the project is rendered", () => {
        store_options.state.project_id = project_id;

        const wrapper = getPersonalWidgetInstance(store_options);

        expect(wrapper.find("[data-test=backlog-link]").attributes("href")).toContain(
            "/plugins/agiledashboard/?action=show-top&group_id=" +
                encodeURIComponent(project_id) +
                "&pane=topplanning-v2"
        );
    });
});
