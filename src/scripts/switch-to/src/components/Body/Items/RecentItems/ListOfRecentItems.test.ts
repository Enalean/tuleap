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

import { describe, expect, it } from "@jest/globals";
import { shallowMount } from "@vue/test-utils";
import ListOfRecentItems from "./ListOfRecentItems.vue";
import { createTestingPinia } from "@pinia/testing";
import type { ItemDefinition, UserHistory } from "../../../../type";
import RecentItemsErrorState from "./RecentItemsErrorState.vue";
import RecentItemsEmptyState from "./RecentItemsEmptyState.vue";
import RecentItemsLoadingState from "./RecentItemsLoadingState.vue";
import ItemEntry from "../ItemEntry.vue";
import { defineStore } from "pinia";
import type { State } from "../../../../stores/type";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

describe("ListOfRecentItems", () => {
    it("Displays an empty state", () => {
        const useSwitchToStore = defineStore("root", {
            state: (): State =>
                ({
                    is_history_in_error: false,
                    is_loading_history: false,
                    is_history_loaded: true,
                    history: { entries: [] as ItemDefinition[] },
                }) as State,
            getters: {
                filtered_history: (): UserHistory => ({ entries: [] }),
                is_in_search_mode: (): boolean => false,
            },
        });

        const pinia = createTestingPinia();
        useSwitchToStore(pinia);

        const wrapper = shallowMount(ListOfRecentItems, {
            global: getGlobalTestOptions(pinia),
        });
        expect(wrapper.findComponent(RecentItemsErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(RecentItemsEmptyState).exists()).toBe(true);
        expect(wrapper.findComponent(RecentItemsLoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(ItemEntry).exists()).toBe(false);
    });

    it("Display a loading state", () => {
        const useSwitchToStore = defineStore("root", {
            state: (): State =>
                ({
                    is_history_in_error: false,
                    is_loading_history: true,
                    is_history_loaded: false,
                    history: { entries: [] as ItemDefinition[] },
                }) as State,
            getters: {
                filtered_history: (): UserHistory => ({ entries: [] }),
                is_in_search_mode: (): boolean => false,
            },
        });

        const pinia = createTestingPinia();
        useSwitchToStore(pinia);

        const wrapper = shallowMount(ListOfRecentItems, {
            global: getGlobalTestOptions(pinia),
        });

        expect(wrapper.findComponent(RecentItemsErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(RecentItemsEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(RecentItemsLoadingState).exists()).toBe(true);
        expect(wrapper.findComponent(ItemEntry).exists()).toBe(false);
    });

    it("Display recent items", () => {
        const useSwitchToStore = defineStore("root", {
            state: (): State =>
                ({
                    is_history_in_error: false,
                    is_loading_history: false,
                    is_history_loaded: true,
                    history: { entries: [{}, {}] as ItemDefinition[] },
                }) as State,
            getters: {
                filtered_history: (): UserHistory => ({ entries: [{}, {}] as ItemDefinition[] }),
                is_in_search_mode: (): boolean => false,
            },
        });

        const pinia = createTestingPinia();
        useSwitchToStore(pinia);

        const wrapper = shallowMount(ListOfRecentItems, {
            global: getGlobalTestOptions(pinia),
        });

        expect(wrapper.findComponent(RecentItemsErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(RecentItemsEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(RecentItemsLoadingState).exists()).toBe(false);
        expect(wrapper.findAllComponents(ItemEntry)).toHaveLength(2);
    });

    it(`Given user is searching for a term
        When there is no matching recent items
        Then we should not display anything`, () => {
        const useSwitchToStore = defineStore("root", {
            state: (): State =>
                ({
                    is_history_in_error: false,
                    is_loading_history: false,
                    is_history_loaded: true,
                    history: { entries: [{}, {}] as ItemDefinition[] },
                }) as State,
            getters: {
                filtered_history: (): UserHistory => ({ entries: [] }),
                is_in_search_mode: (): boolean => true,
            },
        });

        const pinia = createTestingPinia();
        useSwitchToStore(pinia);

        const wrapper = shallowMount(ListOfRecentItems, {
            global: getGlobalTestOptions(pinia),
        });

        expect(wrapper.element).toMatchInlineSnapshot(`<!--v-if-->`);
    });

    it("Display filtered items", () => {
        const useSwitchToStore = defineStore("root", {
            state: (): State =>
                ({
                    is_history_in_error: false,
                    is_loading_history: false,
                    is_history_loaded: true,
                    history: { entries: [{}, {}] as ItemDefinition[] },
                }) as State,
            getters: {
                filtered_history: (): UserHistory => ({ entries: [{}] as ItemDefinition[] }),
                is_in_search_mode: (): boolean => true,
            },
        });

        const pinia = createTestingPinia();
        useSwitchToStore(pinia);

        const wrapper = shallowMount(ListOfRecentItems, {
            global: getGlobalTestOptions(pinia),
        });

        expect(wrapper.findComponent(RecentItemsErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(RecentItemsEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(RecentItemsLoadingState).exists()).toBe(false);
        expect(wrapper.findAllComponents(ItemEntry)).toHaveLength(1);
    });

    it("Display error state", () => {
        const useSwitchToStore = defineStore("root", {
            state: (): State =>
                ({
                    is_history_in_error: true,
                    is_loading_history: true,
                    is_history_loaded: false,
                    history: { entries: [] as ItemDefinition[] },
                }) as State,
            getters: {
                filtered_history: (): UserHistory => ({ entries: [] }),
                is_in_search_mode: (): boolean => false,
            },
        });

        const pinia = createTestingPinia();
        useSwitchToStore(pinia);

        const wrapper = shallowMount(ListOfRecentItems, {
            global: getGlobalTestOptions(pinia),
        });

        expect(wrapper.findComponent(RecentItemsErrorState).exists()).toBe(true);
        expect(wrapper.findComponent(RecentItemsEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(RecentItemsLoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(ItemEntry).exists()).toBe(false);
    });
});
