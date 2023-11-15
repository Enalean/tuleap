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

import type { Wrapper } from "@vue/test-utils";
import { mount } from "@vue/test-utils";
import PastReleaseHeaderInitialPoints from "./PastReleaseHeaderInitialPoints.vue";
import type { MilestoneData } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";

describe("PastReleaseHeaderInitialPoints", () => {
    async function getPersonalWidgetInstance(
        initial_effort: number | null,
    ): Promise<Wrapper<Vue, Element>> {
        const release_data = {
            label: "mile",
            initial_effort,
        } as MilestoneData;

        const component_options = {
            propsData: {
                release_data,
            },
            localVue: await createReleaseWidgetLocalVue(),
        };
        return mount(PastReleaseHeaderInitialPoints, component_options);
    }

    describe("Display initial effort", () => {
        it("When there is initial effort, Then it's displayed", async () => {
            const wrapper = await getPersonalWidgetInstance(10);
            expect(wrapper.get("[data-test=points-initial-value]").text()).toBe("10");
        });

        it("When there isn't initial effort, Then 0 displayed", async () => {
            const wrapper = await getPersonalWidgetInstance(null);
            expect(wrapper.get("[data-test=points-initial-value]").text()).toBe("0");
        });
    });
});
