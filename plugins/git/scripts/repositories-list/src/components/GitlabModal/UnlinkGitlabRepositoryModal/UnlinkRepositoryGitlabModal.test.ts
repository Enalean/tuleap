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
import UnlinkRepositoryGitlabModal from "./UnlinkRepositoryGitlabModal.vue";
import * as api from "../../../gitlab/gitlab-api-querier";
import { mockFetchError, mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import type { Repository, State } from "../../../type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import { jest } from "@jest/globals";

jest.useFakeTimers();

describe("UnlinkRepositoryGitlabModal", () => {
    let store_options = {};
    let removeRepositorySpy: jest.Mock;
    let setSuccessMessageSpy: jest.Mock;

    beforeEach(() => {
        removeRepositorySpy = jest.fn();
        setSuccessMessageSpy = jest.fn();
        store_options = {
            state: {
                is_first_load_done: true,
            } as State,
            getters: {
                areExternalUsedServices: (): boolean => false,
                isCurrentRepositoryListEmpty: (): boolean => false,
                isInitialLoadingDoneWithoutError: (): boolean => true,
            },
            mutations: {
                removeRepository: removeRepositorySpy,
                setSuccessMessage: setSuccessMessageSpy,
            },
            modules: {
                gitlab: {
                    namespaced: true,
                    mutations: {
                        setUnlinkGitlabRepositoryModal: jest.fn(),
                    },
                },
            },
        };
    });

    function instantiateComponent(): VueWrapper<InstanceType<typeof UnlinkRepositoryGitlabModal>> {
        return shallowMount(UnlinkRepositoryGitlabModal, {
            global: { ...getGlobalTestOptions(store_options) },
        });
    }

    it("When the component is diplayed, Then confirmation message contains the label of repository", () => {
        const wrapper = instantiateComponent();

        wrapper.vm.repository = {
            id: 10,
            normalized_path: "My project",
        } as Repository;

        expect(wrapper.vm.confirmation_message).toBe(
            "Wow, wait a minute. You are about to unlink the GitLab repository My project. Please confirm your action.",
        );
    });

    it("When user confirm unlink, Then repository is removed and success message is displayed", async () => {
        const wrapper = instantiateComponent();
        mockFetchSuccess(jest.spyOn(api, "deleteIntegrationGitlab"));

        wrapper.vm.repository = {
            id: 10,
            normalized_path: "My project",
        } as Repository;

        const success_message = "GitLab repository My project has been successfully unlinked!";

        wrapper.find("[data-test=button-delete-gitlab-repository]").trigger("click");
        await jest.useFakeTimers();

        expect(removeRepositorySpy).toHaveBeenCalledWith(expect.any(Object), {
            id: 10,
            normalized_path: "My project",
        });
        expect(setSuccessMessageSpy).toHaveBeenCalledWith(expect.any(Object), success_message);
    });

    it("When error is returned from API, Then error is set to data and button is disabled", async () => {
        const wrapper = instantiateComponent();
        mockFetchError(jest.spyOn(api, "deleteIntegrationGitlab"), {
            status: 404,
            error_json: { error: { code: 404, message: "Error during delete" } },
        });

        wrapper.vm.repository = {
            id: 10,
            normalized_path: "My project",
        } as Repository;

        wrapper.find("[data-test=button-delete-gitlab-repository]").trigger("click");
        await jest.runOnlyPendingTimersAsync();

        expect(wrapper.find("[data-test=gitlab-fail-delete-repository]").text()).toBe(
            "404 Error during delete",
        );

        expect(wrapper.vm.disabled_button).toBeTruthy();
    });

    it("When there is a rest error and we click on submit, Then API is not queried", () => {
        const wrapper = instantiateComponent();
        const api_delete = jest.spyOn(api, "deleteIntegrationGitlab");

        wrapper.vm.repository = {
            id: 10,
            normalized_path: "My project",
        } as Repository;

        wrapper.vm.message_error_rest = "Error during delete";

        wrapper.find("[data-test=button-delete-gitlab-repository]").trigger("click");

        expect(api_delete).not.toHaveBeenCalled();
    });
});
