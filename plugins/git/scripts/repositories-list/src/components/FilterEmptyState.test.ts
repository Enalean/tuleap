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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import FilterEmptyState from "./FilterEmptyState.vue";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import type { StoreOptions } from "vuex";
import type { State } from "../type";

describe("FilterEmptyState", () => {
    function instantiateComponent(
        store_options: StoreOptions<State>,
    ): VueWrapper<InstanceType<typeof FilterEmptyState>> {
        return shallowMount(FilterEmptyState, {
            global: { ...getGlobalTestOptions(store_options) },
        });
    }

    it("does not display empty state when we are not filtering", () => {
        const store_options = {
            getters: {
                isThereAResultInCurrentFilteredList: (): boolean => false,
                isCurrentRepositoryListEmpty: (): boolean => false,
                isInitialLoadingDoneWithoutError: (): boolean => false,
                isFiltering: (): boolean => false,
            },
        };

        const wrapper = instantiateComponent(store_options);
        expect(wrapper.find("[data-test=empty-state]").exists()).toBeFalsy();
    });

    it("does not display empty state when initial load is ko", () => {
        const store_options = {
            getters: {
                isThereAResultInCurrentFilteredList: (): boolean => false,
                isCurrentRepositoryListEmpty: (): boolean => false,
                isInitialLoadingDoneWithoutError: (): boolean => false,
                isFiltering: (): boolean => true,
            },
        };

        const wrapper = instantiateComponent(store_options);
        expect(wrapper.find("[data-test=empty-state]").exists()).toBeFalsy();
    });

    it("does not display empty state when current repository list is empty", () => {
        const store_options = {
            getters: {
                isThereAResultInCurrentFilteredList: (): boolean => false,
                isCurrentRepositoryListEmpty: (): boolean => true,
                isInitialLoadingDoneWithoutError: (): boolean => true,
                isFiltering: (): boolean => true,
            },
        };

        const wrapper = instantiateComponent(store_options);
        expect(wrapper.find("[data-test=empty-state]").exists()).toBeFalsy();
    });

    it("does not display empty state when there are no results in list", () => {
        const store_options = {
            getters: {
                isThereAResultInCurrentFilteredList: (): boolean => false,
                isCurrentRepositoryListEmpty: (): boolean => true,
                isInitialLoadingDoneWithoutError: (): boolean => true,
                isFiltering: (): boolean => true,
            },
        };

        const wrapper = instantiateComponent(store_options);
        expect(wrapper.find("[data-test=empty-state]").exists()).toBeFalsy();
    });

    it("does display empty state when there are results in list", () => {
        const store_options = {
            getters: {
                isThereAResultInCurrentFilteredList: (): boolean => true,
                isCurrentRepositoryListEmpty: (): boolean => false,
                isInitialLoadingDoneWithoutError: (): boolean => true,
                isFiltering: (): boolean => true,
            },
        };

        const wrapper = instantiateComponent(store_options);
        expect(wrapper.find("[data-test=empty-state]").exists()).toBeFalsy();
    });
});
