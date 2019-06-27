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
import ReleaseInformationDisplayer from "./ReleaseInformationDisplayer.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";
import Vue from "vue";
import GetTextPlugin from "vue-gettext";

const releaseData = {
    label: "mile",
    id: 2,
    planning: {
        id: 100
    }
};
const project_id = "102";

describe("ReleaseInformationDisplayer", () => {
    let store_options;
    let store;
    function getPersonalWidgetInstance(store_options) {
        store = createStoreMock(store_options);

        const component_options = {
            propsData: {
                releaseData
            },
            mocks: { $store: store }
        };

        Vue.use(GetTextPlugin, {
            translations: {},
            silent: true
        });

        return shallowMount(ReleaseInformationDisplayer, component_options);
    }
    beforeEach(() => {
        store_options = {
            state: {
                is_open: false,
                total_sprint: 0
            }
        };

        getPersonalWidgetInstance(store_options);
    });

    it("When the user toggle twice a project, the content widget is displayed first and hidden after", () => {
        const wrapper = getPersonalWidgetInstance(store_options);
        const toggle = wrapper.find("[data-test=project-release-toggle]");
        toggle.trigger("click");
        expect(wrapper.contains("[data-test=toggle_open]")).toBeTruthy();
        toggle.trigger("click");
        expect(wrapper.contains("[data-test=toggle_open]")).toBeFalsy();
    });

    it("When a release is toggle, Then a good link to top planning of the release is rendered", () => {
        store_options.state.project_id = project_id;

        const wrapper = getPersonalWidgetInstance(store_options);

        const toggle = wrapper.find("[data-test=project-release-toggle]");
        toggle.trigger("click");

        expect(wrapper.find("[data-test=planning-link]").attributes("href")).toEqual(
            "/plugins/agiledashboard/?group_id=" +
                encodeURIComponent(project_id) +
                "&planning_id=" +
                encodeURIComponent(releaseData.planning.id) +
                "&action=show&aid=" +
                encodeURIComponent(releaseData.id) +
                "&pane=planning-v2"
        );
    });
});
