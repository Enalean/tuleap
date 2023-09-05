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

import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Wrapper } from "@vue/test-utils";
import { createLocalVue, shallowMount } from "@vue/test-utils";
import CredentialsFormModal from "./CredentialsFormModal.vue";
import type { Store } from "@tuleap/vuex-store-wrapper-jest";
import VueDOMPurifyHTML from "vue-dompurify-html";
import GetTextPlugin from "vue-gettext";

describe("CredentialsFormModal", () => {
    let store_options = {},
        localVue,
        store: Store;

    beforeEach(() => {
        store_options = {
            state: {},
            getters: {},
        };
    });

    function instantiateComponent(): Wrapper<CredentialsFormModal> {
        localVue = createLocalVue();
        localVue.use(VueDOMPurifyHTML);
        localVue.use(GetTextPlugin, {
            translations: {},
            silent: true,
        });

        store = createStoreMock(store_options, { gitlab: {} });

        return shallowMount(CredentialsFormModal, {
            propsData: {
                gitlab_api_token: "",
                server_url: "",
            },
            mocks: { $store: store },
            localVue,
        });
    }

    it("When the user clicked on the button, Then the submit button is disabled and icon changed and api is called", async () => {
        const wrapper = instantiateComponent();
        expect(wrapper.find("[data-test=icon-spin]").classes()).toContain(
            "fa-long-arrow-alt-right",
        );

        wrapper.setData({
            is_loading: false,
            gitlab_server: "https://example.com",
            gitlab_token: "AFREZF546",
        });

        await wrapper.vm.$nextTick();

        wrapper.find("[data-test=fetch-gitlab-repository-modal-form]").trigger("submit.prevent");
        await wrapper.vm.$nextTick();

        expect(
            wrapper.find("[data-test=button-add-gitlab-repository]").attributes().disabled,
        ).toBeTruthy();
        expect(wrapper.find("[data-test=icon-spin]").classes()).toContain("fa-circle-notch");

        expect(store.dispatch).toHaveBeenCalledWith("gitlab/getGitlabProjectList", {
            server_url: "https://example.com",
            token: "AFREZF546",
        });
    });

    it("When there is an error message, Then it's displayed", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            is_loading: false,
            gitlab_server: "https://example.com",
            gitlab_token: "AFREZF546",
            error_message: "Error message",
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=gitlab-fail-load-repositories]").text()).toBe(
            "Error message",
        );
    });

    it("When repositories have been retrieved, Then event is emitted with these repositories", async () => {
        const wrapper = instantiateComponent();
        jest.spyOn(store, "dispatch").mockReturnValue(Promise.resolve([{ id: 10 }]));

        wrapper.setData({
            is_loading: false,
            gitlab_server: "https://example.com",
            gitlab_token: "AFREZF546",
            gitlab_projects: null,
        });

        await wrapper.vm.$nextTick();

        wrapper.find("[data-test=fetch-gitlab-repository-modal-form]").trigger("submit.prevent");
        await wrapper.vm.$nextTick();

        const on_get_gitlab_projects = wrapper.emitted()["on-get-gitlab-repositories"];
        if (!on_get_gitlab_projects) {
            throw new Error("Should have emitted on-get-gitlab-repositories");
        }

        expect(on_get_gitlab_projects[0]).toEqual([
            {
                projects: [{ id: 10 }],
                server_url: "https://example.com",
                token: "AFREZF546",
            },
        ]);
    });

    it("When there are no token and server url, Then submit button is disabled", async () => {
        const wrapper = instantiateComponent();
        wrapper.setData({
            is_loading: false,
            gitlab_server: "",
            gitlab_token: "",
        });
        await wrapper.vm.$nextTick();
        expect(
            wrapper.find("[data-test=button-add-gitlab-repository]").attributes().disabled,
        ).toBeTruthy();
    });

    it("When there aren't any repositories in Gitlab, Then empty message is displayed", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            is_loading: false,
            gitlab_server: "",
            gitlab_token: "",
            gitlab_projects: [],
            empty_message: "No repository is available with your GitLab account",
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=gitlab-empty-repositories]").text()).toBe(
            "No repository is available with your GitLab account",
        );
    });

    it("When user submit but credentials are not goods, Then error message is displayed", async () => {
        const wrapper = instantiateComponent();
        jest.spyOn(store, "dispatch").mockReturnValue(Promise.resolve([{ id: 10 }]));

        wrapper.setData({
            is_loading: false,
            gitlab_server: "https://example.com",
            gitlab_token: "",
            gitlab_projects: null,
        });

        await wrapper.vm.$nextTick();

        wrapper.find("[data-test=fetch-gitlab-repository-modal-form]").trigger("submit.prevent");
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=gitlab-fail-load-repositories]").text()).toBe(
            "You must provide a valid GitLab server and user API token",
        );
    });

    it("When user submit but server_url is not valid, Then error message is displayed", async () => {
        const wrapper = instantiateComponent();
        jest.spyOn(store, "dispatch").mockReturnValue(Promise.resolve([{ id: 10 }]));

        wrapper.setData({
            is_loading: false,
            gitlab_server: "htt://example.com",
            gitlab_token: "Azer789",
            gitlab_projects: null,
        });

        await wrapper.vm.$nextTick();

        wrapper.find("[data-test=fetch-gitlab-repository-modal-form]").trigger("submit.prevent");
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=gitlab-fail-load-repositories]").text()).toBe(
            "Server url is invalid",
        );
    });

    it("When api returns empty array, Then error message is displayed", async () => {
        const wrapper = instantiateComponent();
        jest.spyOn(store, "dispatch").mockReturnValue(Promise.resolve([]));

        wrapper.setData({
            is_loading: false,
            gitlab_server: "https://example.com",
            gitlab_token: "AFREZF546",
            gitlab_projects: null,
            empty_message: "",
        });

        await wrapper.vm.$nextTick();

        wrapper.find("[data-test=fetch-gitlab-repository-modal-form]").trigger("submit.prevent");
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.empty_message).toBe(
            "No repository is available with your GitLab account",
        );
    });
});
