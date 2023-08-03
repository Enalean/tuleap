/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import TestManagementDisplayer from "./TestManagementDisplayer.vue";
import type { MilestoneData } from "../../../../type";
import { createReleaseWidgetLocalVue } from "../../../../helpers/local-vue-for-test";
import type { DefaultData } from "vue/types/options";
import TestManagement from "./TestManagement.vue";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";

let release_data: MilestoneData;
const component_options: ShallowMountOptions<TestManagementDisplayer> = {};
const project_id = 100;

describe("TestManagementDisplayer", () => {
    async function getPersonalWidgetInstance(): Promise<Wrapper<TestManagementDisplayer>> {
        const useStore = defineStore("root", {
            state: () => ({
                label_tracker_planning: "Releases",
                project_id,
            }),
            actions: {
                getTestManagementCampaigns: jest.fn(),
            },
        });
        const pinia = createTestingPinia();
        useStore(pinia);
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(TestManagementDisplayer, component_options);
    }

    beforeEach(() => {
        release_data = {
            id: 2,
            planning: {
                id: "100",
            },
            resources: {
                additional_panes: [
                    {
                        icon_name: "fa-check",
                        identifier: "testplan",
                        title: "Tests",
                        uri: "testplan/project/2",
                    },
                ],
            },
            campaign: null,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };
    });

    it("When the component is rendered, Then there is a loader", async () => {
        component_options.data = (): DefaultData<TestManagementDisplayer> => {
            return {
                is_loading: true,
                error_message: null,
            };
        };

        const wrapper = await getPersonalWidgetInstance();
        wrapper.setData({ is_loading: true });

        expect(wrapper.find("[data-test=loading-data]").exists()).toBe(true);
    });

    it("When there is a rest error, Then the error is displayed", async () => {
        component_options.data = (): DefaultData<TestManagementDisplayer> => {
            return {
                is_loading: false,
                message_error_rest: "404 Error",
            };
        };

        const wrapper = await getPersonalWidgetInstance();

        expect(wrapper.find("[data-test=error-rest]").text()).toBe("404 Error");
    });

    it("When TTM plugin is activated and there is some tests, Then TestManagement component is rendered", async () => {
        component_options.data = (): DefaultData<TestManagementDisplayer> => {
            return {
                is_loading: false,
                error_message: null,
            };
        };

        release_data.campaign = {
            nb_of_blocked: 1,
            nb_of_passed: 2,
            nb_of_failed: 3,
            nb_of_notrun: 4,
        };

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance();
        expect(wrapper.findComponent(TestManagement).exists()).toBe(true);
    });

    it("When there is no tests, Then TestManagement component is not rendered", async () => {
        component_options.data = (): DefaultData<TestManagementDisplayer> => {
            return {
                is_loading: false,
                error_message: null,
            };
        };

        release_data.campaign = {
            nb_of_blocked: 0,
            nb_of_passed: 0,
            nb_of_failed: 0,
            nb_of_notrun: 0,
        };

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance();
        expect(wrapper.findComponent(TestManagement).exists()).toBe(false);
    });
});
