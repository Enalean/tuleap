/*
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
import { createPlanIterationsLocalVue } from "../../../helpers/local-vue-for-test";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import * as retriever from "../../../helpers/increment-unplanned-elements-retriever";

import BacklogElementSkeleton from "./../../BacklogElementSkeleton.vue";
import IterationsToBePlannedSection from "./IterationsToBePlannedSection.vue";
import UserStoryCard from "../Iteration/UserStoryCard.vue";

import type { Wrapper } from "@vue/test-utils";

describe("IterationsToBePlannedSection", () => {
    async function getWrapper(): Promise<Wrapper<IterationsToBePlannedSection>> {
        return shallowMount(IterationsToBePlannedSection, {
            localVue: await createPlanIterationsLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            program_increment: {
                                id: 666,
                                title: "Mating",
                            },
                        },
                    },
                }),
            },
        });
    }

    it("should display its placeholder when there is no iteration in the increment", async () => {
        jest.spyOn(retriever, "retrieveUnplannedElements").mockResolvedValue([]);

        const wrapper = await getWrapper();

        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(true);

        await Vue.nextTick();
        await Vue.nextTick();

        expect(wrapper.find("[data-test=no-unplanned-elements-empty-state]").exists()).toBe(true);
        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(false);
    });

    it("should fetch unplanned elements and display them", async () => {
        jest.spyOn(retriever, "retrieveUnplannedElements").mockResolvedValue([
            {
                id: 1279,
                background_color: "red-wine",
                is_open: true,
                uri: "uri/to/us-1279",
                xref: "US #1279",
                title: "Wazaaaa",
                tracker: {
                    color_name: "lemon-green",
                },
                project: {
                    id: 101,
                    uri: "uri/to/project-101",
                    label: "Project 101",
                    icon: "",
                },
                feature: null,
            },
        ]);

        const wrapper = await getWrapper();

        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(true);

        await Vue.nextTick();
        await Vue.nextTick();

        const unplanned_elements = wrapper.findAllComponents(UserStoryCard);

        expect(retriever.retrieveUnplannedElements).toHaveBeenCalledWith(666);
        expect(wrapper.find("[data-test=no-unplanned-elements-empty-state]").exists()).toBe(false);
        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(false);
        expect(unplanned_elements).toHaveLength(1);
    });

    it("should display an error when the retrieval has failed", async () => {
        jest.spyOn(retriever, "retrieveUnplannedElements").mockRejectedValue("Nope");

        const wrapper = await getWrapper();

        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(true);

        await Vue.nextTick();
        await Vue.nextTick();

        const unplanned_elements = wrapper.findAllComponents(UserStoryCard);

        expect(retriever.retrieveUnplannedElements).toHaveBeenCalledWith(666);
        expect(wrapper.find("[data-test=no-unplanned-elements-empty-state]").exists()).toBe(false);
        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(false);
        expect(unplanned_elements).toHaveLength(0);
        expect(
            wrapper.find("[data-test=unplanned-elements-retrieval-error-message]").exists(),
        ).toBe(true);
    });
});
