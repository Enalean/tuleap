/**
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

import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import { shallowMount } from "@vue/test-utils";
import ListRepositoriesModal from "./ListRepositoriesModal.vue";
import localVue from "../../../support/local-vue";

describe("ListRepositoriesModal", () => {
    let store_options, store, propsData;
    beforeEach(() => {
        store_options = {
            state: {
                used_service_name: [],
                is_first_load_done: true,
            },
            getters: {
                areExternalUsedServices: false,
                isCurrentRepositoryListEmpty: false,
                isInitialLoadingDoneWithoutError: true,
            },
        };
    });

    function instantiateComponent() {
        store = createStoreMock(store_options);
        return shallowMount(ListRepositoriesModal, {
            propsData,
            mocks: { $store: store },
            localVue,
        });
    }

    it("When there are repositories, Then repositories are displayed", () => {
        propsData = {
            repositories: [
                { id: 10, name_with_namespace: "My Path / Repository" },
                {
                    id: 11,
                    name_with_namespace: "My Second / Repository",
                    avatar_url: "example.com",
                },
            ],
        };
        const wrapper = instantiateComponent();

        expect(wrapper.find("[data-test=gitlab-repositories-displayed-10]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=gitlab-repositories-displayed-11]").exists()).toBeTruthy();
    });

    it("When no repository is selected, Then integrate button is disabled", async () => {
        propsData = {
            repositories: [
                { id: 10, name_with_namespace: "My Path / Repository" },
                {
                    id: 11,
                    name_with_namespace: "My Second / Repository",
                    avatar_url: "example.com",
                },
            ],
        };
        const wrapper = instantiateComponent();

        wrapper.setData({
            selected_repository: null,
        });
        await wrapper.vm.$nextTick();

        expect(
            wrapper.find("[data-test=button-integrate-gitlab-repository]").attributes().disabled
        ).toBeTruthy();

        wrapper.setData({
            selected_repository: { id: 10, name_with_namespace: "My Path / Repository" },
        });

        await wrapper.vm.$nextTick();

        expect(
            wrapper.find("[data-test=button-integrate-gitlab-repository]").attributes().disabled
        ).toBeFalsy();
    });

    it("When user clicks on back button, Then event is emitted", async () => {
        const wrapper = instantiateComponent();

        wrapper.find("[data-test=gitlab-button-back]").trigger("click");

        await wrapper.vm.$nextTick();
        expect(wrapper.emitted("to-back-button")).toBeTruthy();
    });
});
