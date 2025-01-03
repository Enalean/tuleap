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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import GitLabAdministration from "./GitLabAdministration.vue";
import type { FormattedGitLabRepository } from "../type";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";

jest.mock("tlp");

describe("GitLabAdministration", () => {
    let repository: FormattedGitLabRepository;
    let showDeleteGitlabRepositoryModalSpy: jest.Mock;
    let showEditAccessTokenGitlabRepositoryModalSpy: jest.Mock;
    let showRegenerateGitlabWebhookModalSpy: jest.Mock;
    let showArtifactClosureModalSpy: jest.Mock;

    beforeEach(() => {
        showDeleteGitlabRepositoryModalSpy = jest.fn();
        showEditAccessTokenGitlabRepositoryModalSpy = jest.fn();
        showRegenerateGitlabWebhookModalSpy = jest.fn();
        showArtifactClosureModalSpy = jest.fn();
    });

    function instantiateComponent(): VueWrapper<InstanceType<typeof GitLabAdministration>> {
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
        } as FormattedGitLabRepository;
        const propsData = { repository, is_admin: true };
        const store_options = {
            modules: {
                gitlab: {
                    namespaced: true,
                    actions: {
                        showDeleteGitlabRepositoryModal: showDeleteGitlabRepositoryModalSpy,
                        showEditAccessTokenGitlabRepositoryModal:
                            showEditAccessTokenGitlabRepositoryModalSpy,
                        showRegenerateGitlabWebhookModal: showRegenerateGitlabWebhookModalSpy,
                        showArtifactClosureModal: showArtifactClosureModalSpy,
                    },
                },
            },
        };

        return shallowMount(GitLabAdministration, {
            global: { ...getGlobalTestOptions(store_options) },
            props: {
                repository: propsData.repository,
                is_admin: propsData.is_admin,
            },
        });
    }

    it("When user is git admin but repository comes from Gitlab, Then admin icon is displayed", () => {
        const wrapper = instantiateComponent();

        expect(wrapper.find("[data-test=git-repository-card-admin-link]").exists()).toBeFalsy();
        expect(
            wrapper.find("[data-test=git-repository-card-admin-unlink-gitlab]").exists(),
        ).toBeTruthy();

        expect(wrapper.find("[data-test=dropdown-gitlab-administration-1]").exists()).toBeTruthy();
        expect(
            wrapper.find("[data-test=dropdown-gitlab-administration-menu-options]").exists(),
        ).toBeTruthy();
    });

    it("When repository is GitLab and user clicks to unlink, Then modal opens", () => {
        const wrapper = instantiateComponent();

        wrapper.find("[data-test=unlink-gitlab-repository-1]").trigger("click");

        expect(showDeleteGitlabRepositoryModalSpy).toHaveBeenCalledWith(
            expect.any(Object),
            repository,
        );
    });

    it("When repository is GitLab and user clicks to edit token, Then modal opens", () => {
        const wrapper = instantiateComponent();

        wrapper.find("[data-test=edit-access-token-gitlab-repository]").trigger("click");

        expect(showEditAccessTokenGitlabRepositoryModalSpy).toHaveBeenCalledWith(
            expect.any(Object),
            repository,
        );
    });

    it("When repository is GitLab and user clicks to regenerate webhook, Then modal opens", () => {
        const wrapper = instantiateComponent();

        wrapper.find("[data-test=regenerate-webhook-gitlab-repository]").trigger("click");

        expect(showRegenerateGitlabWebhookModalSpy).toHaveBeenCalledWith(
            expect.any(Object),
            repository,
        );
    });

    it("When repository is GitLab and user clicks to update the allowing artifact closure value, Then modal opens", () => {
        const wrapper = instantiateComponent();

        wrapper.find("[data-test=artifact-closure-gitlab-repository]").trigger("click");

        expect(showArtifactClosureModalSpy).toHaveBeenCalledWith(expect.any(Object), repository);
    });
});
