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

import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../support/local-vue.js";
import CredentialsFormModal from "./CredentialsFormModal.vue";

describe("CredentialsFormModal", () => {
    let store_options, store;
    beforeEach(() => {
        store_options = {
            state: {},
            getters: {},
        };
    });

    function instantiateComponent() {
        store = createStoreMock(store_options);
        return shallowMount(CredentialsFormModal, {
            mocks: { $store: store },
            localVue,
        });
    }

    it("When the user clicked on the button, Then the submit button is disabled and icon changed and api is called", async () => {
        const wrapper = instantiateComponent();
        expect(wrapper.find("[data-test=icon-spin]").classes()).toContain("fa-arrow-right");

        wrapper.setData({
            is_loading: false,
            gitlab_server: "https://example.com",
            gitlab_token_user: "AFREZF546",
        });

        await wrapper.vm.$nextTick();

        wrapper.find("[data-test=fetch-gitlab-project-modal-form]").trigger("submit.prevent");
        await wrapper.vm.$nextTick();

        expect(
            wrapper.find("[data-test=button_add_gitlab_project]").attributes().disabled
        ).toBeTruthy();
        expect(wrapper.find("[data-test=icon-spin]").classes()).toContain("fa-sync-alt");

        expect(store.dispatch).toHaveBeenCalledWith("getGitlabProjectList", {
            server_url: "https://example.com",
            token: "AFREZF546",
        });
    });

    it("When there is an error message, Then it's displayed", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            is_loading: false,
            gitlab_server: "https://example.com",
            gitlab_token_user: "AFREZF546",
            error_message: "Error message",
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=gitlab-fail-load-projects]").text()).toEqual(
            "Error message"
        );
    });

    it("When projects have been retrieved, Then event is emitted with this projects", async () => {
        const wrapper = instantiateComponent();
        jest.spyOn(store, "dispatch").mockReturnValue(Promise.resolve([{ id: 10 }]));

        wrapper.setData({
            is_loading: false,
            gitlab_server: "https://example.com",
            gitlab_token_user: "AFREZF546",
            gitlab_projects: null,
        });

        await wrapper.vm.$nextTick();

        wrapper.find("[data-test=fetch-gitlab-project-modal-form]").trigger("submit.prevent");
        await wrapper.vm.$nextTick();

        expect(wrapper.emitted("on-get-projects")[0][0]).toEqual([{ id: 10 }]);
    });

    it("When there are no token and server url, Then submit button is disabled", async () => {
        const wrapper = instantiateComponent();
        wrapper.setData({
            is_loading: false,
            gitlab_server: "",
            gitlab_token_user: "",
        });
        await wrapper.vm.$nextTick();
        expect(
            wrapper.find("[data-test=button_add_gitlab_project]").attributes().disabled
        ).toBeTruthy();
    });

    it("When there are not projects in GitLab, Then empty message is displayed", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            is_loading: false,
            gitlab_server: "",
            gitlab_token_user: "",
            gitlab_projects: [],
            empty_message: "No project is available with your GitLab account",
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=gitlab-empty-projects]").text()).toEqual(
            "No project is available with your GitLab account"
        );
    });
});
