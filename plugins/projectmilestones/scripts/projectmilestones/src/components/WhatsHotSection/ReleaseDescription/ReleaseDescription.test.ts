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
import ReleaseDescription from "./ReleaseDescription.vue";
import type {
    MilestoneData,
    Pane,
    TrackerNumberArtifacts,
    TrackerProjectLabel,
} from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";
import ChartDisplayer from "./Chart/ChartDisplayer.vue";
import TestManagementDisplayer from "./TestManagement/TestManagementDisplayer.vue";
import ReleaseDescriptionBadgesTracker from "./ReleaseDescriptionBadgesTracker.vue";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";

let release_data: MilestoneData;
const component_options: ShallowMountOptions<ReleaseDescription> = {};

describe("ReleaseDescription", () => {
    async function getPersonalWidgetInstance(): Promise<Wrapper<ReleaseDescription>> {
        const useStore = defineStore("root", {
            state: () => ({
                label_tracker_planning: "Releases",
            }),
        });
        const pinia = createTestingPinia();
        useStore(pinia);
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseDescription, component_options);
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
                        identifier: "testplan",
                        title: "Tests",
                        uri: "/testplan/project/6",
                    },
                ],
                cardwall: {
                    uri: "/cardwall/",
                },
            },
            number_of_artifact_by_trackers: [] as TrackerNumberArtifacts[],
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };
    });

    it("When there is a burndown, Then the ChartDisplayer is rendered", async () => {
        release_data = {
            id: 2,
            resources: {
                burndown: {
                    uri: "/burndown",
                },
                additional_panes: [] as Pane[],
            },
            number_of_artifact_by_trackers: [] as TrackerNumberArtifacts[],
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance();
        expect(wrapper.findComponent(ChartDisplayer).exists()).toBe(true);
    });

    it("When plugin testplan is activated, Then TestManagementDisplayer is rendered", async () => {
        const wrapper = await getPersonalWidgetInstance();
        expect(wrapper.findComponent(TestManagementDisplayer).exists()).toBe(true);
    });

    it("When plugin testplan is disabled, Then TestManagementDisplayer is not rendered", async () => {
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
            number_of_artifact_by_trackers: [] as TrackerNumberArtifacts[],
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance();
        expect(wrapper.findComponent(TestManagementDisplayer).exists()).toBe(false);
    });

    it("When there are no artifacts, Then ReleaseDescriptionBadgesTracker is not rendered", async () => {
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
            number_of_artifact_by_trackers: [
                {
                    color_name: "blue-deep",
                    label: "Bug",
                    id: 15,
                    total_artifact: 0,
                },
            ] as TrackerNumberArtifacts[],
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance();
        expect(wrapper.findComponent(ReleaseDescriptionBadgesTracker).exists()).toBe(false);
    });
});
