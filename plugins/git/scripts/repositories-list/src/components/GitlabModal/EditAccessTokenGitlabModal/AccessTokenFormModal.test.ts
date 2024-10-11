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

import type { Store } from "@tuleap/vuex-store-wrapper-jest";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import AccessTokenFormModal from "./AccessTokenFormModal.vue";
import { createLocalVueForTests } from "../../../helpers/local-vue-for-tests";

jest.useFakeTimers();

type AccessTokenFormModalExposed = { gitlab_new_token: string; error_message: string };

describe("AccessTokenFormModal", () => {
    let store_options = {},
        propsData = {},
        store: Store;

    beforeEach(() => {
        store_options = {
            state: {},
            getters: {},
        };
    });

    async function instantiateComponent(): Promise<Wrapper<Vue & AccessTokenFormModalExposed>> {
        store = createStoreMock(store_options, { gitlab: {} });

        return shallowMount(AccessTokenFormModal, {
            propsData,
            mocks: { $store: store },
            localVue: await createLocalVueForTests(),
        }) as Wrapper<Vue & AccessTokenFormModalExposed>;
    }

    it("When the user check token, Then the submit button is disabled and icon spin is displayed and api is called", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            },
            gitlab_token: "",
        };

        const wrapper = await instantiateComponent();
        expect(wrapper.find("[data-test=icon-spin]").exists()).toBeFalsy();

        wrapper.setData({
            gitlab_new_token: "AFREZF546",
        });

        await wrapper
            .find("[data-test=edit-token-gitlab-repository-modal-form]")
            .trigger("submit.prevent");

        expect(
            wrapper.find("[data-test=button-check-new-token-gitlab-repository]").attributes()
                .disabled,
        ).toBeTruthy();
        expect(wrapper.find("[data-test=icon-spin]").exists()).toBeTruthy();

        expect(store.dispatch).toHaveBeenCalledWith("gitlab/getGitlabRepositoryFromId", {
            credentials: {
                server_url: "https://example.com/",
                token: "AFREZF546",
            },
            id: 12,
        });

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
            },
            gitlab_token: "",
        };

        const wrapper = await instantiateComponent();

        await wrapper.setData({
            error_message: "Error message",
        });

        expect(wrapper.find("[data-test=gitlab-fail-check-new-token]").text()).toBe(
            "Error message",
        );
    });

    it("When there are no token and server url, Then submit button is disabled", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            },
            gitlab_token: "",
        };

        const wrapper = await instantiateComponent();
        await wrapper.setData({
            gitlab_new_token: "",
        });

        expect(
            wrapper.find("[data-test=button-check-new-token-gitlab-repository]").attributes()
                .disabled,
        ).toBeTruthy();
    });

    it("When user submit but token is empty, Then error message is displayed", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            },
            gitlab_token: "",
        };

        const wrapper = await instantiateComponent();

        wrapper.setData({
            gitlab_new_token: "",
        });

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
            },
            gitlab_token: "",
        };

        const wrapper = await instantiateComponent();
        jest.spyOn(store, "dispatch").mockReturnValue(Promise.reject());

        wrapper.setData({
            gitlab_new_token: "AZERTY123",
        });

        wrapper
            .find("[data-test=edit-token-gitlab-repository-modal-form]")
            .trigger("submit.prevent");

        try {
            await jest.runOnlyPendingTimersAsync();
        } catch (e) {
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
            },
            gitlab_token: "",
        };

        const wrapper = await instantiateComponent();
        await wrapper.setData({
            gitlab_new_token: "AZERTY123",
            error_message: "Error",
        });

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
