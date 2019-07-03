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

let releaseData = {};
let component_options = {};
const project_id = "102";

describe("ReleaseInformationDisplayer", () => {
    let store_options;
    let store;
    function getPersonalWidgetInstance(store_options) {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };

        Vue.use(GetTextPlugin, {
            translations: {},
            silent: true
        });

        return shallowMount(ReleaseInformationDisplayer, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {}
        };

        releaseData = {
            label: "mile",
            id: 2,
            planning: {
                id: 100
            },
            start_date: Date("2017-01-22T13:42:08+02:00")
        };

        component_options = {
            propsData: {
                releaseData
            },
            data() {
                return {
                    is_open: false,
                    total_sprint: null
                };
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

    describe("Display arrow of date", () => {
        it("When there is a start date of a release, Then an arrow is displayed", () => {
            const wrapper = getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=display-arrow]")).toBeTruthy();
        });

        it("When there aren't a start date of a release, Then there isn't an arrow", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=display-arrow]")).toBeFalsy();
        });
    });
});
