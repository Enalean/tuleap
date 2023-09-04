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
import PastReleaseHeaderTestsDisplayer from "./PastReleaseHeaderTestsDisplayer.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { MilestoneData, Pane, StoreOptions } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";

let release_data: MilestoneData;
let component_options: ShallowMountOptions<PastReleaseHeaderTestsDisplayer>;

describe("PastReleaseHeaderTestsDisplayer", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions,
    ): Promise<Wrapper<PastReleaseHeaderTestsDisplayer>> {
        store = createStoreMock(store_options);
        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(PastReleaseHeaderTestsDisplayer, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {},
        };

        release_data = {
            label: "mile",
            initial_effort: 10,
            resources: {
                additional_panes: [] as Pane[],
            },
        } as MilestoneData;

        component_options = {
            propsData: {
                release_data,
            },
        };
    });

    describe("Display number of test", () => {
        it("When testplan is disabled, Then the number is not displayed", async () => {
            const wrapper = await getPersonalWidgetInstance(store_options);
            expect(wrapper.find("[data-test=number-tests]").exists()).toBe(false);
        });

        it("When testplan is enabled but there are no test, Then 0 is displayed", async () => {
            release_data.resources.additional_panes = [
                {
                    icon_name: "fa-check",
                    identifier: "testplan",
                    title: "Tests",
                    uri: "testplan/project/2",
                },
            ];

            const wrapper = await getPersonalWidgetInstance(store_options);
            expect(wrapper.find("[data-test=number-tests]").text()).toBe("0");
        });

        it("When testplan is enabled but there are some tests, Then the number is displayed", async () => {
            release_data.resources.additional_panes = [
                {
                    icon_name: "fa-check",
                    identifier: "testplan",
                    title: "Tests",
                    uri: "testplan/project/2",
                },
            ];

            release_data.campaign = {
                nb_of_blocked: 10,
                nb_of_notrun: 5,
                nb_of_passed: 0,
                nb_of_failed: 4,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);
            expect(wrapper.find("[data-test=number-tests]").text()).toBe("19");
        });
    });
});
