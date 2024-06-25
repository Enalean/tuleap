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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ActionBar from "./ActionBar.vue";
import DropdownActionButton from "./DropdownActionButton.vue";
import * as repo_list from "../repository-list-presenter";
import type { State } from "../type";
import { createLocalVueForTests } from "../helpers/local-vue-for-tests";

interface StoreOption {
    state: State;
    getters?: {
        areExternalUsedServices?: boolean;
        isCurrentRepositoryListEmpty?: boolean;
        isInitialLoadingDoneWithoutError?: boolean;
    };
}

describe("ActionBar", () => {
    beforeEach(() => {
        jest.spyOn(repo_list, "getUserIsAdmin").mockReturnValue(true);
    });

    async function instantiateComponent(store_options: StoreOption): Promise<Wrapper<Vue>> {
        const store = createStoreMock(store_options);
        return shallowMount(ActionBar, {
            mocks: { $store: store },
            localVue: await createLocalVueForTests(),
        });
    }

    it("When there is no used externals services, Then there is a button to create a repo", async () => {
        const wrapper = await instantiateComponent({
            state: {
                is_first_load_done: true,
            } as unknown as State,
            getters: {
                areExternalUsedServices: false,
                isCurrentRepositoryListEmpty: false,
                isInitialLoadingDoneWithoutError: true,
            },
        });
        expect(wrapper.findComponent(DropdownActionButton).exists()).toBeFalsy();
        expect(wrapper.find("[data-test=create-repository-button]").exists()).toBeTruthy();
    });

    it("When GitLab is an external service, Then dropdown is displayed the action is displayed", async () => {
        const wrapper = await instantiateComponent({
            state: {
                is_first_load_done: true,
            } as unknown as State,
            getters: {
                areExternalUsedServices: true,
                isCurrentRepositoryListEmpty: false,
                isInitialLoadingDoneWithoutError: true,
            },
        });
        expect(wrapper.findComponent(DropdownActionButton).exists()).toBeTruthy();
        expect(wrapper.find("[data-test=create-repository-button]").exists()).toBeFalsy();
    });
});
