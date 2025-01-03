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
import ConfirmReplaceTokenModal from "./ConfirmReplaceTokenModal.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import type { Repository } from "../../../type";

jest.useFakeTimers();

describe("ConfirmReplaceTokenModal", () => {
    let store_options = {},
        propsData = {
            repository: {} as Repository,
            gitlab_new_token: "",
        };
    let updateBotApiTokenGitlabSpy: jest.Mock;

    beforeEach(() => {
        updateBotApiTokenGitlabSpy = jest.fn();
        store_options = {
            modules: {
                gitlab: {
                    namespaced: true,
                    actions: {
                        updateBotApiTokenGitlab: updateBotApiTokenGitlabSpy,
                    },
                },
            },
        };
    });

    function instantiateComponent(): VueWrapper<InstanceType<typeof ConfirmReplaceTokenModal>> {
        return shallowMount(ConfirmReplaceTokenModal, {
            props: {
                repository: propsData.repository,
                gitlab_new_token: propsData.gitlab_new_token,
            },
            global: { ...getGlobalTestOptions(store_options) },
        });
    }

    it("When the user confirms new token, Then api is called and event is emitted", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
                integration_id: 1,
            } as Repository,
            gitlab_new_token: "AZRERT123",
        };

        const wrapper = instantiateComponent();
        expect(wrapper.find("[data-test=icon-spin]").exists()).toBeFalsy();

        wrapper.find("[data-test=button-confirm-edit-token-gitlab]").trigger("click");
        await jest.useFakeTimers();

        expect(wrapper.vm.disabled_button).toBeTruthy();
        expect(wrapper.find("[data-test=icon-spin]").exists()).toBeTruthy();

        expect(updateBotApiTokenGitlabSpy).toHaveBeenCalledWith(expect.any(Object), {
            gitlab_api_token: "AZRERT123",
            gitlab_integration_id: 1,
        });

        wrapper.vm.$emit("on-success-edit-token");
        const on_success_edit_token = wrapper.emitted()["on-success-edit-token"];
        if (!on_success_edit_token) {
            throw new Error("Should have emitted on-success-edit-token");
        }

        expect(on_success_edit_token[0]).toStrictEqual([]);
    });

    it("When there is an error message, Then it's displayed", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 1,
                },
                normalized_path: "my/repo",
            } as Repository,
            gitlab_new_token: "AZRERT123",
        };

        const wrapper = instantiateComponent();

        wrapper.vm.message_error_rest = "Error message";
        await jest.useFakeTimers();

        expect(wrapper.find("[data-test=gitlab-fail-patch-edit-token]").text()).toBe(
            "Error message",
        );
        expect(wrapper.vm.disabled_button).toBeTruthy();
    });

    it("When user submit but there are errors, Then nothing happens", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            } as Repository,
            gitlab_new_token: "AZRERT123",
        };

        const wrapper = instantiateComponent();

        wrapper.vm.message_error_rest = "Error message";

        await wrapper.find("[data-test=button-confirm-edit-token-gitlab]").trigger("click");

        expect(updateBotApiTokenGitlabSpy).not.toHaveBeenCalled();
    });

    it("When user back to form, Then event is emitted", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            } as Repository,
            gitlab_new_token: "AZRERT123",
        };

        const wrapper = instantiateComponent();

        await wrapper.find("[data-test=button-gitlab-edit-token-back]").trigger("click");

        const on_back_button = wrapper.emitted()["on-back-button"];
        if (!on_back_button) {
            throw new Error("Should have emitted on-back-button");
        }

        expect(on_back_button[0]).toStrictEqual([]);
    });
});
