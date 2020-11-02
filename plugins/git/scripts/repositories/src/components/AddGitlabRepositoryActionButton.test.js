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

import { createStoreMock } from "../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import { shallowMount } from "@vue/test-utils";
import AddGitlabRepositoryActionButton from "./AddGitlabRepositoryActionButton.vue";
import localVue from "../support/local-vue.js";

describe("AddGitlabRepositoryActionButton", () => {
    let store_options;
    beforeEach(() => {
        store_options = {
            state: {
                used_service_name: [],
            },
            getters: {
                isGitlabUsed: false,
            },
        };
    });

    function instantiateComponent() {
        const store = createStoreMock(store_options);
        return shallowMount(AddGitlabRepositoryActionButton, {
            mocks: { $store: store },
            localVue,
        });
    }

    it("When there is no used externals services, Then there is no option GitLab", () => {
        const wrapper = instantiateComponent();
        expect(wrapper.find("[data-test=gitlab-project-button]").exists()).toBeFalsy();
    });

    it("When GitLab is an external service, Then the action is displayed", () => {
        store_options.getters.isGitlabUsed = true;
        store_options.state.used_service_name = ["gitlab"];
        const wrapper = instantiateComponent();
        expect(wrapper.find("[data-test=gitlab-project-button]").exists()).toBeTruthy();
    });
});
