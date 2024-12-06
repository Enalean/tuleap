/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import EditAccessTokenGitlabModal from "./EditAccessTokenGitlabModal.vue";
import AccessTokenFormModal from "./AccessTokenFormModal.vue";
import ConfirmReplaceTokenModal from "./ConfirmReplaceTokenModal.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import type { Repository } from "../../../type";
import { jest } from "@jest/globals";

jest.useFakeTimers();

describe("EditAccessTokenGitlabModal", () => {
    let store_options = {};
    let setEditAccessTokenGitlabRepositoryModalSpy: jest.Mock;
    let setSuccessMessageSpy: jest.Mock;

    beforeEach(() => {
        setEditAccessTokenGitlabRepositoryModalSpy = jest.fn();
        setSuccessMessageSpy = jest.fn();
        store_options = {
            mutations: {
                setSuccessMessage: setSuccessMessageSpy,
            },
            modules: {
                gitlab: {
                    namespaced: true,
                    mutations: {
                        setEditAccessTokenGitlabRepositoryModal:
                            setEditAccessTokenGitlabRepositoryModalSpy,
                    },
                },
            },
        };
    });

    function instantiateComponent(): VueWrapper<InstanceType<typeof EditAccessTokenGitlabModal>> {
        return shallowMount(EditAccessTokenGitlabModal, {
            global: { ...getGlobalTestOptions(store_options) },
        });
    }

    it("When a user displays the modal ,then the AccessTokenFormModal is displayed", async () => {
        const wrapper = instantiateComponent();

        wrapper.vm.repository = {
            id: 10,
        } as Repository;
        await jest.useFakeTimers();

        expect(wrapper.findComponent(AccessTokenFormModal).exists()).toBeTruthy();
    });

    it("When CredentialsFormModal emits on-close-modal, Then form is reset", async () => {
        const wrapper = instantiateComponent();

        wrapper.vm.repository = {
            id: 10,
        } as Repository;
        await jest.useFakeTimers();

        expect(wrapper.vm.repository).toStrictEqual({ id: 10 });

        wrapper.findComponent(AccessTokenFormModal).vm.$emit("on-close-modal");
        jest.useFakeTimers();

        expect(wrapper.vm.repository).toBeNull();
    });

    it("When CredentialsFormModal emits on-get-new-token-gitlab, Then ConfirmReplaceTokenModal is rendered", async () => {
        const wrapper = instantiateComponent();

        wrapper.vm.repository = {
            id: 10,
        } as Repository;
        await jest.useFakeTimers();

        expect(wrapper.findComponent(AccessTokenFormModal).exists()).toBeTruthy();
        wrapper
            .findComponent(AccessTokenFormModal)
            .vm.$emit("on-get-new-token-gitlab", { token: "azert123" });
        await jest.useFakeTimers();

        expect(wrapper.findComponent(AccessTokenFormModal).exists()).toBeFalsy();
        expect(wrapper.findComponent(ConfirmReplaceTokenModal).exists()).toBeTruthy();
        expect(wrapper.findComponent(ConfirmReplaceTokenModal).attributes().gitlab_new_token).toBe(
            "azert123",
        );
    });

    it("When ConfirmReplaceTokenModal emits on-success-edit-token, Then data are reset and success message is displayed", async () => {
        const wrapper = instantiateComponent();

        wrapper.vm.repository = {
            gitlab_data: {
                gitlab_repository_url: "https://example.com/my/repo",
                gitlab_repository_id: 12,
            },
            normalized_path: "my/repo",
        } as Repository;
        wrapper.vm.gitlab_new_token = "azert123";
        await jest.useFakeTimers();

        expect(wrapper.findComponent(AccessTokenFormModal).exists()).toBeFalsy();
        expect(wrapper.findComponent(ConfirmReplaceTokenModal).exists()).toBeTruthy();

        wrapper.findComponent(ConfirmReplaceTokenModal).vm.$emit("on-success-edit-token");
        jest.useFakeTimers();

        expect(wrapper.vm.repository).toBeNull();
        expect(wrapper.vm.gitlab_new_token).toBe("");

        expect(setSuccessMessageSpy).toHaveBeenCalledWith(
            expect.any(Object),
            "Token of GitLab repository my/repo has been successfully updated.",
        );
    });

    it("When ConfirmReplaceTokenModal emits on-back-button, Then CredentialsFormModal is displayed with token", async () => {
        const wrapper = instantiateComponent();

        const repository = {
            gitlab_data: {
                gitlab_repository_url: "https://example.com/my/repo",
                gitlab_repository_id: 12,
            },
            normalized_path: "my/repo",
        } as Repository;
        wrapper.vm.repository = repository;
        wrapper.vm.gitlab_new_token = "azert123";
        await jest.useFakeTimers();

        expect(wrapper.findComponent(AccessTokenFormModal).exists()).toBeFalsy();
        expect(wrapper.findComponent(ConfirmReplaceTokenModal).exists()).toBeTruthy();

        wrapper.findComponent(ConfirmReplaceTokenModal).vm.$emit("on-back-button");
        await jest.useFakeTimers();

        expect(wrapper.findComponent(AccessTokenFormModal).exists()).toBeTruthy();
        expect(wrapper.findComponent(ConfirmReplaceTokenModal).exists()).toBeFalsy();

        expect(wrapper.vm.repository).toStrictEqual(repository);
        expect(wrapper.vm.gitlab_new_token).toBe("azert123");
    });
});
