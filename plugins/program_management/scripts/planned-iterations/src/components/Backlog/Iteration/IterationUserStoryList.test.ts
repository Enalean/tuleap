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
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { createPlanIterationsLocalVue } from "../../../helpers/local-vue-for-test";

import IterationUserStoryList from "./IterationUserStoryList.vue";
import BacklogElementSkeleton from "../../BacklogElementSkeleton.vue";
import UserStoryCard from "./UserStoryCard.vue";

import type { Store } from "@tuleap/vuex-store-wrapper-jest";
import type { UserStory } from "../../../type";

jest.useFakeTimers();

describe("IterationUserStoryList", () => {
    it("Displays the empty state when no user story is found", async () => {
        const store: Store = createStoreMock({
            getters: {
                hasIterationContentInStore: () => false,
                getIterationContentFromStore: () => [],
            },
        });

        jest.spyOn(store, "dispatch").mockResolvedValue([]);

        const wrapper = shallowMount(IterationUserStoryList, {
            localVue: await createPlanIterationsLocalVue(),
            propsData: {
                iteration: {
                    id: 666,
                },
            },
            mocks: {
                $store: store,
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(true);

        await jest.runOnlyPendingTimersAsync();

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(false);
        expect(wrapper.find("[data-test=iteration-content-error-message]").exists()).toBe(false);
    });

    it("Displays the user stories", async () => {
        const store: Store = createStoreMock({
            getters: {
                hasIterationContentInStore: () => false,
                getIterationContentFromStore: () => [],
            },
        });

        jest.spyOn(store, "dispatch").mockResolvedValue([
            { id: 1201 } as UserStory,
            { id: 1202 } as UserStory,
            { id: 1203 } as UserStory,
        ]);

        const wrapper = shallowMount(IterationUserStoryList, {
            localVue: await createPlanIterationsLocalVue(),
            propsData: {
                iteration: {
                    id: 666,
                },
            },
            mocks: {
                $store: store,
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(true);

        await jest.runOnlyPendingTimersAsync();

        const user_stories_card = wrapper.findAllComponents(UserStoryCard);

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(false);
        expect(wrapper.find("[data-test=iteration-content-error-message]").exists()).toBe(false);
        expect(user_stories_card).toHaveLength(3);
    });

    it("displays an error message when the loading of the iteration content has failed", async () => {
        const store: Store = createStoreMock({
            getters: {
                hasIterationContentInStore: () => false,
                getIterationContentFromStore: () => [],
            },
        });

        jest.spyOn(store, "dispatch").mockRejectedValue("Nope");

        const wrapper = shallowMount(IterationUserStoryList, {
            localVue: await createPlanIterationsLocalVue(),
            propsData: {
                iteration: {
                    id: 666,
                },
            },
            mocks: {
                $store: store,
            },
        });

        await jest.runOnlyPendingTimersAsync();

        expect(wrapper.find("[data-test=iteration-content-error-message]").exists()).toBe(true);
        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(false);
    });

    it("should not query the API when content is already stored", async () => {
        const store: Store = createStoreMock({
            getters: {
                hasIterationContentInStore: () => true,
                getIterationContentFromStore: () => [
                    { id: 1201 } as UserStory,
                    { id: 1202 } as UserStory,
                    { id: 1203 } as UserStory,
                ],
            },
        });

        jest.spyOn(store, "dispatch").mockRejectedValue("Nope");

        const wrapper = shallowMount(IterationUserStoryList, {
            localVue: await createPlanIterationsLocalVue(),
            propsData: {
                iteration: {
                    id: 666,
                },
            },
            mocks: {
                $store: store,
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(false);

        await jest.runOnlyPendingTimersAsync();

        const feature_cards = wrapper.findAllComponents(UserStoryCard);

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(false);
        expect(wrapper.find("[data-test=iteration-content-error-message]").exists()).toBe(false);
        expect(feature_cards).toHaveLength(3);
    });
});
