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
import GitRepository from "./GitRepository.vue";
import * as repositoryListPresenter from "../repository-list-presenter";
import PullRequestBadge from "./PullRequestBadge.vue";
import * as breadcrumbPresenter from "./../breadcrumb-presenter";
import type { FormattedGitLabRepository, Repository, State } from "../type";
import TimeAgo from "javascript-time-ago";
import time_ago_english from "javascript-time-ago/locale/en";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";

describe("GitRepository", () => {
    let propsData = {
        repository: {} as unknown as Repository | FormattedGitLabRepository,
    };

    beforeEach(() => {
        TimeAgo.locale(time_ago_english);
        jest.spyOn(repositoryListPresenter, "getUserIsAdmin").mockReturnValue(true);
        jest.spyOn(repositoryListPresenter, "getDashCasedLocale").mockReturnValue("en-US");
    });

    function instantiateComponent(
        is_folder_display_mode = true,
    ): VueWrapper<InstanceType<typeof GitRepository>> {
        const store_options = {
            state: {} as State,
            getters: {
                isGitlabUsed: (): boolean => false,
                isFolderDisplayMode: (): boolean => is_folder_display_mode,
            },
        };

        return shallowMount(GitRepository, {
            global: { ...getGlobalTestOptions(store_options) },
            props: {
                repository: propsData.repository,
            },
        });
    }

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
            } as unknown as Repository | FormattedGitLabRepository,
        };
        const wrapper = instantiateComponent();

        expect(wrapper.find("[data-test=git-repository-card-description]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=git-repository-card-description]").text()).toBe(
            "This is my description.",
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
            } as unknown as Repository | FormattedGitLabRepository,
        };
        const wrapper = instantiateComponent();

        expect(wrapper.find("[data-test=git-repository-card-description]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=git-repository-card-description]").text()).toBe(
            "This is my description.",
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
            } as unknown as Repository | FormattedGitLabRepository,
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
            } as unknown as Repository | FormattedGitLabRepository,
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
            } as unknown as Repository | FormattedGitLabRepository,
        };
        const wrapper = instantiateComponent();

        expect(wrapper.find("[data-test=git-repository-path]").attributes("href")).toBe(
            "https://example.com/MyPath/MyRepo",
        );
    });

    it("When repository is Git, Then url to repository is displayed", () => {
        jest.spyOn(breadcrumbPresenter, "getRepositoryListUrl").mockReturnValue("plugins/git/");

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
            } as unknown as Repository | FormattedGitLabRepository,
        };
        const wrapper = instantiateComponent();

        expect(wrapper.find("[data-test=git-repository-path]").attributes("href")).toBe(
            "plugins/git/MyPath/MyRepo",
        );
    });

    it("When repositories are not sorted by path, Then path is displayed behind label", () => {
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
            } as unknown as Repository | FormattedGitLabRepository,
        };
        const wrapper = instantiateComponent(false);

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
            } as unknown as Repository | FormattedGitLabRepository,
        };
        const wrapper = instantiateComponent();

        expect(wrapper.find("[data-test=repository_name]").text()).not.toContain("MyPath/");
        expect(wrapper.find("[data-test=repository_name]").text()).toContain("MyRepo");
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
            } as unknown as Repository | FormattedGitLabRepository,
        };
        const wrapper = instantiateComponent();

        expect(wrapper.find("[data-test=git-repository-card-description]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=git-repository-card-description]").text()).toBe(
            "This is my description.",
        );
        expect(wrapper.find("[data-test=git-repository-card-gerrit-icon]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=git-repository-card-gitlab-icon]").exists()).toBeFalsy();
    });
});
