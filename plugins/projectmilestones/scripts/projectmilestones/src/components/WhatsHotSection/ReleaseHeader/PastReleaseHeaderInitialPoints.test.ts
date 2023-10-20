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
import PastReleaseHeaderInitialPoints from "./PastReleaseHeaderInitialPoints.vue";
import type { MilestoneData } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";

let release_data: MilestoneData;
let component_options: ShallowMountOptions<PastReleaseHeaderInitialPoints>;

describe("PastReleaseHeaderInitialPoints", () => {
    async function getPersonalWidgetInstance(): Promise<Wrapper<PastReleaseHeaderInitialPoints>> {
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(PastReleaseHeaderInitialPoints, component_options);
    }

    beforeEach(() => {
        release_data = {
            label: "mile",
            initial_effort: 10,
        } as MilestoneData;

        component_options = {
            propsData: {
                release_data,
            },
        };
    });

    describe("Display initial effort", () => {
        it("When there is initial effort, Then it's displayed", async () => {
            const wrapper = await getPersonalWidgetInstance();
            expect(wrapper.get("[data-test=points-initial-value]").text()).toBe("10");
        });

        it("When there isn't initial effort, Then 0 displayed", async () => {
            release_data = {
                label: "mile",
                initial_effort: null,
            } as MilestoneData;

            component_options = {
                propsData: {
                    release_data,
                },
            };

            const wrapper = await getPersonalWidgetInstance();
            expect(wrapper.get("[data-test=points-initial-value]").text()).toBe("0");
        });
    });
});
