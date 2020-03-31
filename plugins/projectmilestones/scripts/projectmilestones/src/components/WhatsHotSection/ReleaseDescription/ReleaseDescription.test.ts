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
import ReleaseDescription from "./ReleaseDescription.vue";
import { createStoreMock } from "../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { MilestoneData, Pane, StoreOptions, TrackerProjectLabel } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";
import ChartDisplayer from "./Chart/ChartDisplayer.vue";

let release_data: MilestoneData;
const component_options: ShallowMountOptions<ReleaseDescription> = {};
const project_id = 100;

describe("ReleaseDescription", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<ReleaseDescription>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseDescription, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {
                label_tracker_planning: "Releases",
            },
        };

        release_data = {
            id: 2,
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
                ],
                cardwall: {
                    uri: "/cardwall/",
                },
            },
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };
    });

    it("When there is a description, Then there is a tooltip to show the whole description", async () => {
        const description =
            "This is a big description, so I write some things, stuff, foo, bar. This is a big description, so I write some things, stuff, foo, bar.";

        release_data = {
            id: 2,
            description,
            resources: {
                burndown: null,
            },
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.get("[data-test=tooltip-description]").text()).toEqual(description);
    });

    it("When there is a burndown, Then the ChartDisplayer is rendered", async () => {
        release_data = {
            id: 2,
            resources: {
                burndown: {
                    uri: "/burndown",
                },
            },
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.contains(ChartDisplayer)).toBe(true);
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
                "&pane=planning-v2"
        );
    });

    it("When the user can't see the subplanning, Then he can't see the planning link", async () => {
        store_options.state.user_can_view_sub_milestones_planning = false;

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.contains("[data-test=planning-link]")).toBe(false);
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
        expect(wrapper.contains("[data-test=planning-link]")).toBe(false);
    });
});
