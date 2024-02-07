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
import ReleaseOthersBadges from "./ReleaseOthersBadges.vue";
import type { MilestoneData, Pane, TrackerProjectLabel } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";

let release_data: MilestoneData & Required<Pick<MilestoneData, "planning">>;
const total_sprint: number | null = 10;
let initial_effort: number | null = 10;
let capacity: number | null = 10;
const project_id = 102;
let resources = {
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
};

describe("ReleaseOthersBadges", () => {
    async function getPersonalWidgetInstance(
        user_can_view_sub_milestones_planning = false,
    ): Promise<Wrapper<Vue, Element>> {
        release_data = {
            id: 2,
            capacity,
            total_sprint,
            initial_effort,
            planning: {
                id: "100",
            },
            resources,
        } as MilestoneData;

        const useStore = defineStore("root", {
            state: () => ({
                project_id: project_id,
                user_can_view_sub_milestones_planning,
            }),
        });
        const pinia = createTestingPinia();
        useStore(pinia);

        const component_options = {
            localVue: await createReleaseWidgetLocalVue(),
            pinia,
            propsData: {
                release_data,
            },
        };

        return shallowMount(ReleaseOthersBadges, component_options);
    }

    describe("Display points of initial effort", () => {
        it("When there is an initial effort, Then the points of initial effort are displayed", async () => {
            const wrapper = await getPersonalWidgetInstance();

            expect(wrapper.find("[data-test=initial-effort-not-empty]").exists()).toBe(true);
            expect(wrapper.find("[data-test=initial-effort-empty]").exists()).toBe(false);
        });

        it("When there is initial effort but null, Then the points of initial effort are 'N/A'", async () => {
            initial_effort = null;
            const wrapper = await getPersonalWidgetInstance();

            expect(wrapper.find("[data-test=initial-effort-not-empty]").exists()).toBe(false);
            expect(wrapper.find("[data-test=initial-effort-empty]").exists()).toBe(true);
        });
    });

    describe("Display points of capacity", () => {
        it("When there are points of capacity, Then the points of capacity are displayed", async () => {
            const wrapper = await getPersonalWidgetInstance();

            expect(wrapper.find("[data-test=capacity-not-empty]").exists()).toBe(true);
            expect(wrapper.find("[data-test=capacity-empty]").exists()).toBe(false);
        });

        it("When there are points of capacity but null, Then the points of capacity are 'N/A'", async () => {
            capacity = null;
            const wrapper = await getPersonalWidgetInstance();

            expect(wrapper.find("[data-test=capacity-not-empty]").exists()).toBe(false);
            expect(wrapper.find("[data-test=capacity-empty]").exists()).toBe(true);
        });

        it("Given initial effort is bigger than capacity, Then the initial effort badge has a warning style", async () => {
            initial_effort = 100;
            capacity = 50;

            const wrapper = await getPersonalWidgetInstance();

            expect(wrapper.get("[data-test=initial_effort_badge]").classes()).toContain(
                "tlp-badge-warning",
            );
        });

        it("Given initial effort is smaller than capacity, Then the initial effort badge has primary style", async () => {
            capacity = 100;
            initial_effort = 50;

            const wrapper = await getPersonalWidgetInstance();

            expect(wrapper.get("[data-test=initial_effort_badge]").classes()).toContain(
                "tlp-badge-primary",
            );
        });
    });

    it("Given user display widget, Then a good link to sprint planning is renderer", async () => {
        const wrapper = await getPersonalWidgetInstance(true);
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
        const wrapper = await getPersonalWidgetInstance(false);
        expect(wrapper.find("[data-test=planning-link]").exists()).toBe(false);
    });

    it("When there isn't sub-planning, Then there isn't any link to sub-planning", async () => {
        resources = {
            milestones: {
                accept: {
                    trackers: [] as TrackerProjectLabel[],
                },
            },
            additional_panes: [] as Pane[],
            cardwall: {
                uri: "/cardwall/",
            },
        };

        const wrapper = await getPersonalWidgetInstance();
        expect(wrapper.find("[data-test=planning-link]").exists()).toBe(false);
    });
});
