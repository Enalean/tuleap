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

import type { Store } from "@tuleap/vuex-store-wrapper-jest";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import GitRepository from "./GitRepository.vue";
import * as repositoryListPresenter from "../repository-list-presenter";
import PullRequestBadge from "./PullRequestBadge.vue";
import * as breadcrumbPresenter from "./../breadcrumb-presenter";
import type { State } from "../type";
import TimeAgo from "javascript-time-ago";
import time_ago_english from "javascript-time-ago/locale/en";
import { createLocalVueForTests } from "../helpers/local-vue-for-tests";

interface StoreOptions {
    state: State;
    getters: {
        isGitlabUsed: boolean;
        isFolderDisplayMode: boolean;
    };
}

describe("GitRepository", () => {
    let store_options: StoreOptions,
        propsData = {},
        store: Store;

    beforeEach(() => {
        TimeAgo.locale(time_ago_english);
        jest.spyOn(repositoryListPresenter, "getUserIsAdmin").mockReturnValue(true);
        jest.spyOn(repositoryListPresenter, "getDashCasedLocale").mockReturnValue("en-US");

        store_options = {
            state: {} as State,
            getters: {
                isGitlabUsed: false,
                isFolderDisplayMode: true,
            },
        };
    });

    async function instantiateComponent(): Promise<Wrapper<GitRepository>> {
        store = createStoreMock(store_options);
        return shallowMount(GitRepository, {
            propsData,
            mocks: { $store: store },
            localVue: await createLocalVueForTests(),
        });
    }

    it("When repository comes from Gitlab and there is a description, Then Gitlab icon and description are displayed", async () => {
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
        const wrapper = await instantiateComponent();

        expect(wrapper.find("[data-test=git-repository-card-description]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=git-repository-card-description]").text()).toBe(
            "This is my description.",
        );
        expect(wrapper.find("[data-test=git-repository-card-gitlab-icon]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=git-repository-card-gerrit-icon]").exists()).toBeFalsy();
    });

    it("When repository doesn't come from Gitlab and there is a description, Then only description is displayed", async () => {
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
        const wrapper = await instantiateComponent();

        expect(wrapper.find("[data-test=git-repository-card-description]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=git-repository-card-description]").text()).toBe(
            "This is my description.",
        );
        expect(wrapper.find("[data-test=git-repository-card-gitlab-icon]").exists()).toBeFalsy();
    });

    it("When repository comes from Gitlab, Then PullRequestBadge is not displayed", async () => {
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
        const wrapper = await instantiateComponent();

        expect(wrapper.findComponent(PullRequestBadge).exists()).toBeFalsy();
    });

    it("When repository is Git and there are some pull requests, Then PullRequestBadge is displayed", async () => {
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
        const wrapper = await instantiateComponent();

        expect(wrapper.findComponent(PullRequestBadge).exists()).toBeTruthy();
    });

    it("When repository is GitLab, Then gitlab_repository_url of gitlab is displayed", async () => {
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
        const wrapper = await instantiateComponent();

        expect(wrapper.find("[data-test=git-repository-path]").attributes("href")).toBe(
            "https://example.com/MyPath/MyRepo",
        );
    });

    it("When repository is Git, Then url to repository is displayed", async () => {
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
            },
        };
        const wrapper = await instantiateComponent();

        expect(wrapper.find("[data-test=git-repository-path]").attributes("href")).toBe(
            "plugins/git/MyPath/MyRepo",
        );
    });

    it("When repositories are not sorted by path, Then path is displayed behind label", async () => {
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
        const wrapper = await instantiateComponent();

        expect(wrapper.find("[data-test=repository_name]").text()).toContain("MyPath/");
        expect(wrapper.find("[data-test=repository_name]").text()).toContain("MyRepo");
    });

    it("When repositories are sorted by path, Then path is not displayed behind label", async () => {
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
        const wrapper = await instantiateComponent();

        expect(wrapper.find("[data-test=repository_name]").text()).not.toContain("MyPath/");
        expect(wrapper.find("[data-test=repository_name]").text()).toContain("MyRepo");
    });

    it("When repository is Git and handled by Gerrit, Then Gerrit icon and description are displayed", async () => {
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
        const wrapper = await instantiateComponent();

        expect(wrapper.find("[data-test=git-repository-card-description]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=git-repository-card-description]").text()).toBe(
            "This is my description.",
        );
        expect(wrapper.find("[data-test=git-repository-card-gerrit-icon]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=git-repository-card-gitlab-icon]").exists()).toBeFalsy();
    });
});
