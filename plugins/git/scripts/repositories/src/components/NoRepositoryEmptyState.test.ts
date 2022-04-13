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
import { createLocalVue, shallowMount } from "@vue/test-utils";
import NoRepositoryEmptyState from "./NoRepositoryEmptyState.vue";
import DropdownActionButton from "./DropdownActionButton.vue";
import * as repo_list from "../repository-list-presenter";
import VueDOMPurifyHTML from "vue-dompurify-html";
import GetTextPlugin from "vue-gettext";
import type { State } from "../type";

describe("NoRepositoryEmptyState", () => {
    interface StoreOption {
        state: State;
        getters: {
            areExternalUsedServices?: boolean;
            isCurrentRepositoryListEmpty?: boolean;
            isInitialLoadingDoneWithoutError?: boolean;
        };
    }

    let store_options: StoreOption;
    beforeEach(() => {
        jest.spyOn(repo_list, "getUserIsAdmin").mockReturnValue(true);

        store_options = {
            state: {
                is_first_load_done: true,
            } as State,
            getters: {
                areExternalUsedServices: false,
                isCurrentRepositoryListEmpty: true,
                isInitialLoadingDoneWithoutError: true,
            },
        };
    });

    function instantiateComponent(): Wrapper<NoRepositoryEmptyState> {
        const localVue = createLocalVue();
        localVue.use(VueDOMPurifyHTML);
        localVue.use(GetTextPlugin, {
            translations: {},
            silent: true,
        });

        const store = createStoreMock(store_options);
        return shallowMount(NoRepositoryEmptyState, {
            mocks: { $store: store },
            localVue,
        });
    }

    it("When there is no used externals services, Then there is a button to create a repo", () => {
        const wrapper = instantiateComponent();
        expect(wrapper.findComponent(DropdownActionButton).exists()).toBeFalsy();
        expect(wrapper.find("[data-test=create-repository-button]").exists()).toBeTruthy();
    });

    it("When Gitlab is an external service, Then dropdown is displayed the action is displayed", () => {
        store_options.getters.areExternalUsedServices = true;
        const wrapper = instantiateComponent();
        expect(wrapper.findComponent(DropdownActionButton).exists()).toBeTruthy();
        expect(wrapper.find("[data-test=create-repository-button]").exists()).toBeFalsy();
    });

    it("When the user is not git admin, Then there aren't any button", () => {
        jest.spyOn(repo_list, "getUserIsAdmin").mockReturnValue(false);
        const wrapper = instantiateComponent();
        expect(wrapper.findComponent(DropdownActionButton).exists()).toBeFalsy();
        expect(wrapper.find("[data-test=create-repository-button]").exists()).toBeFalsy();
    });
});
