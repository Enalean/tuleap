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
import PastReleaseHeaderTestsDisplayer from "./PastReleaseHeaderTestsDisplayer.vue";
import type { MilestoneData, Pane, TestManagementCampaign } from "../../../type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

describe("PastReleaseHeaderTestsDisplayer", () => {
    function getPersonalWidgetInstance(
        additional_panes: Array<Pane>,
        campaign: TestManagementCampaign | null,
    ): VueWrapper<InstanceType<typeof PastReleaseHeaderTestsDisplayer>> {
        const release_data = {
            label: "mile",
            initial_effort: 10,
            resources: {
                additional_panes,
            },
            campaign,
        } as MilestoneData;

        const component_options = {
            propsData: {
                release_data,
            },
            global: {
                ...getGlobalTestOptions(),
            },
        };

        return shallowMount(PastReleaseHeaderTestsDisplayer, component_options);
    }

    describe("Display number of test", () => {
        it("When testplan is disabled, Then the number is not displayed", () => {
            const wrapper = getPersonalWidgetInstance([], null);
            expect(wrapper.find("[data-test=number-tests]").exists()).toBe(false);
        });

        it("When testplan is enabled but there are no test, Then 0 is displayed", () => {
            const additional_panes = [
                {
                    icon_name: "fa-check",
                    identifier: "testplan",
                    title: "Tests",
                    uri: "testplan/project/2",
                },
            ];

            const wrapper = getPersonalWidgetInstance(additional_panes, null);
            expect(wrapper.find("[data-test=number-tests]").text()).toBe("0");
        });

        it("When testplan is enabled but there are some tests, Then the number is displayed", () => {
            const additional_panes = [
                {
                    icon_name: "fa-check",
                    identifier: "testplan",
                    title: "Tests",
                    uri: "testplan/project/2",
                },
            ];

            const campaign = {
                nb_of_blocked: 10,
                nb_of_notrun: 5,
                nb_of_passed: 0,
                nb_of_failed: 4,
            };

            const wrapper = getPersonalWidgetInstance(additional_panes, campaign);
            expect(wrapper.find("[data-test=number-tests]").text()).toBe("19");
        });
    });
});
