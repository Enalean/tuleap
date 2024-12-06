/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import RegenerateGitlabWebhook from "./RegenerateGitlabWebhook.vue";
import type { GitLabData, Repository } from "../../../type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";

jest.useFakeTimers();

describe("RegenerateGitlabWebhook", () => {
    let setRegenerateGitlabWebhookModalSpy: jest.Mock;
    let regenerateGitlabWebhookSpy: jest.Mock;
    let setSuccessMessageSpy: jest.Mock;
    let store_options = {};

    beforeEach(() => {
        setRegenerateGitlabWebhookModalSpy = jest.fn();
        regenerateGitlabWebhookSpy = jest.fn();
        setSuccessMessageSpy = jest.fn();
        store_options = {
            state: {},
            getters: {},
            mutations: {
                setSuccessMessage: setSuccessMessageSpy,
            },
            modules: {
                gitlab: {
                    namespaced: true,
                    mutations: {
                        setRegenerateGitlabWebhookModal: setRegenerateGitlabWebhookModalSpy,
                    },
                    actions: {
                        regenerateGitlabWebhook: regenerateGitlabWebhookSpy,
                    },
                },
            },
        };
    });

    function instantiateComponent(): VueWrapper<InstanceType<typeof RegenerateGitlabWebhook>> {
        return shallowMount(RegenerateGitlabWebhook, {
            global: { ...getGlobalTestOptions(store_options) },
        });
    }

    it("When the user confirms new token, Then api is called", () => {
        const wrapper = instantiateComponent();
        expect(wrapper.find("[data-test=icon-spin]").exists()).toBeFalsy();

        wrapper.vm.repository = {
            gitlab_data: {
                gitlab_repository_url: "https://example.com/my/repo",
                gitlab_repository_id: 12,
                is_webhook_configured: false,
            } as GitLabData,
            normalized_path: "my/repo",
            integration_id: 1,
        } as Repository;

        wrapper.find("[data-test=regenerate-gitlab-webhook-submit]").trigger("click");

        expect(wrapper.vm.disabled_button).toBeTruthy();
        expect(wrapper.vm.is_updating_webhook).toBeTruthy();

        expect(regenerateGitlabWebhookSpy).toHaveBeenCalledWith(expect.any(Object), 1);
    });

    it("When user submit but there are errors, Then nothing happens", () => {
        const wrapper = instantiateComponent();

        wrapper.vm.message_error_rest = "Error message";
        wrapper.vm.repository = {
            gitlab_data: {
                gitlab_repository_url: "https://example.com/my/repo",
                gitlab_repository_id: 12,
                is_webhook_configured: false,
            },
            normalized_path: "my/repo",
        } as Repository;

        wrapper.find("[data-test=regenerate-gitlab-webhook-submit]").trigger("click");

        expect(regenerateGitlabWebhookSpy).not.toHaveBeenCalled();
    });

    it("When user cancel, Then data are reset", () => {
        const wrapper = instantiateComponent();

        wrapper.vm.repository = {
            gitlab_data: {
                gitlab_repository_url: "https://example.com/my/repo",
                gitlab_repository_id: 12,
                is_webhook_configured: false,
            },
            normalized_path: "my/repo",
        } as Repository;
        wrapper.vm.message_error_rest = "Error server";
        wrapper.vm.is_updating_webhook = true;

        wrapper.find("[data-test=regenerate-gitlab-webhook-cancel]").trigger("click");

        expect(wrapper.vm.message_error_rest).toBe("");
        expect(wrapper.vm.repository).toBeNull();
        expect(wrapper.vm.is_updating_webhook).toBeFalsy();
    });
});
