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

import { nextTick } from "vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import IterationUserStoryList from "./IterationUserStoryList.vue";
import BacklogElementSkeleton from "../../BacklogElementSkeleton.vue";
import UserStoryCard from "./UserStoryCard.vue";
import type { Iteration, UserStory } from "../../../type";

jest.useFakeTimers();

type CachedContentChecker = () => boolean;
type CachedContentGetter = () => ReadonlyArray<UserStory>;

describe("IterationUserStoryList", () => {
    function getWrapper(
        resultOfFetch: Promise<UserStory[]>,
    ): VueWrapper<InstanceType<typeof IterationUserStoryList>> {
        const store_options = {
            getters: {
                hasIterationContentInStore: (): CachedContentChecker => () => false,
                getIterationContentFromStore: (): CachedContentGetter => () => [],
            },
            actions: {
                fetchIterationContent: (): Promise<UserStory[]> => resultOfFetch,
            },
        };
        return shallowMount(IterationUserStoryList, {
            global: { ...getGlobalTestOptions(store_options) },
            props: { iteration: { id: 666 } as Iteration },
        });
    }

    it("Displays the empty state when no user story is found", async () => {
        const wrapper = getWrapper(Promise.resolve([]));

        await nextTick();
        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(true);

        await jest.runOnlyPendingTimersAsync();

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(false);
        expect(wrapper.find("[data-test=iteration-content-error-message]").exists()).toBe(false);
    });

    it("Displays the user stories", async () => {
        const wrapper = getWrapper(
            Promise.resolve([
                { id: 1201 } as UserStory,
                { id: 1202 } as UserStory,
                { id: 1203 } as UserStory,
            ]),
        );

        await nextTick();
        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(true);

        await jest.runOnlyPendingTimersAsync();

        const user_stories_card = wrapper.findAllComponents(UserStoryCard);

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(false);
        expect(wrapper.find("[data-test=iteration-content-error-message]").exists()).toBe(false);
        expect(user_stories_card).toHaveLength(3);
    });

    it("displays an error message when the loading of the iteration content has failed", async () => {
        const wrapper = getWrapper(Promise.reject("Nope"));

        await jest.runOnlyPendingTimersAsync();

        expect(wrapper.find("[data-test=iteration-content-error-message]").exists()).toBe(true);
        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(false);
    });

    it("should not query the API when content is already stored", async () => {
        const wrapper = shallowMount(IterationUserStoryList, {
            global: {
                ...getGlobalTestOptions({
                    getters: {
                        hasIterationContentInStore: (): CachedContentChecker => () => true,
                        getIterationContentFromStore: (): CachedContentGetter => () => [
                            { id: 1201 } as UserStory,
                            { id: 1202 } as UserStory,
                            { id: 1203 } as UserStory,
                        ],
                    },
                    actions: {
                        fetchIterationContent: () => Promise.reject("Nope"),
                    },
                }),
            },
            props: { iteration: { id: 666 } as Iteration },
        });

        await nextTick();
        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(false);

        await jest.runOnlyPendingTimersAsync();

        const feature_cards = wrapper.findAllComponents(UserStoryCard);

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBe(false);
        expect(wrapper.find("[data-test=iteration-content-error-message]").exists()).toBe(false);
        expect(feature_cards).toHaveLength(3);
    });
});
