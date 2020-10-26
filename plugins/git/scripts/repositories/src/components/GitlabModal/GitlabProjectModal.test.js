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
import GitlabProjectModal from "./GitlabProjectModal.vue";

describe("GitlabProjectModal", () => {
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
        return shallowMount(GitlabProjectModal, {
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

    it("When there is a success message, Then it's displayed", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            is_loading: false,
            gitlab_server: "https://example.com",
            gitlab_token_user: "AFREZF546",
            success_message: "Success message",
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=gitlab-success-load-projects]").text()).toEqual(
            "Success message"
        );
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
});
