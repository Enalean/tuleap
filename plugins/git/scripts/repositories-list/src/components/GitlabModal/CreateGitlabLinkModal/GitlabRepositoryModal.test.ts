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

import type { Store } from "@tuleap/vuex-store-wrapper-jest";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import GitlabRepositoryModal from "./GitlabRepositoryModal.vue";
import ListRepositoriesModal from "./ListRepositoriesModal.vue";
import CredentialsFormModal from "./CredentialsFormModal.vue";
import { createLocalVueForTests } from "../../../helpers/local-vue-for-tests";
import type Vue from "vue";

type GitlabRepositoryModalExposed = {
    credentialsForm: InstanceType<typeof CredentialsFormModal>;
    listRepositories: InstanceType<typeof ListRepositoriesModal>;
};

const noop = (): void => {
    // Do nothing
};

describe("GitlabRepositoryModal", () => {
    let store_options = {},
        store: Store;

    beforeEach(() => {
        store_options = {
            state: {},
            getters: {},
            mutations: { setSuccessMessage: noop },
        };
    });

    async function instantiateComponent(): Promise<Wrapper<Vue & GitlabRepositoryModalExposed>> {
        store = createStoreMock(store_options);
        return shallowMount(GitlabRepositoryModal, {
            mocks: { $store: store },
            localVue: await createLocalVueForTests(),
        }) as Wrapper<Vue & GitlabRepositoryModalExposed>;
    }

    it("When a user displays the modal ,then the CredentialsFormModal is displayed", async () => {
        const wrapper = await instantiateComponent();

        await wrapper.setData({
            gitlab_projects: null,
            back_button_clicked: false,
        });

        expect(wrapper.findComponent(ListRepositoriesModal).exists()).toBeFalsy();
        expect(wrapper.findComponent(CredentialsFormModal).exists()).toBeTruthy();
    });

    it("When user have clicked on back button, Then CredentialsFormModal is displayed", async () => {
        const wrapper = await instantiateComponent();

        await wrapper.setData({
            gitlab_projects: [{ id: 10 }],
            back_button_clicked: true,
        });

        expect(wrapper.findComponent(ListRepositoriesModal).exists()).toBeFalsy();
        expect(wrapper.findComponent(CredentialsFormModal).exists()).toBeTruthy();
    });

    it("When repositories have been retrieved, Then ListRepositoriesModal is displayed", async () => {
        const wrapper = await instantiateComponent();

        await wrapper.setData({
            gitlab_projects: [{ id: 10 }],
        });

        expect(wrapper.findComponent(ListRepositoriesModal).exists()).toBeTruthy();
        expect(wrapper.findComponent(CredentialsFormModal).exists()).toBeFalsy();
    });

    it("When ListRepositoriesModal emit to-back-button, Then CredentialsFormModal is displayed", async () => {
        const wrapper = await instantiateComponent();

        await wrapper.setData({
            gitlab_projects: [{ id: 10 }],
        });

        expect(wrapper.findComponent(ListRepositoriesModal).exists()).toBeTruthy();
        expect(wrapper.findComponent(CredentialsFormModal).exists()).toBeFalsy();

        wrapper.findComponent(ListRepositoriesModal).vm.$emit("to-back-button");
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(ListRepositoriesModal).exists()).toBeFalsy();
        expect(wrapper.findComponent(CredentialsFormModal).exists()).toBeTruthy();
    });

    it("When CredentialsFormModal emit on-get-gitlab-repositories with repositories, Then ListRepositoriesModal is displayed", async () => {
        const wrapper = await instantiateComponent();

        expect(wrapper.findComponent(ListRepositoriesModal).exists()).toBeFalsy();
        expect(wrapper.findComponent(CredentialsFormModal).exists()).toBeTruthy();

        wrapper.findComponent(CredentialsFormModal).vm.$emit("on-get-gitlab-repositories", {
            projects: [{ id: 10 }],
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
        const wrapper = await instantiateComponent();

        await wrapper.setData({
            gitlab_projects: [
                { id: 10, label: "My Project", path_with_namespace: "my-path/my-project" },
            ],
            back_button_clicked: false,
        });
        wrapper.vm.listRepositories = { reset: noop } as unknown as InstanceType<
            typeof ListRepositoriesModal
        >;

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
            "GitLab repository my-path/my-project has been successfully integrated!";
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("setSuccessMessage", success_message);
    });

    it("When CredentialsFormModal emits on-close-modal, Then form and list of repositories are reset", async () => {
        const wrapper = await instantiateComponent();

        await wrapper.setData({
            gitlab_projects: null,
        });

        const reset_credentials_form = jest.fn();
        wrapper.vm.credentialsForm = { reset: reset_credentials_form } as unknown as InstanceType<
            typeof CredentialsFormModal
        >;

        const reset_list_repositories_modal = jest.fn();
        wrapper.vm.listRepositories = {
            reset: reset_list_repositories_modal,
        } as unknown as InstanceType<typeof ListRepositoriesModal>;

        wrapper.findComponent(CredentialsFormModal).vm.$emit("on-close-modal");
        await wrapper.vm.$nextTick();

        expect(reset_credentials_form).toHaveBeenCalled();
        expect(reset_list_repositories_modal).toHaveBeenCalled();
    });

    it("When ListRepositoriesModal emits on-close-modal, Then form and list of repositories are reset", async () => {
        const wrapper = await instantiateComponent();

        await wrapper.setData({
            gitlab_projects: null,
        });

        const reset_credentials_form = jest.fn();
        wrapper.vm.credentialsForm = { reset: reset_credentials_form } as unknown as InstanceType<
            typeof CredentialsFormModal
        >;

        const reset_list_repositories_modal = jest.fn();
        wrapper.vm.listRepositories = {
            reset: reset_list_repositories_modal,
        } as unknown as InstanceType<typeof ListRepositoriesModal>;

        wrapper.findComponent(CredentialsFormModal).vm.$emit("on-close-modal");
        await wrapper.vm.$nextTick();

        expect(reset_credentials_form).toHaveBeenCalled();
        expect(reset_list_repositories_modal).toHaveBeenCalled();
    });
});
