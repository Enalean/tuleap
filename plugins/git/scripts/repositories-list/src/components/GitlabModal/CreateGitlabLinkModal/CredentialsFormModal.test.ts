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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import CredentialsFormModal from "./CredentialsFormModal.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import { jest } from "@jest/globals";

describe("CredentialsFormModal", () => {
    let store_options = {};
    let getGitlabProjectListSpy: jest.Mock;

    beforeEach(() => {
        getGitlabProjectListSpy = jest.fn();
        store_options = {
            state: {},
            getters: {},
            modules: {
                gitlab: {
                    namespaced: true,
                    actions: {
                        getGitlabProjectList: getGitlabProjectListSpy,
                    },
                },
            },
        };
    });

    function instantiateComponent(): VueWrapper<InstanceType<typeof CredentialsFormModal>> {
        return shallowMount(CredentialsFormModal, {
            props: {
                gitlab_api_token: "",
                server_url: "",
            },
            global: { ...getGlobalTestOptions(store_options) },
        });
    }

    it("When the user clicked on the button, Then the submit button is disabled and icon changed and api is called", async () => {
        const wrapper = instantiateComponent();
        expect(wrapper.find("[data-test=icon-spin]").classes()).toContain(
            "fa-long-arrow-alt-right",
        );

        wrapper.vm.is_loading = false;
        wrapper.vm.gitlab_server = "https://example.com";
        wrapper.vm.gitlab_token = "AFREZF546";

        wrapper.find("[data-test=fetch-gitlab-repository-modal-form]").trigger("submit.prevent");
        await jest.useFakeTimers();

        expect(wrapper.vm.disabled_button).toBeTruthy();
        expect(wrapper.find("[data-test=icon-spin]").classes()).toContain("fa-circle-notch");

        expect(getGitlabProjectListSpy).toHaveBeenCalledWith(expect.any(Object), {
            server_url: "https://example.com",
            token: "AFREZF546",
        });
    });

    it("When there is an error message, Then it's displayed", async () => {
        const wrapper = instantiateComponent();

        wrapper.vm.is_loading = false;
        wrapper.vm.gitlab_server = "https://example.com";
        wrapper.vm.gitlab_token = "AFREZF546";
        wrapper.vm.error_message = "Error message";
        await jest.useFakeTimers();

        expect(wrapper.find("[data-test=gitlab-fail-load-repositories]").text()).toBe(
            "Error message",
        );
    });

    it("When repositories have been retrieved, Then event is emitted with these repositories", async () => {
        const wrapper = instantiateComponent();
        getGitlabProjectListSpy.mockReturnValue(Promise.resolve([{ id: 10 }]));

        wrapper.vm.is_loading = false;
        wrapper.vm.gitlab_server = "https://example.com";
        wrapper.vm.gitlab_token = "AFREZF546";
        wrapper.vm.gitlab_projects = null;
        jest.useFakeTimers();

        await wrapper
            .find("[data-test=fetch-gitlab-repository-modal-form]")
            .trigger("submit.prevent");

        const on_get_gitlab_projects = wrapper.emitted()["on-get-gitlab-repositories"];
        if (!on_get_gitlab_projects) {
            throw new Error("Should have emitted on-get-gitlab-repositories");
        }

        expect(on_get_gitlab_projects[0]).toStrictEqual([
            {
                projects: [{ id: 10 }],
                server_url: "https://example.com",
                token: "AFREZF546",
            },
        ]);
    });

    it("When there are no token and server url, Then submit button is disabled", () => {
        const wrapper = instantiateComponent();
        wrapper.vm.is_loading = false;
        wrapper.vm.gitlab_server = "";
        wrapper.vm.gitlab_token = "";
        jest.useFakeTimers();

        expect(wrapper.vm.disabled_button).toBeTruthy();
    });

    it("When there aren't any repositories in Gitlab, Then empty message is displayed", async () => {
        const wrapper = instantiateComponent();

        wrapper.vm.is_loading = false;
        wrapper.vm.gitlab_server = "";
        wrapper.vm.gitlab_token = "";
        wrapper.vm.gitlab_projects = [];
        wrapper.vm.empty_message = "No repository is available with your GitLab account";
        await jest.useFakeTimers();

        expect(wrapper.find("[data-test=gitlab-empty-repositories]").text()).toBe(
            "No repository is available with your GitLab account",
        );
    });

    it("When user submit but credentials are not goods, Then error message is displayed", async () => {
        const wrapper = instantiateComponent();
        getGitlabProjectListSpy.mockReturnValue(Promise.resolve([{ id: 10 }]));

        wrapper.vm.is_loading = false;
        wrapper.vm.gitlab_server = "https://example.com";
        wrapper.vm.gitlab_token = "";
        wrapper.vm.gitlab_projects = null;
        jest.useFakeTimers();

        await wrapper
            .find("[data-test=fetch-gitlab-repository-modal-form]")
            .trigger("submit.prevent");

        expect(wrapper.find("[data-test=gitlab-fail-load-repositories]").text()).toBe(
            "You must provide a valid GitLab server and user API token",
        );
    });

    it("When user submit but server_url is not valid, Then error message is displayed", async () => {
        const wrapper = instantiateComponent();
        getGitlabProjectListSpy.mockReturnValue(Promise.resolve([{ id: 10 }]));

        wrapper.vm.is_loading = false;
        wrapper.vm.gitlab_server = "htt://example.com";
        wrapper.vm.gitlab_token = "Azer789";
        wrapper.vm.gitlab_projects = null;
        jest.useFakeTimers();

        await wrapper
            .find("[data-test=fetch-gitlab-repository-modal-form]")
            .trigger("submit.prevent");

        expect(wrapper.find("[data-test=gitlab-fail-load-repositories]").text()).toBe(
            "Server url is invalid",
        );
    });

    it("When api returns empty array, Then error message is displayed", async () => {
        const wrapper = instantiateComponent();
        getGitlabProjectListSpy.mockReturnValue(Promise.resolve(null));

        wrapper.vm.is_loading = false;
        wrapper.vm.gitlab_server = "https://example.com";
        wrapper.vm.gitlab_token = "AFREZF546";
        wrapper.vm.gitlab_projects = null;
        wrapper.vm.empty_message = "";
        jest.useFakeTimers();

        await wrapper
            .find("[data-test=fetch-gitlab-repository-modal-form]")
            .trigger("submit.prevent");

        expect(wrapper.vm.empty_message).toBe(
            "No repository is available with your GitLab account",
        );
    });
});
