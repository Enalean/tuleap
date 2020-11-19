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
import localVue from "../../../support/local-vue.js";
import GitlabRepositoryModal from "./GitlabRepositoryModal.vue";
import ListRepositoriesModal from "./ListRepositoriesModal.vue";
import CredentialsFormModal from "./CredentialsFormModal.vue";

describe("GitlabRepositoryModal", () => {
    let store_options, store;
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
        return shallowMount(GitlabRepositoryModal, {
            mocks: { $store: store },
            localVue,
        });
    }

    it("When a user displays the modal ,then the CredentialsFormModal is displayed", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            gitlab_repositories: null,
            back_button_clicked: false,
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(ListRepositoriesModal).exists()).toBeFalsy();
        expect(wrapper.findComponent(CredentialsFormModal).exists()).toBeTruthy();
    });

    it("When user have clicked on back button, Then CredentialsFormModal is displayed", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            gitlab_repositories: [{ id: 10 }],
            back_button_clicked: true,
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(ListRepositoriesModal).exists()).toBeFalsy();
        expect(wrapper.findComponent(CredentialsFormModal).exists()).toBeTruthy();
    });

    it("When repositories have been retrieved, Then ListRepositoriesModal is displayed", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            gitlab_repositories: [{ id: 10 }],
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(ListRepositoriesModal).exists()).toBeTruthy();
        expect(wrapper.findComponent(CredentialsFormModal).exists()).toBeFalsy();
    });

    it("When ListRepositoriesModal emit to-back-button, Then CredentialsFormModal is displayed", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            gitlab_repositories: [{ id: 10 }],
        });
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(ListRepositoriesModal).exists()).toBeTruthy();
        expect(wrapper.findComponent(CredentialsFormModal).exists()).toBeFalsy();

        wrapper.findComponent(ListRepositoriesModal).vm.$emit("to-back-button");
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(ListRepositoriesModal).exists()).toBeFalsy();
        expect(wrapper.findComponent(CredentialsFormModal).exists()).toBeTruthy();
    });

    it("When CredentialsFormModal emit on-get-gitlab-repositories with repositories, Then ListRepositoriesModal is displayed", async () => {
        const wrapper = instantiateComponent();

        expect(wrapper.findComponent(ListRepositoriesModal).exists()).toBeFalsy();
        expect(wrapper.findComponent(CredentialsFormModal).exists()).toBeTruthy();

        wrapper.findComponent(CredentialsFormModal).vm.$emit("on-get-gitlab-repositories", {
            repositories: [{ id: 10 }],
            token: "Azer7897",
            server_url: "https://example.com",
        });
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(ListRepositoriesModal).exists()).toBeTruthy();
        expect(wrapper.findComponent(ListRepositoriesModal).props("repositories")).toEqual([
            { id: 10 },
        ]);
        expect(wrapper.findComponent(CredentialsFormModal).exists()).toBeFalsy();
    });

    it("When ListRepositoriesModal emits on-success-close-modal, Then success message is displayed", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            gitlab_repositories: [
                { id: 10, label: "My Project", path_with_namespace: "my-path/my-project" },
            ],
            back_button_clicked: false,
        });

        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        // eslint-disable-next-line jest/prefer-spy-on
        wrapper.vm.$refs.listRepositoriesModal.reset = jest.fn();

        expect(wrapper.findComponent(ListRepositoriesModal).exists()).toBeTruthy();
        expect(wrapper.findComponent(CredentialsFormModal).exists()).toBeFalsy();

        wrapper.findComponent(ListRepositoriesModal).vm.$emit("on-success-close-modal", {
            repository: {
                id: 10,
                path_with_namespace: "my-path/my-project",
            },
        });
        await wrapper.vm.$nextTick();

        const success_message =
            "GitLab repository <strong>my-path/my-project</strong> has been successfully integrated!";
        expect(store.commit).toHaveBeenCalledWith("setSuccessMessage", success_message);
    });

    it("When CredentialsFormModal emits on-close-modal, Then form is reset", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            gitlab_repositories: null,
        });

        await wrapper.vm.$nextTick();

        // eslint-disable-next-line jest/prefer-spy-on
        wrapper.vm.$refs.credentialsForm.reset = jest.fn();

        wrapper.findComponent(CredentialsFormModal).vm.$emit("on-close-modal");
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$refs.credentialsForm.reset).toHaveBeenCalled();
    });
});
