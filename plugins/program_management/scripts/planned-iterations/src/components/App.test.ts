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

import type { Wrapper } from "@vue/test-utils";

import { shallowMount } from "@vue/test-utils";
import App from "./App.vue";
import { createPlanIterationsLocalVue } from "../helpers/local-vue-for-test";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import IterationsToBePlannedSection from "./IterationsToBePlannedSection.vue";
import PlannedIterationsSection from "./PlannedIterationsSection.vue";
import Breadcrumb from "./Breadcrumb.vue";

describe("App", () => {
    async function createWrapper(): Promise<Wrapper<App>> {
        return shallowMount(App, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        program_increment: {
                            id: 666,
                            title: "Mating",
                            start_date: "Oct 01",
                            end_date: "Oct 31",
                        },
                    },
                }),
            },
            localVue: await createPlanIterationsLocalVue(),
        });
    }

    it("Displays the app header and main sections", async () => {
        const wrapper = await createWrapper();
        const header_title = wrapper.find("[data-test=app-header-title]");

        expect(header_title.exists()).toBe(true);
        expect(header_title.text().includes("Mating")).toBe(true);
        expect(header_title.text().includes("Oct 01 – Oct 31")).toBe(true);

        expect(wrapper.findComponent(Breadcrumb).exists()).toBe(true);
        expect(wrapper.findComponent(IterationsToBePlannedSection).exists()).toBe(true);
        expect(wrapper.findComponent(PlannedIterationsSection).exists()).toBe(true);
    });
});
