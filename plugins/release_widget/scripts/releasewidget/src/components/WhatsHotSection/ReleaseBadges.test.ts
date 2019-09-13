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

import { shallowMount, ShallowMountOptions, Wrapper } from "@vue/test-utils";
import ReleaseBadges from "./ReleaseBadges.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper-jest";
import Vue from "vue";
import { MilestoneData, StoreOptions } from "../../type";
import { initVueGettext } from "../../../../../../../src/www/scripts/tuleap/gettext/vue-gettext-init";

let releaseData: MilestoneData & Required<Pick<MilestoneData, "planning">>;
const total_sprint = 10;
const initial_effort = 10;
const component_options: ShallowMountOptions<ReleaseBadges> = {};

const project_id = 102;

describe("ReleaseBadges", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<ReleaseBadges>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };

        await initVueGettext(Vue, () => {
            throw new Error("Fallback to default");
        });

        return shallowMount(ReleaseBadges, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {
                project_id: project_id
            }
        };

        releaseData = {
            label: "mile",
            id: 2,
            planning: {
                id: "100"
            },
            capacity: 10,
            total_sprint,
            initial_effort,
            number_of_artifact_by_trackers: []
        };

        component_options.propsData = { releaseData };
    });

    it("When the component is displayed, Then a good link to top planning of the release is rendered", async () => {
        const wrapper = await getPersonalWidgetInstance(store_options);

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
        it("When there is an initial effort, Then the points of initial effort are displayed", async () => {
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=initial-effort-not-empty]")).toBeTruthy();
            expect(wrapper.contains("[data-test=initial-effort-empty]")).toBeFalsy();
        });

        it("When there is initial effort but null, Then the points of initial effort are 'N/A'", async () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                capacity: 10,
                total_sprint,
                initial_effort: null,
                number_of_artifact_by_trackers: []
            };

            component_options.propsData = { releaseData };
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=initial-effort-not-empty]")).toBeFalsy();
            expect(wrapper.contains("[data-test=initial-effort-empty]")).toBeTruthy();
        });

        it("When there isn't initial effort, Then the points of initial effort are 'N/A'", async () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                capacity: null,
                total_sprint,
                number_of_artifact_by_trackers: []
            };

            component_options.propsData = {
                releaseData
            };
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=initial-effort-not-empty]")).toBeFalsy();
            expect(wrapper.contains("[data-test=initial-effort-empty]")).toBeTruthy();
        });
    });

    describe("Display points of capacity", () => {
        it("When there are points of capacity, Then the points of capacity are displayed", async () => {
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=capacity-not-empty]")).toBeTruthy();
            expect(wrapper.contains("[data-test=capacity-empty]")).toBeFalsy();
        });

        it("When there are points of capacity but null, Then the points of capacity are 'N/A'", async () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                capacity: null,
                total_sprint,
                initial_effort,
                number_of_artifact_by_trackers: []
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=capacity-not-empty]")).toBeFalsy();
            expect(wrapper.contains("[data-test=capacity-empty]")).toBeTruthy();
        });

        it("When there aren't points of capacity, Then the points of capacity are 'N/A'", async () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                total_sprint,
                initial_effort,
                number_of_artifact_by_trackers: []
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=capacity-not-empty]")).toBeFalsy();
            expect(wrapper.contains("[data-test=capacity-empty]")).toBeTruthy();
        });
    });
});
