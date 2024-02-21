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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { MilestoneData } from "../../../../type";
import TestManagement from "./TestManagement.vue";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

let release_data: MilestoneData;
const project_id = 100;

describe("TestManagement", () => {
    function getPersonalWidgetInstance(): VueWrapper<InstanceType<typeof TestManagement>> {
        const useStore = defineStore("root", {
            state: () => ({
                label_tracker_planning: "Releases",
                project_id,
            }),
        });
        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(TestManagement, {
            global: {
                ...getGlobalTestOptions(pinia),
            },
            propsData: {
                release_data,
                campaign: release_data.campaign,
            },
        });
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
                        uri: "/testplan/project/2",
                    },
                ],
            },
            campaign: {
                nb_of_blocked: 1,
                nb_of_notrun: 0,
                nb_of_passed: 10,
                nb_of_failed: 2,
            },
        } as MilestoneData;
    });

    it("When there is not campaign in release data, Then there is not lists", () => {
        release_data = {
            id: 2,
            planning: {
                id: "100",
            },
            resources: {
                additional_panes: [
                    {
                        title: "random",
                        identifier: "random",
                        icon_name: "fa-random",
                        uri: "/project/random",
                    },
                ],
            },
            campaign: null,
        } as MilestoneData;

        const wrapper = getPersonalWidgetInstance();
        expect(wrapper.find("[data-test=display-ttm]").exists()).toBe(false);
    });

    it("When component is renderer, Then there is a div element with id of release", () => {
        const wrapper = getPersonalWidgetInstance();
        expect(wrapper.element).toMatchSnapshot();
    });
});
