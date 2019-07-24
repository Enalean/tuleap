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
import ReleaseBadges from "./ReleaseBadges.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";
import Vue from "vue";
import GetTextPlugin from "vue-gettext";

let releaseData = {};
const total_sprint = 10;
let initial_effort = 10;
let component_options = {};
const project_id = "102";

describe("ReleaseBadges", () => {
    let store_options;
    let store;

    function getPersonalWidgetInstance(store_options) {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };

        Vue.use(GetTextPlugin, {
            translations: {},
            silent: true
        });

        return shallowMount(ReleaseBadges, component_options);
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
            capacity: 10,
            total_sprint,
            initial_effort
        };

        component_options = {
            propsData: {
                releaseData
            }
        };

        getPersonalWidgetInstance(store_options);
    });

    it("When the component is displayed, Then a good link to top planning of the release is rendered", () => {
        store_options.state.project_id = project_id;

        const wrapper = getPersonalWidgetInstance(store_options);

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

    describe("Display points of initial effort", () => {
        it("When there is an initial effort, Then the points of initial effort are displayed", () => {
            const wrapper = getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=initial-effort-not-empty]")).toBeTruthy();
            expect(wrapper.contains("[data-test=initial-effort-empty]")).toBeFalsy();
        });

        it("When there is initial effort but null, Then the points of initial effort are 'N/A'", () => {
            initial_effort = null;

            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                capacity: 10,
                total_sprint,
                initial_effort
            };

            component_options = {
                propsData: {
                    releaseData
                }
            };
            const wrapper = getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=initial-effort-not-empty]")).toBeFalsy();
            expect(wrapper.contains("[data-test=initial-effort-empty]")).toBeTruthy();
        });

        it("When there isn't initial effort, Then the points of initial effort are 'N/A'", () => {
            initial_effort = null;

            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                capacity: 10,
                total_sprint
            };

            component_options = {
                propsData: {
                    releaseData
                }
            };
            const wrapper = getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=initial-effort-not-empty]")).toBeFalsy();
            expect(wrapper.contains("[data-test=initial-effort-empty]")).toBeTruthy();
        });
    });

    describe("Display points of capacity", () => {
        it("When there are points of capacity, Then the points of capacity are displayed", () => {
            const wrapper = getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=capacity-not-empty]")).toBeTruthy();
            expect(wrapper.contains("[data-test=capacity-empty]")).toBeFalsy();
        });

        it("When there are points of capacity but null, Then the points of capacity are 'N/A'", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                capacity: null,
                total_sprint,
                initial_effort
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=capacity-not-empty]")).toBeFalsy();
            expect(wrapper.contains("[data-test=capacity-empty]")).toBeTruthy();
        });

        it("When there aren't points of capacity, Then the points of capacity are 'N/A'", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                total_sprint,
                initial_effort
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=capacity-not-empty]")).toBeFalsy();
            expect(wrapper.contains("[data-test=capacity-empty]")).toBeTruthy();
        });
    });
});
