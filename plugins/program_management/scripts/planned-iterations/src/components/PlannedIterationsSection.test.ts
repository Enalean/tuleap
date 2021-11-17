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

import Vue from "vue";
import { shallowMount } from "@vue/test-utils";
import { createPlanIterationsLocalVue } from "../helpers/local-vue-for-test";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import * as retriever from "../helpers/increment-iterations-retriever";
import PlannedIterationsSection from "./PlannedIterationsSection.vue";
import IterationCard from "./IterationCard.vue";
import BacklogElementSkeleton from "./BacklogElementSkeleton.vue";

import type { Wrapper } from "@vue/test-utils";
import type { IterationLabels } from "../type";

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
                        program_increment: {
                            id: 666,
                            title: "Mating",
                        },
                    },
                }),
            },
        });
    }

    describe("Custom iterations labels", () => {
        beforeEach(() => {
            jest.spyOn(retriever, "getIncrementIterations").mockResolvedValue([]);
        });

        it("should display the custom iterations label and sub-label when there are configured", async () => {
            const wrapper = await getWrapper({
                label: "Guinea Pigs",
                sub_label: "g-pig",
            });

            await Vue.nextTick();
            await Vue.nextTick();

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

            await Vue.nextTick();
            await Vue.nextTick();

            expect(wrapper.get("[data-test=planned-iterations-section-title]").text()).toEqual(
                "Iterations"
            );
            expect(wrapper.get("[data-test=planned-iterations-empty-state-text]").text()).toEqual(
                "There is no iteration yet."
            );
        });
    });

    describe("Iterations display", () => {
        it("should display its placeholder when there is no iteration in the increment", async () => {
            jest.spyOn(retriever, "getIncrementIterations").mockResolvedValue([]);

            const wrapper = await getWrapper({ label: "", sub_label: "" });

            expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(true);

            await Vue.nextTick();
            await Vue.nextTick();

            expect(wrapper.find("[data-test=planned-iterations-empty-state]").exists()).toBe(true);
            expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(false);
        });

        it("should fetch iterations and display them", async () => {
            jest.spyOn(retriever, "getIncrementIterations").mockResolvedValue([
                {
                    id: 1279,
                    title: "Iteration 1",
                    status: "On going",
                    start_date: "2021-10-01T00:00:00+02:00",
                    end_date: "2021-10-15T00:00:00+02:00",
                },
            ]);

            const wrapper = await getWrapper({ label: "", sub_label: "" });

            expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(true);

            await Vue.nextTick();
            await Vue.nextTick();

            expect(retriever.getIncrementIterations).toHaveBeenCalledWith(666);
            expect(wrapper.find("[data-test=planned-iterations-empty-state]").exists()).toBe(false);
            expect(wrapper.findComponent(IterationCard).exists()).toBe(true);
            expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(false);
        });
    });
});
