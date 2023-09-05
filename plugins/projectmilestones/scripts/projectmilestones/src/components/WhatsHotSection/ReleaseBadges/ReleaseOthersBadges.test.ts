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
import ReleaseOthersBadges from "./ReleaseOthersBadges.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { MilestoneData, Pane, StoreOptions, TrackerProjectLabel } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";

let release_data: MilestoneData & Required<Pick<MilestoneData, "planning">>;
const total_sprint = 10;
const initial_effort = 10;
const component_options: ShallowMountOptions<ReleaseOthersBadges> = {};

const project_id = 102;

describe("ReleaseOthersBadges", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions,
    ): Promise<Wrapper<ReleaseOthersBadges>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseOthersBadges, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {
                project_id: project_id,
            },
        };

        release_data = {
            id: 2,
            capacity: 10,
            total_sprint,
            initial_effort,
            planning: {
                id: "100",
            },
            resources: {
                milestones: {
                    accept: {
                        trackers: [
                            {
                                label: "Sprint1",
                            },
                        ],
                    },
                },
                additional_panes: [
                    {
                        icon_name: "fa-tlp-taskboard",
                        title: "Taskboard",
                        uri: "/taskboard/project/6",
                        identifier: "taskboard",
                    },
                    {
                        icon_name: "fa-check",
                        identifier: "testplan",
                        title: "Tests",
                        uri: "testplan/project/6",
                    },
                ],
                cardwall: {
                    uri: "/cardwall/",
                },
            },
        } as MilestoneData;

        component_options.propsData = { release_data };
    });

    describe("Display points of initial effort", () => {
        it("When there is an initial effort, Then the points of initial effort are displayed", async () => {
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.find("[data-test=initial-effort-not-empty]").exists()).toBe(true);
            expect(wrapper.find("[data-test=initial-effort-empty]").exists()).toBe(false);
        });

        it("When there is initial effort but null, Then the points of initial effort are 'N/A'", async () => {
            release_data = {
                id: 2,
                capacity: 10,
                total_sprint,
                initial_effort: null,
            } as MilestoneData;

            component_options.propsData = { release_data };
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.find("[data-test=initial-effort-not-empty]").exists()).toBe(false);
            expect(wrapper.find("[data-test=initial-effort-empty]").exists()).toBe(true);
        });
    });

    describe("Display points of capacity", () => {
        it("When there are points of capacity, Then the points of capacity are displayed", async () => {
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.find("[data-test=capacity-not-empty]").exists()).toBe(true);
            expect(wrapper.find("[data-test=capacity-empty]").exists()).toBe(false);
        });

        it("When there are points of capacity but null, Then the points of capacity are 'N/A'", async () => {
            release_data = {
                id: 2,
                capacity: null,
                total_sprint,
                initial_effort,
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.find("[data-test=capacity-not-empty]").exists()).toBe(false);
            expect(wrapper.find("[data-test=capacity-empty]").exists()).toBe(true);
        });

        it("Given initial effort is bigger than capacity, Then the initial effort badge has a warning style", async () => {
            release_data = {
                id: 2,
                capacity: 50,
                total_sprint,
                initial_effort: 100,
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.get("[data-test=initial_effort_badge]").classes()).toContain(
                "tlp-badge-warning",
            );
        });

        it("Given initial effort is smaller than capacity, Then the initial effort badge has primary style", async () => {
            release_data = {
                id: 2,
                capacity: 100,
                total_sprint,
                initial_effort: 50,
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.get("[data-test=initial_effort_badge]").classes()).toContain(
                "tlp-badge-primary",
            );
        });
    });

    it("Given user display widget, Then a good link to sprint planning is renderer", async () => {
        store_options.state.project_id = project_id;
        store_options.state.user_can_view_sub_milestones_planning = true;

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.get("[data-test=planning-link]").attributes("href")).toEqual(
            "/plugins/agiledashboard/?group_id=" +
                encodeURIComponent(project_id) +
                "&planning_id=" +
                encodeURIComponent(release_data.planning.id) +
                "&action=show&aid=" +
                encodeURIComponent(release_data.id) +
                "&pane=planning-v2",
        );
    });

    it("When the user can't see the subplanning, Then he can't see the planning link", async () => {
        store_options.state.user_can_view_sub_milestones_planning = false;

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.find("[data-test=planning-link]").exists()).toBe(false);
    });

    it("When there isn't sub-planning, Then there isn't any link to sub-planning", async () => {
        store_options.state.user_can_view_sub_milestones_planning = true;

        release_data = {
            id: 2,
            planning: {
                id: "100",
            },
            resources: {
                milestones: {
                    accept: {
                        trackers: [] as TrackerProjectLabel[],
                    },
                },
                additional_panes: [] as Pane[],
            },
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.find("[data-test=planning-link]").exists()).toBe(false);
    });
});
