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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ArtifactClosureModal from "./ArtifactClosureModal.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";

jest.useFakeTimers();

describe("ArtifactClosureModal", () => {
    let setArtifactClosureModalSpy: jest.Mock;
    let setSuccessMessageSpy: jest.Mock;
    let updateGitlabRepositoryArtifactClosureSpy: jest.Mock;
    beforeEach(() => {
        setArtifactClosureModalSpy = jest.fn();
        setSuccessMessageSpy = jest.fn();
        updateGitlabRepositoryArtifactClosureSpy = jest.fn();
    });
    function instantiateComponent(): VueWrapper<InstanceType<typeof ArtifactClosureModal>> {
        const store_options = {
            mutations: {
                setSuccessMessage: setSuccessMessageSpy,
            },
            modules: {
                gitlab: {
                    state: {
                        artifact_closure_repository: {
                            integration_id: "gitlab-1234",
                            allow_artifact_closure: true,
                            label: "wow gitlab",
                        },
                    },
                    namespaced: true,
                    mutations: {
                        setArtifactClosureModal: setArtifactClosureModalSpy,
                    },
                    actions: {
                        updateGitlabRepositoryArtifactClosure:
                            updateGitlabRepositoryArtifactClosureSpy,
                    },
                },
            },
        };

        return shallowMount(ArtifactClosureModal, {
            global: { ...getGlobalTestOptions(store_options) },
        });
    }

    describe("The feedback display", () => {
        it("shows the error feedback if there is any REST error", async () => {
            const wrapper = instantiateComponent();

            wrapper.vm.message_error_rest = "error";
            await jest.useFakeTimers();

            expect(wrapper.find("[data-test=update-integration-fail]").exists()).toBe(true);
        });

        it("does not show the error feedback if there is no REST error", () => {
            const wrapper = instantiateComponent();

            wrapper.vm.message_error_rest = "";
            jest.useFakeTimers();

            expect(wrapper.find("[data-test=update-integration-fail]").exists()).toBe(false);
        });
    });

    describe("The 'Save' button display", () => {
        it("disables the button and displays the spinner during the Gitlab integration", async () => {
            const wrapper = instantiateComponent();

            wrapper.vm.is_updating_gitlab_repository = true;
            wrapper.vm.message_error_rest = "";
            await jest.useFakeTimers();

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

            wrapper.vm.is_updating_gitlab_repository = false;
            wrapper.vm.message_error_rest = "error";
            await jest.useFakeTimers();

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

            wrapper.vm.is_updating_gitlab_repository = false;
            wrapper.vm.message_error_rest = "";
            await jest.useFakeTimers();

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

                await jest.runOnlyPendingTimersAsync();

                updateGitlabRepositoryArtifactClosureSpy.mockResolvedValue({
                    allow_artifact_closure,
                });

                await wrapper
                    .find("[data-test=update-artifact-closure-modal-save-button]")
                    .trigger("click");

                const success_message = `Artifact closure is now ${expected_keyword_message} for 'wow gitlab'!`;
                expect(setSuccessMessageSpy).toHaveBeenCalledWith(
                    expect.any(Object),
                    success_message,
                );
            },
        );
    });
});
