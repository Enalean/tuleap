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
import type { StoreOptions } from "vuex";
import AddGitlabRepositoryActionButton from "./AddGitlabRepositoryActionButton.vue";
import type { State } from "../type";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";

describe("AddGitlabRepositoryActionButton", () => {
    function instantiateComponent(
        store_options: StoreOptions<State>,
    ): VueWrapper<InstanceType<typeof AddGitlabRepositoryActionButton>> {
        return shallowMount(AddGitlabRepositoryActionButton, {
            global: { ...getGlobalTestOptions(store_options) },
        });
    }

    it("When there is no used externals services, Then there is no option GitLab", () => {
        const wrapper = instantiateComponent({
            state: {} as State,
            getters: {
                isGitlabUsed: () => false,
            },
        });
        expect(wrapper.find("[data-test=gitlab-project-button]").exists()).toBeFalsy();
    });

    it("When GitLab is an external service, Then the action is displayed", () => {
        const wrapper = instantiateComponent({
            state: {} as State,
            getters: {
                isGitlabUsed: () => true,
            },
        });
        expect(wrapper.find("[data-test=gitlab-project-button]").exists()).toBeTruthy();
    });
});
