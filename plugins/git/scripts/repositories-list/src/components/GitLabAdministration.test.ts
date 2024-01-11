/*
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import GitLabAdministration from "./GitLabAdministration.vue";
import type { Store } from "@tuleap/vuex-store-wrapper-jest";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Repository } from "../type";
import { createLocalVueForTests } from "../helpers/local-vue-for-tests";

jest.mock("tlp");

describe("GitLabAdministration", () => {
    let repository: Repository, store: Store;

    async function instantiateComponent(): Promise<Wrapper<GitLabAdministration>> {
        repository = {
            id: 1,
            normalized_path: "MyPath/MyRepo",
            description: "This is my description.",
            path_without_project: "MyPath",
            label: "MyRepo",
            last_update_date: "2020-10-28T15:13:13+01:00",
            gitlab_data: {
                gitlab_repository_url: "https://example.com/MyPath/MyRepo",
                gitlab_repository_id: 1,
            },
        } as Repository;
        const propsData = { repository, is_admin: true };
        const store_options = {
            state: { gitlab: {} },
            getters: {
                isGitlabUsed: false,
                isFolderDisplayMode: true,
            },
        };

        store = createStoreMock(store_options);
        return shallowMount(GitLabAdministration, {
            propsData,
            mocks: { $store: store },
            localVue: await createLocalVueForTests(),
        });
    }

    it("When user is git admin but repository comes from Gitlab, Then admin icon is displayed", async () => {
        const wrapper = await instantiateComponent();

        expect(wrapper.find("[data-test=git-repository-card-admin-link]").exists()).toBeFalsy();
        expect(
            wrapper.find("[data-test=git-repository-card-admin-unlink-gitlab]").exists(),
        ).toBeTruthy();

        expect(wrapper.find("[data-test=dropdown-gitlab-administration-1]").exists()).toBeTruthy();
        expect(
            wrapper.find("[data-test=dropdown-gitlab-administration-menu-options]").exists(),
        ).toBeTruthy();
    });

    it("When repository is GitLab and user clicks to unlink, Then modal opens", async () => {
        const wrapper = await instantiateComponent();

        wrapper.find("[data-test=unlink-gitlab-repository-1]").trigger("click");

        await wrapper.vm.$nextTick();

        expect(store.dispatch).toHaveBeenCalledWith(
            "gitlab/showDeleteGitlabRepositoryModal",
            repository,
        );
    });

    it("When repository is GitLab and user clicks to edit token, Then modal opens", async () => {
        const wrapper = await instantiateComponent();

        wrapper.find("[data-test=edit-access-token-gitlab-repository]").trigger("click");

        await wrapper.vm.$nextTick();

        expect(store.dispatch).toHaveBeenCalledWith(
            "gitlab/showEditAccessTokenGitlabRepositoryModal",
            repository,
        );
    });

    it("When repository is GitLab and user clicks to regenerate webhook, Then modal opens", async () => {
        const wrapper = await instantiateComponent();

        wrapper.find("[data-test=regenerate-webhook-gitlab-repository]").trigger("click");

        await wrapper.vm.$nextTick();

        expect(store.dispatch).toHaveBeenCalledWith(
            "gitlab/showRegenerateGitlabWebhookModal",
            repository,
        );
    });

    it("When repository is GitLab and user clicks to update the allowing artifact closure value, Then modal opens", async () => {
        const wrapper = await instantiateComponent();

        wrapper.find("[data-test=artifact-closure-gitlab-repository]").trigger("click");

        await wrapper.vm.$nextTick();

        expect(store.dispatch).toHaveBeenCalledWith("gitlab/showArtifactClosureModal", repository);
    });
});
