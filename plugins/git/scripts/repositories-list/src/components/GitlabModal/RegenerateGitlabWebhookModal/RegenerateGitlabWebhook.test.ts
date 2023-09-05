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

import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Wrapper } from "@vue/test-utils";
import { createLocalVue, shallowMount } from "@vue/test-utils";
import RegenerateGitlabWebhook from "./RegenerateGitlabWebhook.vue";
import VueDOMPurifyHTML from "vue-dompurify-html";
import GetTextPlugin from "vue-gettext";
import type { Store } from "@tuleap/vuex-store-wrapper-jest";
import type { GitLabData, Repository } from "../../../type";
import * as gitlab_error_handler from "../../../gitlab/gitlab-error-handler";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

describe("RegenerateGitlabWebhook", () => {
    let store_options = {},
        localVue,
        store: Store;

    beforeEach(() => {
        store_options = {
            state: { gitlab: {} },
            getters: {},
        };
    });

    function instantiateComponent(): Wrapper<RegenerateGitlabWebhook> {
        localVue = createLocalVue();
        localVue.use(VueDOMPurifyHTML);
        localVue.use(GetTextPlugin, {
            translations: {},
            silent: true,
        });

        store = createStoreMock(store_options);

        return shallowMount(RegenerateGitlabWebhook, {
            mocks: { $store: store },
            localVue,
        });
    }

    it("When the user confirms new token, Then api is called", async () => {
        const wrapper = instantiateComponent();
        expect(wrapper.find("[data-test=icon-spin]").exists()).toBeFalsy();

        wrapper.setData({
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                    is_webhook_configured: false,
                } as GitLabData,
                normalized_path: "my/repo",
                integration_id: 1,
            } as Repository,
        });
        await wrapper.vm.$nextTick();

        wrapper.find("[data-test=regenerate-gitlab-webhook-submit]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(
            wrapper.find("[data-test=regenerate-gitlab-webhook-submit]").attributes().disabled,
        ).toBeTruthy();
        expect(wrapper.find("[data-test=icon-spin]").exists()).toBeTruthy();

        expect(store.dispatch).toHaveBeenCalledWith("gitlab/regenerateGitlabWebhook", 1);
    });

    it("When user submit but there are errors, Then nothing happens", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            message_error_rest: "Error message",
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                    is_webhook_configured: false,
                },
                normalized_path: "my/repo",
            },
        });
        await wrapper.vm.$nextTick();

        wrapper.find("[data-test=regenerate-gitlab-webhook-submit]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(store.dispatch).not.toHaveBeenCalled();
    });

    it("When api throws an error, Then error message is displayed", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                    is_webhook_configured: false,
                },
                normalized_path: "my/repo",
            },
        });
        await wrapper.vm.$nextTick();

        jest.spyOn(store, "dispatch").mockRejectedValue(
            new FetchWrapperError("Not Found", {
                status: 404,
                json: (): Promise<{ error: { code: number; message: string } }> =>
                    Promise.resolve({ error: { code: 404, message: "Error on server" } }),
            } as Response),
        );

        jest.spyOn(gitlab_error_handler, "handleError");
        // We also display the error in the console.
        jest.spyOn(global.console, "error").mockImplementation();

        wrapper.find("[data-test=regenerate-gitlab-webhook-submit]").trigger("click");
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.message_error_rest).toBe("404 Error on server");
        expect(
            wrapper.find("[data-test=regenerate-gitlab-webhook-submit]").attributes().disabled,
        ).toBeTruthy();
        expect(gitlab_error_handler.handleError).toHaveBeenCalled();
    });

    it("When user cancel, Then data are reset", async () => {
        const wrapper = instantiateComponent();
        wrapper.setData({
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                    is_webhook_configured: false,
                },
                normalized_path: "my/repo",
            },
            message_error_rest: "Error server",
            is_updating_webhook: true,
        });
        await wrapper.vm.$nextTick();

        wrapper.find("[data-test=regenerate-gitlab-webhook-cancel]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.message_error_rest).toBe("");
        expect(wrapper.vm.$data.repository).toBeNull();
        expect(wrapper.vm.$data.is_updating_webhook).toBeFalsy();
    });
});
