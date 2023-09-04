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

import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Wrapper } from "@vue/test-utils";
import { createLocalVue, shallowMount } from "@vue/test-utils";
import EditAccessTokenGitlabModal from "./EditAccessTokenGitlabModal.vue";
import VueDOMPurifyHTML from "vue-dompurify-html";
import GetTextPlugin from "vue-gettext";
import AccessTokenFormModal from "./AccessTokenFormModal.vue";
import ConfirmReplaceTokenModal from "./ConfirmReplaceTokenModal.vue";
import type { Store } from "@tuleap/vuex-store-wrapper-jest";

describe("EditAccessTokenGitlabModal", () => {
    let store: Store, localVue;

    function instantiateComponent(): Wrapper<EditAccessTokenGitlabModal> {
        store = createStoreMock({}, { gitlab: {} });
        localVue = createLocalVue();
        localVue.use(VueDOMPurifyHTML);
        localVue.use(GetTextPlugin, {
            translations: {},
            silent: true,
        });

        return shallowMount(EditAccessTokenGitlabModal, {
            mocks: { $store: store },
            localVue,
        });
    }

    it("When a user displays the modal ,then the AccessTokenFormModal is displayed", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            repository: {
                id: 10,
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(AccessTokenFormModal).exists()).toBeTruthy();
    });

    it("When CredentialsFormModal emits on-close-modal, Then form is reset", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            repository: {
                id: 10,
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.repository).toEqual({ id: 10 });

        wrapper.findComponent(AccessTokenFormModal).vm.$emit("on-close-modal");
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.repository).toBeNull();
    });

    it("When CredentialsFormModal emits on-get-new-token-gitlab, Then ConfirmReplaceTokenModal is rendered", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            repository: {
                id: 10,
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(AccessTokenFormModal).exists()).toBeTruthy();
        wrapper
            .findComponent(AccessTokenFormModal)
            .vm.$emit("on-get-new-token-gitlab", { token: "azert123" });
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(AccessTokenFormModal).exists()).toBeFalsy();
        expect(wrapper.findComponent(ConfirmReplaceTokenModal).exists()).toBeTruthy();
        expect(wrapper.findComponent(ConfirmReplaceTokenModal).attributes().gitlab_new_token).toBe(
            "azert123",
        );
    });

    it("When ConfirmReplaceTokenModal emits on-success-edit-token, Then data are reset and success message is displayed", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            },
            gitlab_new_token: "azert123",
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(AccessTokenFormModal).exists()).toBeFalsy();
        expect(wrapper.findComponent(ConfirmReplaceTokenModal).exists()).toBeTruthy();

        wrapper.findComponent(ConfirmReplaceTokenModal).vm.$emit("on-success-edit-token");
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.repository).toBeNull();
        expect(wrapper.vm.$data.gitlab_new_token).toBe("");

        expect(store.commit).toHaveBeenCalledWith(
            "setSuccessMessage",
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
        };

        wrapper.setData({
            repository,
            gitlab_new_token: "azert123",
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(AccessTokenFormModal).exists()).toBeFalsy();
        expect(wrapper.findComponent(ConfirmReplaceTokenModal).exists()).toBeTruthy();

        wrapper.findComponent(ConfirmReplaceTokenModal).vm.$emit("on-back-button");
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(AccessTokenFormModal).exists()).toBeTruthy();
        expect(wrapper.findComponent(AccessTokenFormModal).attributes().gitlab_token).toBe(
            "azert123",
        );

        expect(wrapper.findComponent(ConfirmReplaceTokenModal).exists()).toBeFalsy();

        expect(wrapper.vm.$data.repository).toEqual(repository);
        expect(wrapper.vm.$data.gitlab_new_token).toBe("azert123");
    });
});
