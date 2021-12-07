/**
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
import * as retriever from "../../../helpers/iteration-content-retriever";

import IterationUserStoryList from "./IterationUserStoryList.vue";
import BacklogElementSkeleton from "../../BacklogElementSkeleton.vue";
import FeatureCard from "./FeatureCard.vue";

import type { Wrapper } from "@vue/test-utils";
import type { Feature } from "../../../type";

describe("IterationUserStoryList", () => {
    async function getWrapper(): Promise<Wrapper<IterationUserStoryList>> {
        return shallowMount(IterationUserStoryList, {
            localVue: await createPlanIterationsLocalVue(),
            propsData: {
                iteration: {
                    id: 666,
                },
            },
        });
    }

    it("Displays the empty state when no features are found", async () => {
        jest.spyOn(retriever, "retrieveIterationContent").mockResolvedValue([]);

        const wrapper = await getWrapper();

        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(true);

        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(false);
    });

    it("Displays the features", async () => {
        jest.spyOn(retriever, "retrieveIterationContent").mockResolvedValue([
            { id: 1201 } as Feature,
            { id: 1202 } as Feature,
            { id: 1203 } as Feature,
        ]);

        const wrapper = await getWrapper();

        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(true);

        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        const feature_cards = wrapper.findAllComponents(FeatureCard);

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(false);
        expect(feature_cards.length).toEqual(3);
    });
});
