/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import App from "./App.vue";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import IterationsToBePlannedSection from "./Backlog/ToBePlanned/IterationsToBePlannedSection.vue";
import AppBreadcrumb from "./AppBreadcrumb.vue";
import PlannedIterationsSection from "./Backlog/Iteration/PlannedIterationsSection.vue";

describe("App", () => {
    function createWrapper(): VueWrapper<InstanceType<typeof App>> {
        const store_options = {
            modules: {
                configuration: {
                    namespaced: true,
                    state: {
                        program_increment: {
                            id: 666,
                            title: "Mating",
                            start_date: "Oct 01",
                            end_date: "Oct 31",
                        },
                    },
                },
            },
        };
        return shallowMount(App, { global: { ...getGlobalTestOptions(store_options) } });
    }

    it("Displays the app header and main sections", () => {
        const wrapper = createWrapper();
        const header_title = wrapper.find("[data-test=app-header-title]");

        expect(header_title.exists()).toBe(true);
        expect(header_title.text()).toContain("Mating");
        expect(header_title.text()).toContain("Oct 01 – Oct 31");

        expect(wrapper.findComponent(AppBreadcrumb).exists()).toBe(true);
        expect(wrapper.findComponent(IterationsToBePlannedSection).exists()).toBe(true);
        expect(wrapper.findComponent(PlannedIterationsSection).exists()).toBe(true);
    });
});
