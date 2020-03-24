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

import { shallowMount, ShallowMountOptions, Wrapper } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { MilestoneData, StoreOptions } from "../../../../type";
import { createReleaseWidgetLocalVue } from "../../../../helpers/local-vue-for-test";
import { DefaultData } from "vue/types/options";
import TestManagement from "./TestManagement.vue";

let release_data: MilestoneData;
const component_options: ShallowMountOptions<TestManagement> = {};
const project_id = 100;

describe("TestManagement", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<TestManagement>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(TestManagement, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {
                label_tracker_planning: "Releases",
                project_id,
                project_milestone_activate_ttm: true,
            },
        };

        release_data = {
            id: 2,
            planning: {
                id: "100",
            },
            resources: {
                additional_panes: [
                    {
                        icon_name: "fa-external-link",
                        identifier: "testmgmt",
                        title: "Test Campaigns",
                        uri: "/plugin/testmanagement",
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

        component_options.propsData = {
            release_data,
        };
    });

    it("When the component is rendered, Then there is a list", async () => {
        component_options.data = (): DefaultData<TestManagement> => {
            return {
                is_loading: false,
                error_message: null,
            };
        };

        store_options.state.project_milestone_activate_ttm = true;

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.contains("[data-test=nb-test-failed]")).toBe(true);
        expect(wrapper.contains("[data-test=nb-test-blocked]")).toBe(true);
        expect(wrapper.contains("[data-test=nb-test-notrun]")).toBe(true);
        expect(wrapper.contains("[data-test=nb-test-passed]")).toBe(true);
    });

    it("When the project has not activated project_milestone_activate_ttm, Then there is not lists", async () => {
        component_options.data = (): DefaultData<TestManagement> => {
            return {
                is_loading: false,
                error_message: null,
            };
        };

        store_options.state.project_milestone_activate_ttm = false;

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.contains("[data-test=nb-test-failed]")).toBe(false);
        expect(wrapper.contains("[data-test=nb-test-blocked]")).toBe(false);
        expect(wrapper.contains("[data-test=nb-test-notrun]")).toBe(false);
        expect(wrapper.contains("[data-test=nb-test-passed]")).toBe(false);
    });

    it("When there is not campagin in release data, Then there is not lists", async () => {
        component_options.data = (): DefaultData<TestManagement> => {
            return {
                is_loading: false,
                error_message: null,
            };
        };

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

        component_options.propsData = {
            release_data,
        };

        store_options.state.project_milestone_activate_ttm = true;

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.contains("[data-test=nb-test-failed]")).toBe(false);
        expect(wrapper.contains("[data-test=nb-test-blocked]")).toBe(false);
        expect(wrapper.contains("[data-test=nb-test-notrun]")).toBe(false);
        expect(wrapper.contains("[data-test=nb-test-passed]")).toBe(false);
    });
});
