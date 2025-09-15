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
import ListRepositoriesModal from "./ListRepositoriesModal.vue";
import * as repository_list_presenter from "../../../repository-list-presenter";
import { PROJECT_KEY } from "../../../constants";
import type { GitlabProject, GitLabRepository } from "../../../type";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import { jest } from "@jest/globals";

jest.useFakeTimers();

describe("ListRepositoriesModal", () => {
    let postIntegrationGitlabSpy: jest.Mock;
    let resetRepositoriesSpy: jest.Mock;
    let changeRepositoriesSpy: jest.Mock;

    beforeEach(() => {
        postIntegrationGitlabSpy = jest.fn();
        resetRepositoriesSpy = jest.fn();
        changeRepositoriesSpy = jest.fn();
    });

    function instantiateComponent(
        repositories_props_data: GitlabProject[],
        getter_getGitlabRepositoriesIntegrated: GitLabRepository[] = [],
    ): VueWrapper<InstanceType<typeof ListRepositoriesModal>> {
        const store_options = {
            getters: {
                getGitlabRepositoriesIntegrated: (): GitLabRepository[] =>
                    getter_getGitlabRepositoriesIntegrated,
            },
            mutations: {
                resetRepositories: resetRepositoriesSpy,
            },
            actions: {
                changeRepositories: changeRepositoriesSpy,
            },
            modules: {
                gitlab: {
                    namespaced: true,
                    actions: {
                        postIntegrationGitlab: postIntegrationGitlabSpy,
                    },
                },
            },
        };

        return shallowMount(ListRepositoriesModal, {
            props: {
                gitlab_api_token: "AZERTY123",
                server_url: "example.com",
                repositories: repositories_props_data,
            },
            global: { ...getGlobalTestOptions(store_options) },
        });
    }

    it("When there are repositories, Then repositories are displayed", () => {
        const repositories = [
            {
                id: 10,
                name_with_namespace: "My Path / Repository",
                path_with_namespace: "my-path/repository",
            } as GitlabProject,
            {
                id: 11,
                name_with_namespace: "My Second / Repository",
                path_with_namespace: "my-second/repository",
                avatar_url: "example.com",
            } as GitlabProject,
        ];

        const wrapper = instantiateComponent(repositories);

        expect(wrapper.find("[data-test=gitlab-repositories-displayed-10]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=gitlab-repositories-displayed-11]").exists()).toBeTruthy();
    });

    it("When no repository is selected, Then integrate button is disabled", () => {
        const repositories = [
            {
                id: 10,
                name_with_namespace: "My Path / Repository",
                path_with_namespace: "my-path/repository",
            } as GitlabProject,
            {
                id: 11,
                name_with_namespace: "My Second / Repository",
                path_with_namespace: "my-second/repository",
                avatar_url: "example.com",
            } as GitlabProject,
        ];

        const wrapper = instantiateComponent(repositories);

        wrapper.vm.selected_repository = null;
        jest.useFakeTimers();

        expect(wrapper.vm.disabled_button).toBeTruthy();

        wrapper.vm.selected_repository = {
            id: 10,
            path_with_namespace: "My Path / Repository",
        } as GitlabProject;
        jest.useFakeTimers();

        expect(
            wrapper.find("[data-test=button-integrate-gitlab-repository]").attributes().disabled,
        ).toBeFalsy();
    });

    it("When user clicks on back button, Then event is emitted", async () => {
        const wrapper = instantiateComponent([]);

        await wrapper.find("[data-test=gitlab-button-back]").trigger("click");

        expect(wrapper.emitted("to-back-button")).toBeTruthy();
    });

    it("When user submit repository, Then api is queried, repositories are recovered, submit button is disabled, icon changed and success message is displayed", async () => {
        const wrapper = instantiateComponent([]);

        postIntegrationGitlabSpy.mockReturnValue(Promise.resolve());
        jest.spyOn(repository_list_presenter, "getProjectId").mockReturnValue(101);

        expect(wrapper.find("[data-test=icon-spin]").classes()).toContain(
            "fa-long-arrow-alt-right",
        );

        wrapper.vm.selected_repository = { id: 1 } as GitlabProject;
        wrapper.vm.is_loading = false;
        wrapper.vm.message_error_rest = "";
        jest.useFakeTimers();

        wrapper.find("[data-test=select-gitlab-repository-modal-form]").trigger("submit.prevent");
        await jest.useFakeTimers();

        expect(wrapper.vm.disabled_button).toBeTruthy();
        expect(wrapper.find("[data-test=icon-spin]").classes()).toContain("fa-circle-notch");
        await jest.runOnlyPendingTimersAsync();
        expect(resetRepositoriesSpy).toHaveBeenCalled();
        expect(changeRepositoriesSpy).toHaveBeenCalledWith(expect.any(Object), PROJECT_KEY);
        expect(wrapper.emitted()["on-success-close-modal"]).toStrictEqual([
            [{ repository: { id: 1 } }],
        ]);
    });

    it("When error throw from API, Then error is displayed and button is disabled", async () => {
        const wrapper = instantiateComponent([]);

        postIntegrationGitlabSpy.mockReturnValue(
            Promise.reject(
                new FetchWrapperError("Not found", {
                    json(): Promise<{ error: { code: number; message: string } }> {
                        return Promise.resolve({
                            error: {
                                code: 404,
                                message: "Error during post",
                            },
                        });
                    },
                } as Response),
            ),
        );

        wrapper.vm.selected_repository = { id: 1 } as GitlabProject;
        wrapper.vm.is_loading = false;
        wrapper.vm.message_error_rest = "";
        jest.useFakeTimers();

        wrapper.find("[data-test=select-gitlab-repository-modal-form]").trigger("submit.prevent");
        await jest.runOnlyPendingTimersAsync();

        expect(wrapper.find("[data-test=gitlab-fail-post-repositories]").text()).toBe(
            "404: Error during post",
        );

        expect(wrapper.vm.disabled_button).toBeTruthy();
    });

    it("When repository is already integrated, Then button is disabled", () => {
        const getters_getGitlabRepositoriesIntegrated = [
            {
                gitlab_data: {
                    gitlab_repository_id: 1,
                    gitlab_repository_url: "https://example.com/MyPath/1",
                    is_webhook_configured: true,
                },
                normalized_path: "My Path / Repository",
            } as unknown as GitLabRepository,
        ];

        const repositories = [
            {
                id: 1,
                name_with_namespace: "My Path / Repository",
                path_with_namespace: "my-path/repository",
                web_url: "https://example.com/MyPath/1",
            } as GitlabProject,
            {
                id: 2,
                name_with_namespace: "My Second / Repository",
                path_with_namespace: "my-second/repository",
                avatar_url: "example.com",
                web_url: "https://example.com/MySecond/2",
            } as GitlabProject,
        ];

        const wrapper = instantiateComponent(repositories, getters_getGitlabRepositoriesIntegrated);

        expect(wrapper.find("[data-test=gitlab-repositories-displayed-1]").classes()).toStrictEqual(
            ["gitlab-select-repository-disabled", "gitlab-select-repository"],
        );
        expect(wrapper.vm.disabled_button).toBeTruthy();
        expect(wrapper.find("[data-test=gitlab-repositories-displayed-2]").classes()).toStrictEqual(
            ["gitlab-select-repository"],
        );
        expect(wrapper.find("[data-test=gitlab-repositories-tooltip-1]").classes()).toStrictEqual([
            "gitlab-tooltip-name",
            "tlp-tooltip",
            "tlp-tooltip-top",
        ]);
        expect(
            wrapper
                .find("[data-test=gitlab-repositories-tooltip-1]")
                .attributes("data-tlp-tooltip"),
        ).toBe("This repository is already integrated.");
    });

    it("When repository with same namepath and another instance is already integrated, Then button is disabled", () => {
        const getters_getGitlabRepositoriesIntegrated = [
            {
                gitlab_data: {
                    gitlab_repository_id: 152,
                    gitlab_repository_url: "https://example.com/MyPath/152",
                    is_webhook_configured: true,
                },
                normalized_path: "my-path/repository",
            } as unknown as GitLabRepository,
        ];

        const repositories = [
            {
                id: 1,
                name_with_namespace: "My Path / Repository",
                path_with_namespace: "my-path/repository",
                web_url: "https://another.instance.example.com/MyPath/1",
            } as GitlabProject,
            {
                id: 2,
                name_with_namespace: "My Second / Repository",
                path_with_namespace: "my-second/repository",
                avatar_url: "example.com",
                web_url: "https://example.com/MySecond/2",
            } as GitlabProject,
        ];

        const wrapper = instantiateComponent(repositories, getters_getGitlabRepositoriesIntegrated);

        expect(wrapper.find("[data-test=gitlab-repositories-displayed-1]").classes()).toStrictEqual(
            ["gitlab-select-repository-disabled", "gitlab-select-repository"],
        );
        expect(wrapper.vm.disabled_button).toBeTruthy();
        expect(wrapper.find("[data-test=gitlab-repositories-displayed-2]").classes()).toStrictEqual(
            ["gitlab-select-repository"],
        );
        expect(wrapper.find("[data-test=gitlab-repositories-tooltip-1]").classes()).toStrictEqual([
            "gitlab-tooltip-name",
            "tlp-tooltip",
            "tlp-tooltip-top",
        ]);
        expect(
            wrapper
                .find("[data-test=gitlab-repositories-tooltip-1]")
                .attributes("data-tlp-tooltip"),
        ).toBe("A repository with same name and path was already integrated.");
    });

    it("When user clicks on avatar or path, Then repository is selected", async () => {
        const repositories = [
            {
                id: 1,
                name_with_namespace: "My Path / Repository",
                path_with_namespace: "my-path/repository",
                web_url: "https://example.com/MyPath/1",
            } as GitlabProject,
            {
                id: 2,
                name_with_namespace: "My Second / Repository",
                path_with_namespace: "my-second/repository",
                avatar_url: "example.com",
                web_url: "https://example.com/MySecond/2",
            } as GitlabProject,
        ];

        const wrapper = instantiateComponent(repositories);

        await wrapper.find("[data-test=gitlab-avatar-1]").trigger("click");

        expect(wrapper.vm.selected_repository?.id).toBe(1);

        await wrapper.find("[data-test=gitlab-label-path-2]").trigger("click");

        expect(wrapper.vm.selected_repository?.id).toBe(2);
    });
});
