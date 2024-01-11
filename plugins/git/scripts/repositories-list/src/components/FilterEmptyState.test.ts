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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import FilterEmptyState from "./FilterEmptyState.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { createLocalVueForTests } from "../helpers/local-vue-for-tests";

interface StoreOption {
    getters?: {
        isThereAResultInCurrentFilteredList: boolean;
        isCurrentRepositoryListEmpty: boolean;
        isInitialLoadingDoneWithoutError: boolean;
        isFiltering: boolean;
    };
}

describe("FilterEmptyState", () => {
    async function instantiateComponent(
        store_options: StoreOption,
    ): Promise<Wrapper<FilterEmptyState>> {
        const store = createStoreMock(store_options);
        return shallowMount(FilterEmptyState, {
            mocks: { $store: store },
            localVue: await createLocalVueForTests(),
        });
    }

    it("does not display empty state when we are not filtering", async () => {
        const store_options = {
            getters: {
                isThereAResultInCurrentFilteredList: false,
                isCurrentRepositoryListEmpty: false,
                isInitialLoadingDoneWithoutError: false,
                isFiltering: false,
            },
        };

        const wrapper = await instantiateComponent(store_options);
        expect(wrapper.find("[data-test=empty-state]").exists()).toBeFalsy();
    });

    it("does not display empty state when initial load is ko", async () => {
        const store_options = {
            getters: {
                isThereAResultInCurrentFilteredList: false,
                isCurrentRepositoryListEmpty: false,
                isInitialLoadingDoneWithoutError: false,
                isFiltering: true,
            },
        };

        const wrapper = await instantiateComponent(store_options);
        expect(wrapper.find("[data-test=empty-state]").exists()).toBeFalsy();
    });

    it("does not display empty state when current repository list is empty", async () => {
        const store_options = {
            getters: {
                isThereAResultInCurrentFilteredList: false,
                isCurrentRepositoryListEmpty: true,
                isInitialLoadingDoneWithoutError: true,
                isFiltering: true,
            },
        };

        const wrapper = await instantiateComponent(store_options);
        expect(wrapper.find("[data-test=empty-state]").exists()).toBeFalsy();
    });

    it("does not display empty state when there are no results in list", async () => {
        const store_options = {
            getters: {
                isThereAResultInCurrentFilteredList: false,
                isCurrentRepositoryListEmpty: true,
                isInitialLoadingDoneWithoutError: true,
                isFiltering: true,
            },
        };

        const wrapper = await instantiateComponent(store_options);
        expect(wrapper.find("[data-test=empty-state]").exists()).toBeFalsy();
    });

    it("does display empty state when there are results in list", async () => {
        const store_options = {
            getters: {
                isThereAResultInCurrentFilteredList: true,
                isCurrentRepositoryListEmpty: false,
                isInitialLoadingDoneWithoutError: true,
                isFiltering: true,
            },
        };

        const wrapper = await instantiateComponent(store_options);
        expect(wrapper.find("[data-test=empty-state]").exists()).toBeFalsy();
    });
});
