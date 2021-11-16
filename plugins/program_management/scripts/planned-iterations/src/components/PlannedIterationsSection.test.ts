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
import PlannedIterationsSection from "./PlannedIterationsSection.vue";
import { createPlanIterationsLocalVue } from "../helpers/local-vue-for-test";
import type { IterationLabels } from "../type";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";

describe("PlannedIterationsSection", () => {
    async function getWrapper(
        iterations_labels: IterationLabels
    ): Promise<Wrapper<PlannedIterationsSection>> {
        return shallowMount(PlannedIterationsSection, {
            localVue: await createPlanIterationsLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        iterations_labels,
                    },
                }),
            },
        });
    }

    describe("Custom iterations labels", () => {
        it("should display the custom iterations label and sub-label when there are configured", async () => {
            const wrapper = await getWrapper({
                label: "Guinea Pigs",
                sub_label: "g-pig",
            });

            expect(wrapper.get("[data-test=planned-iterations-section-title]").text()).toEqual(
                "Guinea Pigs"
            );
            expect(wrapper.get("[data-test=planned-iterations-empty-state-text]").text()).toEqual(
                "There is no g-pig yet."
            );
        });

        it("should display Iterations/iteration by default", async () => {
            const wrapper = await getWrapper({
                label: "",
                sub_label: "",
            });

            expect(wrapper.get("[data-test=planned-iterations-section-title]").text()).toEqual(
                "Iterations"
            );
            expect(wrapper.get("[data-test=planned-iterations-empty-state-text]").text()).toEqual(
                "There is no iteration yet."
            );
        });
    });
});
