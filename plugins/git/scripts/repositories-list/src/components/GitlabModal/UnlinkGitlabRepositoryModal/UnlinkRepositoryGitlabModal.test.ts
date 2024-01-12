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
import { shallowMount } from "@vue/test-utils";
import UnlinkRepositoryGitlabModal from "./UnlinkRepositoryGitlabModal.vue";
import * as api from "../../../gitlab/gitlab-api-querier";
import { mockFetchError, mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import type { State } from "../../../type";
import { createLocalVueForTests } from "../../../helpers/local-vue-for-tests";

describe("UnlinkRepositoryGitlabModal", () => {
    let store_options = {},
        store = {
            commit: jest.fn(),
        };

    beforeEach(() => {
        store_options = {
            state: {
                is_first_load_done: true,
            } as State,
            getters: {
                areExternalUsedServices: false,
                isCurrentRepositoryListEmpty: false,
                isInitialLoadingDoneWithoutError: true,
            },
        };
    });

    async function instantiateComponent(): Promise<Wrapper<UnlinkRepositoryGitlabModal>> {
        store = createStoreMock(store_options);
        return shallowMount(UnlinkRepositoryGitlabModal, {
            mocks: { $store: store },
            localVue: await createLocalVueForTests(),
        });
    }

    it("When the component is diplayed, Then confirmation message contains the label of repository", async () => {
        const wrapper = await instantiateComponent();

        wrapper.setData({
            repository: {
                id: 10,
                normalized_path: "My project",
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=confirm-unlink-gitlab-message]").text()).toBe(
            "Wow, wait a minute. You are about to unlink the GitLab repository My project. Please confirm your action.",
        );
    });

    it("When user confirm unlink, Then repository is removed and success message is displayed", async () => {
        const wrapper = await instantiateComponent();
        mockFetchSuccess(jest.spyOn(api, "deleteIntegrationGitlab"));

        wrapper.setData({
            repository: {
                id: 10,
                normalized_path: "My project",
            },
        });

        const success_message = "GitLab repository My project has been successfully unlinked!";

        await wrapper.vm.$nextTick();

        wrapper.find("[data-test=button-delete-gitlab-repository]").trigger("click");

        await wrapper.vm.$nextTick();

        expect(store.commit).toHaveBeenCalledWith("removeRepository", {
            id: 10,
            normalized_path: "My project",
        });
        expect(store.commit).toHaveBeenCalledWith("setSuccessMessage", success_message);
    });

    it("When error is returned from API, Then error is set to data and button is disabled", async () => {
        const wrapper = await instantiateComponent();
        mockFetchError(jest.spyOn(api, "deleteIntegrationGitlab"), {
            status: 404,
            error_json: { error: { code: 404, message: "Error during delete" } },
        });

        wrapper.setData({
            repository: {
                id: 10,
                normalized_path: "My project",
            },
        });

        wrapper.find("[data-test=button-delete-gitlab-repository]").trigger("click");
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=gitlab-fail-delete-repository]").text()).toBe(
            "404 Error during delete",
        );

        expect(
            wrapper.find("[data-test=button-delete-gitlab-repository]").attributes("disabled"),
        ).toBeTruthy();
    });

    it("When there is a rest error and we click on submit, Then API is not queried", async () => {
        const wrapper = await instantiateComponent();
        const api_delete = jest.spyOn(api, "deleteIntegrationGitlab");

        wrapper.setData({
            repository: {
                id: 10,
                normalized_path: "My project",
            },
            message_error_rest: "Error during delete",
        });

        wrapper.find("[data-test=button-delete-gitlab-repository]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(api_delete).not.toHaveBeenCalled();
    });
});
