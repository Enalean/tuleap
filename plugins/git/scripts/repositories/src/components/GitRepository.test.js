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

import { createStoreMock } from "../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import { shallowMount } from "@vue/test-utils";
import GitRepository from "./GitRepository.vue";
import localVue from "../support/local-vue.js";
import * as repositoryListPresenter from "../repository-list-presenter";
import PullRequestBadge from "./PullRequestBadge.vue";
import * as breadcrumPresenter from "./../breadcrumb-presenter";

describe("GitRepository", () => {
    let store_options, store, propsData;
    beforeEach(() => {
        jest.spyOn(repositoryListPresenter, "getUserIsAdmin").mockReturnValue(true);

        store_options = {
            state: {
                used_service_name: [],
            },
            getters: {
                isGitlabUsed: false,
                isFolderDisplayMode: true,
            },
        };
    });

    function instantiateComponent() {
        store = createStoreMock(store_options, { gitlab: {} });
        return shallowMount(GitRepository, {
            propsData,
            mocks: { $store: store },
            localVue,
        });
    }

    it("When user is git admin but repository comes from Gitlab, Then admin icon is displayed", () => {
        propsData = {
            repository: {
                id: 1,
                normalized_path: "MyPath/MyRepo",
                description: "This is my description.",
                path_without_project: "MyPath",
                label: "MyRepo",
                last_update_date: "2020-10-28T15:13:13+01:00",
                additional_information: [],
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/MyPath/MyRepo",
                    gitlab_repository_id: 1,
                },
            },
        };
        const wrapper = instantiateComponent();

        expect(wrapper.find("[data-test=git-repository-card-admin-link]").exists()).toBeFalsy();
        expect(
            wrapper.find("[data-test=git-repository-card-admin-unlink-gitlab]").exists()
        ).toBeTruthy();

        expect(wrapper.find("[data-test=dropdown-gitlab-administration-1]").exists()).toBeTruthy();
        expect(
            wrapper.find("[data-test=dropdown-gitlab-administration-menu-options]").exists()
        ).toBeTruthy();
    });

    it("When repository comes from Gitlab and there is a description, Then Gitlab icon and description are displayed", () => {
        propsData = {
            repository: {
                id: 1,
                normalized_path: "MyPath/MyRepo",
                description: "This is my description.",
                path_without_project: "MyPath",
                label: "MyRepo",
                last_update_date: "2020-10-28T15:13:13+01:00",
                additional_information: [],
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/MyPath/MyRepo",
                    gitlab_repository_id: 1,
                },
            },
        };
        const wrapper = instantiateComponent();

        expect(wrapper.find("[data-test=git-repository-card-description]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=git-repository-card-description]").text()).toEqual(
            "This is my description."
        );
        expect(wrapper.find("[data-test=git-repository-card-gitlab-icon]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=git-repository-card-gerrit-icon]").exists()).toBeFalsy();
    });

    it("When repository doesn't come from Gitlab and there is a description, Then only description is displayed", () => {
        propsData = {
            repository: {
                id: 1,
                description: "This is my description.",
                normalized_path: "",
                path_without_project: "",
                additional_information: [],
                label: "MyRepo",
                last_update_date: "2020-10-28T15:13:13+01:00",
                html_url: "https://example.com/MyPath/MyRepo",
            },
        };
        const wrapper = instantiateComponent();

        expect(wrapper.find("[data-test=git-repository-card-description]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=git-repository-card-description]").text()).toEqual(
            "This is my description."
        );
        expect(wrapper.find("[data-test=git-repository-card-gitlab-icon]").exists()).toBeFalsy();
    });

    it("When repository comes from Gitlab, Then PullRequestBadge is not displayed", () => {
        propsData = {
            repository: {
                id: 1,
                normalized_path: "MyPath/MyRepo",
                description: "This is my description.",
                path_without_project: "MyPath",
                label: "MyRepo",
                last_update_date: "2020-10-28T15:13:13+01:00",
                additional_information: {
                    opened_pull_requests: 2,
                },
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/MyPath/MyRepo",
                    gitlab_repository_id: 1,
                },
            },
        };
        const wrapper = instantiateComponent();

        expect(wrapper.findComponent(PullRequestBadge).exists()).toBeFalsy();
    });

    it("When repository is Git and there are some pull requests, Then PullRequestBadge is displayed", () => {
        propsData = {
            repository: {
                id: 1,
                normalized_path: "MyPath/MyRepo",
                description: "This is my description.",
                path_without_project: "MyPath",
                label: "MyRepo",
                last_update_date: "2020-10-28T15:13:13+01:00",
                additional_information: {
                    opened_pull_requests: 2,
                },
            },
        };
        const wrapper = instantiateComponent();

        expect(wrapper.findComponent(PullRequestBadge).exists()).toBeTruthy();
    });

    it("When repository is GitLab, Then gitlab_repository_url of gitlab is displayed", () => {
        propsData = {
            repository: {
                id: 1,
                normalized_path: "MyPath/MyRepo",
                description: "This is my description.",
                path_without_project: "MyPath",
                label: "MyRepo",
                last_update_date: "2020-10-28T15:13:13+01:00",
                additional_information: {
                    opened_pull_requests: 2,
                },
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/MyPath/MyRepo",
                    gitlab_repository_id: 1,
                },
            },
        };
        const wrapper = instantiateComponent();

        expect(wrapper.find("[data-test=git-repository-path]").attributes("href")).toEqual(
            "https://example.com/MyPath/MyRepo"
        );
    });

    it("When repository is Git, Then url to repository is displayed", () => {
        jest.spyOn(breadcrumPresenter, "getRepositoryListUrl").mockReturnValue("plugins/git/");

        propsData = {
            repository: {
                id: 1,
                normalized_path: "MyPath/MyRepo",
                description: "This is my description.",
                path_without_project: "MyPath",
                label: "MyRepo",
                last_update_date: "2020-10-28T15:13:13+01:00",
                additional_information: {
                    opened_pull_requests: 2,
                },
            },
        };
        const wrapper = instantiateComponent();

        expect(wrapper.find("[data-test=git-repository-path]").attributes("href")).toEqual(
            "plugins/git/MyPath/MyRepo"
        );
    });

    it("When repositories are not sorted by path, Then path is displayed behind label", () => {
        store_options.getters.isFolderDisplayMode = false;

        propsData = {
            repository: {
                id: 1,
                normalized_path: "MyPath/MyRepo",
                description: "This is my description.",
                path_without_project: "MyPath",
                label: "MyRepo",
                last_update_date: "2020-10-28T15:13:13+01:00",
                additional_information: {
                    opened_pull_requests: 2,
                },
            },
        };
        const wrapper = instantiateComponent();

        expect(wrapper.find("[data-test=repository_name]").text()).toContain("MyPath/");
        expect(wrapper.find("[data-test=repository_name]").text()).toContain("MyRepo");
    });

    it("When repositories are sorted by path, Then path is not displayed behind label", () => {
        propsData = {
            repository: {
                id: 1,
                normalized_path: "MyPath/MyRepo",
                description: "This is my description.",
                path_without_project: "MyPath",
                label: "MyRepo",
                last_update_date: "2020-10-28T15:13:13+01:00",
                additional_information: {
                    opened_pull_requests: 2,
                },
            },
        };
        const wrapper = instantiateComponent();

        expect(wrapper.find("[data-test=repository_name]").text()).not.toContain("MyPath/");
        expect(wrapper.find("[data-test=repository_name]").text()).toContain("MyRepo");
    });

    it("When repository is GitLab and user clicks to unlink, Then modal opens", async () => {
        const repository = {
            id: 1,
            normalized_path: "MyPath/MyRepo",
            description: "This is my description.",
            path_without_project: "MyPath",
            label: "MyRepo",
            last_update_date: "2020-10-28T15:13:13+01:00",
            additional_information: [],
            gitlab_data: {
                gitlab_repository_url: "https://example.com/MyPath/MyRepo",
                gitlab_repository_id: 1,
            },
        };
        propsData = { repository };

        const wrapper = instantiateComponent();

        wrapper.find("[data-test=unlink-gitlab-repository-1]").trigger("click");

        await wrapper.vm.$nextTick();

        expect(store.dispatch).toHaveBeenCalledWith(
            "gitlab/showDeleteGitlabRepositoryModal",
            repository
        );
    });

    it("When repository is GitLab and user clicks to edit token, Then modal opens", async () => {
        const repository = {
            id: 1,
            normalized_path: "MyPath/MyRepo",
            description: "This is my description.",
            path_without_project: "MyPath",
            label: "MyRepo",
            last_update_date: "2020-10-28T15:13:13+01:00",
            additional_information: [],
            gitlab_data: {
                gitlab_repository_url: "https://example.com/MyPath/MyRepo",
                gitlab_repository_id: 1,
            },
        };
        propsData = { repository };

        const wrapper = instantiateComponent();

        wrapper.find("[data-test=edit-access-token-gitlab-repository]").trigger("click");

        await wrapper.vm.$nextTick();

        expect(store.dispatch).toHaveBeenCalledWith(
            "gitlab/showEditAccessTokenGitlabRepositoryModal",
            repository
        );
    });

    it("When repository is GitLab and user clicks to regenerate webhook, Then modal opens", async () => {
        const repository = {
            id: 1,
            normalized_path: "MyPath/MyRepo",
            description: "This is my description.",
            path_without_project: "MyPath",
            label: "MyRepo",
            last_update_date: "2020-10-28T15:13:13+01:00",
            additional_information: [],
            gitlab_data: {
                gitlab_repository_url: "https://example.com/MyPath/MyRepo",
                gitlab_repository_id: 1,
            },
        };
        propsData = { repository };

        const wrapper = instantiateComponent();

        wrapper.find("[data-test=regenerate-webhook-gitlab-repository]").trigger("click");

        await wrapper.vm.$nextTick();

        expect(store.dispatch).toHaveBeenCalledWith(
            "gitlab/showRegenerateGitlabWebhookModal",
            repository
        );
    });

    it("When repository is GitLab and user clicks to update the allowing artifact closure value, Then modal opens", async () => {
        const repository = {
            id: 1,
            normalized_path: "MyPath/MyRepo",
            description: "This is my description.",
            path_without_project: "MyPath",
            label: "MyRepo",
            last_update_date: "2020-10-28T15:13:13+01:00",
            additional_information: [],
            gitlab_data: {
                gitlab_repository_url: "https://example.com/MyPath/MyRepo",
                gitlab_repository_id: 1,
            },
        };
        propsData = { repository };

        const wrapper = instantiateComponent();

        wrapper.find("[data-test=artifact-closure-gitlab-repository]").trigger("click");

        await wrapper.vm.$nextTick();

        expect(store.dispatch).toHaveBeenCalledWith("gitlab/showArtifactClosureModal", repository);
    });

    it("When repository is Git and handled by Gerrit, Then Gerrit icon and description are displayed", () => {
        propsData = {
            repository: {
                id: 1,
                normalized_path: "MyPath/MyRepo",
                description: "This is my description.",
                path_without_project: "MyPath",
                label: "MyRepo",
                last_update_date: "2020-10-28T15:13:13+01:00",
                additional_information: [],
                server: {
                    id: 1,
                    html_url: "https://example.com/MyPath/MyRepo",
                },
            },
        };
        const wrapper = instantiateComponent();

        expect(wrapper.find("[data-test=git-repository-card-description]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=git-repository-card-description]").text()).toEqual(
            "This is my description."
        );
        expect(wrapper.find("[data-test=git-repository-card-gerrit-icon]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=git-repository-card-gitlab-icon]").exists()).toBeFalsy();
    });
});
