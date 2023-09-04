/*
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import { shallowMount } from "@vue/test-utils";
import { createPlanIterationsLocalVue } from "../../../helpers/local-vue-for-test";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

import PlannedIterationsSectionEmptyState from "./PlannedIterationsSectionEmptyState.vue";

import type { Wrapper } from "@vue/test-utils";
import type { IterationLabels } from "../../../store/configuration";

describe("PlannedIterationsSectionEmptyState", () => {
    async function getWrapper(
        iterations_labels: IterationLabels,
    ): Promise<Wrapper<PlannedIterationsSectionEmptyState>> {
        return shallowMount(PlannedIterationsSectionEmptyState, {
            localVue: await createPlanIterationsLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            iterations_labels,
                            program_increment: {
                                id: 666,
                                title: "Mating",
                            },
                            iteration_tracker_id: 101,
                        },
                    },
                }),
            },
        });
    }

    it.each([
        [{ label: "Guinea Pigs", sub_label: "g-pig" }, "g-pig"],
        [{ label: "", sub_label: "" }, "iteration"],
    ])(
        "should use the custom iteration sub_label in the text and button when it is defined",
        async (labels: IterationLabels, expected_naming: string) => {
            const wrapper = await getWrapper(labels);

            expect(wrapper.get("[data-test=planned-iterations-empty-state-text]").text()).toContain(
                expected_naming,
            );
            expect(wrapper.get("[data-test=create-first-iteration-button]").text()).toContain(
                expected_naming,
            );
        },
    );
});
