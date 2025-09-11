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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import NoRepositoryEmptyState from "./NoRepositoryEmptyState.vue";
import DropdownActionButton from "./DropdownActionButton.vue";
import * as repo_list from "../repository-list-presenter";
import type { State } from "../type";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";

describe("NoRepositoryEmptyState", () => {
    beforeEach(() => {
        jest.spyOn(repo_list, "getUserIsAdmin").mockReturnValue(true);
    });

    function instantiateComponent(
        are_external_used_services: boolean,
    ): VueWrapper<InstanceType<typeof NoRepositoryEmptyState>> {
        const store_options = {
            state: {
                is_first_load_done: true,
            } as State,
            getters: {
                areExternalUsedServices: (): boolean => are_external_used_services,
                isCurrentRepositoryListEmpty: (): boolean => true,
                isInitialLoadingDoneWithoutError: (): boolean => true,
            },
        };

        return shallowMount(NoRepositoryEmptyState, {
            global: { ...getGlobalTestOptions(store_options) },
        });
    }

    it("When there is no used externals services, Then there is a button to create a repo", () => {
        const wrapper = instantiateComponent(false);
        expect(wrapper.findComponent(DropdownActionButton).exists()).toBeFalsy();
        expect(wrapper.find("[data-test=create-repository-button]").exists()).toBeTruthy();
    });

    it("When Gitlab is an external service, Then dropdown is displayed the action is displayed", () => {
        const wrapper = instantiateComponent(true);
        expect(wrapper.findComponent(DropdownActionButton).exists()).toBeTruthy();
        expect(wrapper.find("[data-test=create-repository-button]").exists()).toBeFalsy();
    });

    it("When the user is not git admin, Then there aren't any button", () => {
        jest.spyOn(repo_list, "getUserIsAdmin").mockReturnValue(false);
        const wrapper = instantiateComponent(false);
        expect(wrapper.findComponent(DropdownActionButton).exists()).toBeFalsy();
        expect(wrapper.find("[data-test=create-repository-button]").exists()).toBeFalsy();
    });
});
