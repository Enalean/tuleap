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
import ReleaseButtonsDescription from "./ReleaseButtonsDescription.vue";
import type { MilestoneData, Pane } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";

let release_data: MilestoneData & Required<Pick<MilestoneData, "planning">>;
const component_options: ShallowMountOptions<ReleaseButtonsDescription> = {};
const project_id = 102;

describe("ReleaseButtonsDescription", () => {
    async function getPersonalWidgetInstance(): Promise<Wrapper<ReleaseButtonsDescription>> {
        const useStore = defineStore("root", {
            state: () => ({
                project_id,
            }),
        });
        const pinia = createTestingPinia();
        useStore(pinia);
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseButtonsDescription, component_options);
    }

    beforeEach(() => {
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
                    {
                        icon_name: "fa-check",
                        title: "Tests",
                        uri: "/testplan/project/6",
                        identifier: "testplan",
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

    it("Given user display widget, Then a good link to TestPlan is renderer", async () => {
        const wrapper = await getPersonalWidgetInstance();
        const ttm_element = wrapper.get("[data-test=pane-link-testplan]");
        expect(ttm_element.attributes("href")).toBe("/testplan/project/6");
        expect(ttm_element.attributes("data-tlp-tooltip")).toBe("Tests");
        expect(wrapper.get("[data-test=pane-icon-testplan]").classes()).toContain("fa-check");
    });

    it("Given user display widget, Then a good link to taskboard is renderer", async () => {
        const wrapper = await getPersonalWidgetInstance();
        const taskboard_element = wrapper.get("[data-test=pane-link-taskboard]");
        expect(taskboard_element.attributes("href")).toBe("/taskboard/project/6");
        expect(taskboard_element.attributes("data-tlp-tooltip")).toBe("Taskboard");
        expect(wrapper.get("[data-test=pane-icon-taskboard]").classes()).toContain(
            "fa-tlp-taskboard",
        );
    });

    it("Given user display widget, Then a good link to overview is renderer", async () => {
        const wrapper = await getPersonalWidgetInstance();
        expect(wrapper.get("[data-test=overview-link]").attributes("href")).toEqual(
            "/plugins/agiledashboard/?group_id=" +
                encodeURIComponent(project_id) +
                "&planning_id=" +
                encodeURIComponent(release_data.planning.id) +
                "&action=show&aid=" +
                encodeURIComponent(release_data.id) +
                "&pane=details",
        );
    });

    it("Given user display widget, Then a good link to cardwall is renderer", async () => {
        const wrapper = await getPersonalWidgetInstance();
        expect(wrapper.get("[data-test=cardwall-link]").attributes("href")).toEqual(
            "/plugins/agiledashboard/?group_id=" +
                encodeURIComponent(project_id) +
                "&planning_id=" +
                encodeURIComponent(release_data.planning.id) +
                "&action=show&aid=" +
                encodeURIComponent(release_data.id) +
                "&pane=cardwall",
        );
    });

    it("When there isn't taskboard, Then there isn't any link to taskboard", async () => {
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
                        title: "random",
                        identifier: "random",
                        icon_name: "fa-random",
                        uri: "/project/random",
                    },
                ],
                cardwall: null,
            },
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance();
        expect(wrapper.find("[data-test=taskboard-link]").exists()).toBe(false);
    });

    it("When there isn't cardwall in resources, Then there isn't any link to cardwall", async () => {
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
                additional_panes: [] as Pane[],
                cardwall: null,
            },
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance();
        expect(wrapper.find("[data-test=cardwall-link]").exists()).toBe(false);
    });
});
