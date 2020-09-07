/*
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

import { shallowMount } from "@vue/test-utils";
import { createSwitchToLocalVue } from "../../../helpers/local-vue-for-test";
import ListOfRecentItems from "./ListOfRecentItems.vue";
import { createStoreMock } from "../../../../../vue-components/store-wrapper-jest";
import { State } from "../../../store/type";
import { UserHistoryEntry } from "../../../type";
import RecentItemsErrorState from "./RecentItemsErrorState.vue";
import RecentItemsEmptyState from "./RecentItemsEmptyState.vue";
import RecentItemsLoadingState from "./RecentItemsLoadingState.vue";
import RecentItemsEntry from "./RecentItemsEntry.vue";

describe("ListOfRecentItems", () => {
    it("Displays an empty state", async () => {
        const wrapper = shallowMount(ListOfRecentItems, {
            localVue: await createSwitchToLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        is_history_in_error: false,
                        is_loading_history: false,
                        is_history_loaded: true,
                        history: { entries: [] as UserHistoryEntry[] },
                    } as State,
                    getters: {
                        filtered_history: { entries: [] as UserHistoryEntry[] },
                    },
                }),
            },
        });

        expect(wrapper.findComponent(RecentItemsErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(RecentItemsEmptyState).exists()).toBe(true);
        expect(wrapper.findComponent(RecentItemsLoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(RecentItemsEntry).exists()).toBe(false);
    });

    it("Display a loading state", async () => {
        const wrapper = shallowMount(ListOfRecentItems, {
            localVue: await createSwitchToLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        is_history_in_error: false,
                        is_loading_history: true,
                        is_history_loaded: false,
                        history: { entries: [] as UserHistoryEntry[] },
                    } as State,
                    getters: {
                        filtered_history: { entries: [] as UserHistoryEntry[] },
                    },
                }),
            },
        });

        expect(wrapper.findComponent(RecentItemsErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(RecentItemsEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(RecentItemsLoadingState).exists()).toBe(true);
        expect(wrapper.findComponent(RecentItemsEntry).exists()).toBe(false);
    });

    it("Display recent items", async () => {
        const wrapper = shallowMount(ListOfRecentItems, {
            localVue: await createSwitchToLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        is_history_in_error: false,
                        is_loading_history: false,
                        is_history_loaded: true,
                        history: { entries: [{}, {}] as UserHistoryEntry[] },
                    } as State,
                    getters: {
                        filtered_history: { entries: [{}, {}] as UserHistoryEntry[] },
                    },
                }),
            },
        });

        expect(wrapper.findComponent(RecentItemsErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(RecentItemsEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(RecentItemsLoadingState).exists()).toBe(false);
        expect(wrapper.findAllComponents(RecentItemsEntry).length).toBe(2);
    });

    it("Display filtered items", async () => {
        const wrapper = shallowMount(ListOfRecentItems, {
            localVue: await createSwitchToLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        is_history_in_error: false,
                        is_loading_history: false,
                        is_history_loaded: true,
                        history: { entries: [{}, {}] as UserHistoryEntry[] },
                    } as State,
                    getters: {
                        filtered_history: { entries: [{}] as UserHistoryEntry[] },
                    },
                }),
            },
        });

        expect(wrapper.findComponent(RecentItemsErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(RecentItemsEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(RecentItemsLoadingState).exists()).toBe(false);
        expect(wrapper.findAllComponents(RecentItemsEntry).length).toBe(1);
    });

    it("Display error state", async () => {
        const wrapper = shallowMount(ListOfRecentItems, {
            localVue: await createSwitchToLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        is_history_in_error: true,
                        is_loading_history: true,
                        is_history_loaded: false,
                        history: { entries: [] as UserHistoryEntry[] },
                    } as State,
                    getters: {
                        filtered_history: { entries: [] as UserHistoryEntry[] },
                    },
                }),
            },
        });

        expect(wrapper.findComponent(RecentItemsErrorState).exists()).toBe(true);
        expect(wrapper.findComponent(RecentItemsEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(RecentItemsLoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(RecentItemsEntry).exists()).toBe(false);
    });
});
