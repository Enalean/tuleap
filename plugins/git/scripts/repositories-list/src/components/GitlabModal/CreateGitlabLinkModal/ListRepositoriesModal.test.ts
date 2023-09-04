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
import ListRepositoriesModal from "./ListRepositoriesModal.vue";
import * as repository_list_presenter from "../../../repository-list-presenter";
import { PROJECT_KEY } from "../../../constants";
import type { Store } from "@tuleap/vuex-store-wrapper-jest";
import VueDOMPurifyHTML from "vue-dompurify-html";
import GetTextPlugin from "vue-gettext";
import type { GitlabDataWithPath, GitlabProject } from "../../../type";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

describe("ListRepositoriesModal", () => {
    let store_options: {
            getters: { getGitlabRepositoriesIntegrated: GitlabDataWithPath[] };
        } = {
            getters: {
                getGitlabRepositoriesIntegrated: [],
            },
        },
        localVue,
        store: Store;

    beforeEach(() => {
        store_options = {
            getters: {
                getGitlabRepositoriesIntegrated: [],
            },
        };
    });

    function instantiateComponent(
        repositories_props_data: GitlabProject[],
    ): Wrapper<ListRepositoriesModal> {
        localVue = createLocalVue();
        localVue.use(VueDOMPurifyHTML);
        localVue.use(GetTextPlugin, {
            translations: {},
            silent: true,
        });

        store = createStoreMock(store_options, { gitlab: {} });
        return shallowMount(ListRepositoriesModal, {
            propsData: {
                gitlab_api_token: "AZERTY123",
                server_url: "example.com",
                repositories: repositories_props_data,
            },
            mocks: { $store: store },
            localVue,
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

    it("When no repository is selected, Then integrate button is disabled", async () => {
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

        wrapper.setData({
            selected_repository: null,
        });
        await wrapper.vm.$nextTick();

        expect(
            wrapper.find("[data-test=button-integrate-gitlab-repository]").attributes().disabled,
        ).toBeTruthy();

        wrapper.setData({
            selected_repository: { id: 10, path_with_namespace: "My Path / Repository" },
        });

        await wrapper.vm.$nextTick();

        expect(
            wrapper.find("[data-test=button-integrate-gitlab-repository]").attributes().disabled,
        ).toBeFalsy();
    });

    it("When user clicks on back button, Then event is emitted", async () => {
        const wrapper = instantiateComponent([]);

        wrapper.find("[data-test=gitlab-button-back]").trigger("click");

        await wrapper.vm.$nextTick();
        expect(wrapper.emitted("to-back-button")).toBeTruthy();
    });

    it("When user submit repository, Then api is queried, repositories are recovered, submit button is disabled, icon changed and success message is displayed", async () => {
        const wrapper = instantiateComponent([]);

        jest.spyOn(store, "dispatch").mockReturnValue(Promise.resolve());
        jest.spyOn(repository_list_presenter, "getProjectId").mockReturnValue(101);

        expect(wrapper.find("[data-test=icon-spin]").classes()).toContain(
            "fa-long-arrow-alt-right",
        );

        wrapper.setData({
            selected_repository: { id: 1 },
            is_loading: false,
            message_error_rest: "",
        });
        await wrapper.vm.$nextTick();

        wrapper.find("[data-test=select-gitlab-repository-modal-form]").trigger("submit.prevent");
        await wrapper.vm.$nextTick();

        expect(
            wrapper.find("[data-test=button-integrate-gitlab-repository]").attributes().disabled,
        ).toBeTruthy();
        expect(wrapper.find("[data-test=icon-spin]").classes()).toContain("fa-circle-notch");
        expect(store.commit).toHaveBeenCalledWith("resetRepositories");
        expect(store.dispatch).toHaveBeenCalledWith("changeRepositories", PROJECT_KEY);
        expect(wrapper.vm.$emit("on-success-close-modal", { repository: { id: 1 } })).toBeTruthy();
    });

    it("When error throw from API, Then error is displayed and button is disabled", async () => {
        const wrapper = instantiateComponent([]);

        jest.spyOn(store, "dispatch").mockReturnValue(
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

        wrapper.setData({
            selected_repository: { id: 1 },
            is_loading: false,
            message_error_rest: "",
        });

        wrapper.find("[data-test=select-gitlab-repository-modal-form]").trigger("submit.prevent");
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=gitlab-fail-post-repositories]").text()).toBe(
            "404: Error during post",
        );

        expect(
            wrapper.find("[data-test=button-integrate-gitlab-repository]").attributes("disabled"),
        ).toBeTruthy();
    });

    it("When repository is already integrated, Then button is disabled", () => {
        store_options.getters.getGitlabRepositoriesIntegrated = [
            {
                gitlab_data: {
                    gitlab_repository_id: 1,
                    gitlab_repository_url: "https://example.com/MyPath/1",
                    is_webhook_configured: true,
                },
                normalized_path: "My Path / Repository",
            },
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

        const wrapper = instantiateComponent(repositories);

        expect(wrapper.find("[data-test=gitlab-repositories-displayed-1]").classes()).toEqual([
            "gitlab-select-repository",
            "gitlab-select-repository-disabled",
        ]);
        expect(
            wrapper.find("[data-test=gitlab-repository-disabled-1]").attributes().disabled,
        ).toBeTruthy();
        expect(wrapper.find("[data-test=gitlab-repositories-displayed-2]").classes()).toEqual([
            "gitlab-select-repository",
        ]);
        expect(wrapper.find("[data-test=gitlab-repositories-tooltip-1]").classes()).toEqual([
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
        store_options.getters.getGitlabRepositoriesIntegrated = [
            {
                gitlab_data: {
                    gitlab_repository_id: 152,
                    gitlab_repository_url: "https://example.com/MyPath/152",
                    is_webhook_configured: true,
                },
                normalized_path: "my-path/repository",
            },
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

        const wrapper = instantiateComponent(repositories);

        expect(wrapper.find("[data-test=gitlab-repositories-displayed-1]").classes()).toEqual([
            "gitlab-select-repository",
            "gitlab-select-repository-disabled",
        ]);
        expect(
            wrapper.find("[data-test=gitlab-repository-disabled-1]").attributes().disabled,
        ).toBeTruthy();
        expect(wrapper.find("[data-test=gitlab-repositories-displayed-2]").classes()).toEqual([
            "gitlab-select-repository",
        ]);
        expect(wrapper.find("[data-test=gitlab-repositories-tooltip-1]").classes()).toEqual([
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

        wrapper.find("[data-test=gitlab-avatar-1]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.selected_repository.id).toBe(1);

        wrapper.find("[data-test=gitlab-label-path-2]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.selected_repository.id).toBe(2);
    });
});
