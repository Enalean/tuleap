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
import AccessTokenFormModal from "./AccessTokenFormModal.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import type { Repository } from "../../../type";
import { jest } from "@jest/globals";

jest.useFakeTimers();

describe("AccessTokenFormModal", () => {
    let store_options = {},
        propsData = {
            repository: {} as Repository,
            gitlab_token: "",
        };
    let getGitlabRepositoryFromIdSpy: jest.Mock;

    beforeEach(() => {
        getGitlabRepositoryFromIdSpy = jest.fn();
        store_options = {
            modules: {
                gitlab: {
                    namespaced: true,
                    actions: {
                        getGitlabRepositoryFromId: getGitlabRepositoryFromIdSpy,
                    },
                },
            },
        };
    });

    function instantiateComponent(): VueWrapper<InstanceType<typeof AccessTokenFormModal>> {
        return shallowMount(AccessTokenFormModal, {
            props: {
                repository: propsData.repository,
                gitlab_token: propsData.gitlab_token,
            },
            global: { ...getGlobalTestOptions(store_options) },
        });
    }

    it("When the user check token, Then the submit button is disabled and icon spin is displayed and api is called", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            } as Repository,
            gitlab_token: "",
        };

        const wrapper = instantiateComponent();
        expect(wrapper.find("[data-test=icon-spin]").exists()).toBeFalsy();

        wrapper.vm.gitlab_new_token = "AFREZF546";

        wrapper
            .find("[data-test=edit-token-gitlab-repository-modal-form]")
            .trigger("submit.prevent");
        await jest.useFakeTimers();

        expect(wrapper.vm.disabled_button).toBeTruthy();
        expect(wrapper.find("[data-test=icon-spin]").exists()).toBeTruthy();

        expect(getGitlabRepositoryFromIdSpy).toHaveBeenCalledWith(expect.any(Object), {
            credentials: {
                server_url: "https://example.com/",
                token: "AFREZF546",
            },
            id: 12,
        });

        wrapper.vm.$emit("on-get-new-token-gitlab", { token: "AFREZF546" });
        const on_get_new_token = wrapper.emitted()["on-get-new-token-gitlab"];
        if (!on_get_new_token) {
            throw new Error("Should have emitted on-get-new-token");
        }

        expect(on_get_new_token[0]).toStrictEqual([{ token: "AFREZF546" }]);
    });

    it("When there is an error message, Then it's displayed", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            } as Repository,
            gitlab_token: "",
        };

        const wrapper = instantiateComponent();

        wrapper.vm.error_message = "Error message";
        await jest.useFakeTimers();

        expect(wrapper.find("[data-test=gitlab-fail-check-new-token]").text()).toBe(
            "Error message",
        );
    });

    it("When there are no token and server url, Then submit button is disabled", () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            } as Repository,
            gitlab_token: "",
        };

        const wrapper = instantiateComponent();
        wrapper.vm.gitlab_new_token = "";

        expect(wrapper.vm.disabled_button).toBeTruthy();
    });

    it("When user submit but token is empty, Then error message is displayed", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            } as Repository,
            gitlab_token: "",
        };

        const wrapper = instantiateComponent();

        wrapper.vm.gitlab_new_token = "";

        await wrapper
            .find("[data-test=edit-token-gitlab-repository-modal-form]")
            .trigger("submit.prevent");

        expect(wrapper.find("[data-test=gitlab-fail-check-new-token]").text()).toBe(
            "You must provide a valid GitLab API token",
        );
    });

    it("When api throws an error, Then error message is displayed", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            } as Repository,
            gitlab_token: "",
        };

        const wrapper = instantiateComponent();
        getGitlabRepositoryFromIdSpy.mockReturnValue(Promise.reject());

        wrapper.vm.gitlab_new_token = "AZERTY123";

        wrapper
            .find("[data-test=edit-token-gitlab-repository-modal-form]")
            .trigger("submit.prevent");

        try {
            await jest.runOnlyPendingTimersAsync();
        } catch (_e) {
            // Ignore, error handler re-throws REST errors
        }

        expect(wrapper.vm.error_message).toBe(
            "Submitted token is invalid to access to this repository on this GitLab server.",
        );
    });
    it("When user cancel, Then data are reset", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            } as Repository,
            gitlab_token: "",
        };

        const wrapper = instantiateComponent();
        wrapper.vm.gitlab_new_token = "AZERTY123";
        wrapper.vm.error_message = "Error";

        expect(wrapper.vm.gitlab_new_token).toBe("AZERTY123");
        expect(wrapper.vm.error_message).toBe("Error");

        await wrapper
            .find("[data-test=button-cancel-new-token-gitlab-repository]")
            .trigger("click");

        expect(wrapper.vm.gitlab_new_token).toBe("");
        expect(wrapper.vm.error_message).toBe("");

        const on_close_modal = wrapper.emitted()["on-close-modal"];
        if (!on_close_modal) {
            throw new Error("Should have emitted on-close-modal");
        }

        expect(on_close_modal[0]).toStrictEqual([]);
    });
});
