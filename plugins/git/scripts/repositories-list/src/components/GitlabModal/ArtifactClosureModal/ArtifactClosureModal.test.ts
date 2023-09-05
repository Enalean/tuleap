/*
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import type { Wrapper } from "@vue/test-utils";
import { createLocalVue, shallowMount } from "@vue/test-utils";
import VueDOMPurifyHTML from "vue-dompurify-html";
import GetTextPlugin from "vue-gettext";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Store } from "@tuleap/vuex-store-wrapper-jest";
import ArtifactClosureModal from "./ArtifactClosureModal.vue";
import * as gitlab_error_handler from "../../../gitlab/gitlab-error-handler";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

describe("ArtifactClosureModal", () => {
    let localVue, store: Store;

    function instantiateComponent(): Wrapper<ArtifactClosureModal> {
        localVue = createLocalVue();
        localVue.use(VueDOMPurifyHTML);
        localVue.use(GetTextPlugin, {
            translations: {},
            silent: true,
        });

        store = createStoreMock(
            {},
            {
                gitlab: {
                    artifact_closure_repository: { integration_id: 10, label: "wow gitlab" },
                },
            },
        );

        return shallowMount(ArtifactClosureModal, {
            propsData: {},
            mocks: { $store: store },
            localVue,
        });
    }

    describe("The feedback display", () => {
        it("shows the error feedback if there is any REST error", async () => {
            const wrapper = instantiateComponent();

            wrapper.setData({ message_error_rest: "error" });
            await wrapper.vm.$nextTick();

            expect(wrapper.find("[data-test=update-integration-fail]").exists()).toBe(true);
        });

        it("does not show the error feedback if there is no REST error", async () => {
            const wrapper = instantiateComponent();

            wrapper.setData({ message_error_rest: "" });
            await wrapper.vm.$nextTick();

            expect(wrapper.find("[data-test=update-integration-fail]").exists()).toBe(false);
        });
    });

    describe("The 'Save' button display", () => {
        it("disables the button and displays the spinner during the Gitlab integration", async () => {
            const wrapper = instantiateComponent();

            wrapper.setData({ is_updating_gitlab_repository: true, message_error_rest: "" });
            await wrapper.vm.$nextTick();

            expect(
                wrapper.find("[data-test=update-artifact-closure-modal-icon-spin]").exists(),
            ).toBe(true);

            const save_button = wrapper.find(
                "[data-test=update-artifact-closure-modal-save-button]",
            ).element as HTMLButtonElement;
            if (!(save_button instanceof HTMLButtonElement)) {
                throw new Error("Could not find the help button");
            }
            expect(save_button.disabled).toBe(true);
        });

        it("disables the button but does NOT display the spinner if the update failed", async () => {
            const wrapper = instantiateComponent();

            wrapper.setData({ is_updating_gitlab_repository: false, message_error_rest: "error" });
            await wrapper.vm.$nextTick();

            expect(
                wrapper.find("[data-test=update-artifact-closure-modal-icon-spin]").exists(),
            ).toBe(false);

            const save_button = wrapper.find(
                "[data-test=update-artifact-closure-modal-save-button]",
            ).element as HTMLButtonElement;
            if (!(save_button instanceof HTMLButtonElement)) {
                throw new Error("Could not find the help button");
            }
            expect(save_button.disabled).toBe(true);
        });

        it("let enabled the button when everything are ok and there when is no update", async () => {
            const wrapper = instantiateComponent();

            wrapper.setData({ is_updating_gitlab_repository: false, message_error_rest: "" });
            await wrapper.vm.$nextTick();

            expect(
                wrapper.find("[data-test=update-artifact-closure-modal-icon-spin]").exists(),
            ).toBe(false);

            const save_button = wrapper.find(
                "[data-test=update-artifact-closure-modal-save-button]",
            ).element as HTMLButtonElement;
            if (!(save_button instanceof HTMLButtonElement)) {
                throw new Error("Could not find the help button");
            }
            expect(save_button.disabled).toBe(false);
        });
    });

    describe("updateArtifactClosureValue", () => {
        it.each([
            ["allowed", true],
            ["disabled", false],
        ])(
            "updates and returns the '%s' artifact closure message",
            async (expected_keyword_message: string, allow_artifact_closure: boolean) => {
                const wrapper = instantiateComponent();

                await wrapper.vm.$nextTick();

                jest.spyOn(store, "dispatch").mockResolvedValue({
                    allow_artifact_closure,
                });

                wrapper
                    .find("[data-test=update-artifact-closure-modal-save-button]")
                    .trigger("click");
                await wrapper.vm.$nextTick();
                await wrapper.vm.$nextTick();

                const success_message = `Artifact closure is now ${expected_keyword_message} for 'wow gitlab'!`;
                expect(store.commit).toHaveBeenCalledWith("setSuccessMessage", success_message);
            },
        );

        it("set the message error and display this error in the console if there is error during the update", async () => {
            const wrapper = instantiateComponent();

            wrapper.setData({
                allow_artifact_closure: true,
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

            wrapper.find("[data-test=update-artifact-closure-modal-save-button]").trigger("click");
            await wrapper.vm.$nextTick();
            await wrapper.vm.$nextTick();
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.$data.message_error_rest).toBe("404 Error on server");
        });
    });
});
