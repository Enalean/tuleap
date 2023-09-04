/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
 */
import { vi, describe, beforeEach, it, expect } from "vitest";

import type { SpyInstance } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { getGlobalTestOptions } from "../../tests/global-options-for-tests";
import { useModalStore } from "../stores/modal";
import { useSelectorsStore } from "../stores/selectors";
import { useDryRunStore } from "../stores/dry-run";
import { ARTIFACT_ID } from "../injection-symbols";
import type { ArtifactField } from "../api/types";

import MoveModal from "./MoveModal.vue";
import MoveModalSelectors from "./MoveModalSelectors.vue";
import DryRunPreview from "./DryRunPreview.vue";

const artifact_id = 126;

type MockedJquery = { on: SpyInstance; modal: SpyInstance };
const mocked_jquery: MockedJquery = {
    on: vi.fn(),
    modal: vi.fn(),
};

vi.mock("jquery", () => ({
    default: (): MockedJquery => mocked_jquery,
}));

vi.mock("@tuleap/vue-strict-inject");

describe("MoveModal", () => {
    const getWrapper = (): VueWrapper => {
        return shallowMount(MoveModal, {
            global: {
                ...getGlobalTestOptions(),
            },
        });
    };

    beforeEach(() => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation((key) => {
            if (key !== ARTIFACT_ID) {
                throw new Error(`Tried to inject ${key} while it was not mocked.`);
            }

            return artifact_id;
        });
    });

    describe("mounted()", () => {
        it("should create a modal", () => {
            getWrapper();

            expect(mocked_jquery.modal).toHaveBeenCalledTimes(1);
        });
    });

    describe("display", () => {
        describe("Loader", () => {
            it.each([
                ["the modal is loading the projects", true, false],
                ["the modal is processing the artifact move", false, true],
            ])(
                "should show a loader when %s",
                async (when, are_projects_loading, is_processing_move) => {
                    const wrapper = getWrapper();

                    await useModalStore().$patch({
                        is_processing_move,
                    });

                    await useSelectorsStore().$patch({
                        are_projects_loading,
                    });

                    expect(wrapper.find("[data-test=modal-loader]").exists()).toBe(true);
                },
            );
        });

        describe("Error message", () => {
            it("should be displayed when there is one to display", async () => {
                const error_message = "Oh snap!";
                const wrapper = getWrapper();

                await useModalStore().$patch({ error_message });

                const error = wrapper.find("[data-test=modal-error-message]");

                expect(error.exists()).toBe(true);
                expect(error.text()).toBe(error_message);
            });

            it("should not be displayed when there is no error to display", () => {
                const wrapper = getWrapper();
                const error = wrapper.find("[data-test=modal-error-message]");

                expect(error.exists()).toBe(false);
            });
        });

        describe("Selectors", () => {
            it("should be visible when the move is not being processed", async () => {
                const wrapper = getWrapper();
                await useModalStore().$patch({ is_processing_move: false });

                expect(wrapper.findComponent(MoveModalSelectors).isVisible()).toBe(true);
            });

            it("should not be visible when the move is being processed", async () => {
                const wrapper = getWrapper();
                await useModalStore().$patch({ is_processing_move: true });

                expect(wrapper.findComponent(MoveModalSelectors).isVisible()).toBe(false);
            });
        });

        describe("Dry run preview", () => {
            it("should not be displayed if the dry run has not been processed", async () => {
                const wrapper = getWrapper();

                await useModalStore().$patch({ is_processing_move: false });
                await useDryRunStore().$patch({ has_processed_dry_run: false });

                expect(wrapper.findComponent(DryRunPreview).exists()).toBe(false);
            });

            it("should not be displayed when the move is being processed", async () => {
                const wrapper = getWrapper();

                await useModalStore().$patch({ is_processing_move: true });
                await useDryRunStore().$patch({ has_processed_dry_run: true });

                expect(wrapper.findComponent(DryRunPreview).exists()).toBe(false);
            });

            it("should be displayed when the dry run has been processed", async () => {
                const wrapper = getWrapper();

                await useModalStore().$patch({ is_processing_move: false });
                await useDryRunStore().$patch({ has_processed_dry_run: true });

                expect(wrapper.findComponent(DryRunPreview).exists()).toBe(true);
            });
        });

        describe("Buttons", () => {
            it("When the dry run has not been run, then only the [Move artifact] button is shown", async () => {
                const wrapper = getWrapper();

                await useDryRunStore().$patch({ has_processed_dry_run: false });

                expect(wrapper.find("[data-test=move-artifact]").isVisible()).toBe(true);
                expect(wrapper.find("[data-test=confirm-move-artifact]").isVisible()).toBe(false);
            });

            it.each([
                ["be disabled", "no tracker has been selected", null, false, true],
                ["be disabled", "the move is being processed", null, true, true],
                [
                    "not be disabled",
                    "a tracker has been selected and the dry run is not being processed",
                    102,
                    false,
                    false,
                ],
            ])(
                "The [Move artifact] button should %s when %s",
                async (what, when, selected_tracker_id, is_processing_move, is_disabled) => {
                    const wrapper = getWrapper();

                    await useModalStore().$patch({ is_processing_move });
                    await useSelectorsStore().$patch({ selected_tracker_id });

                    expect(
                        wrapper.find<HTMLButtonElement>("[data-test=move-artifact]").element
                            .disabled,
                    ).toBe(is_disabled);
                },
            );

            it("When the dry run has been run, then only the [Confirm] button is shown", async () => {
                const wrapper = getWrapper();

                await useDryRunStore().$patch({ has_processed_dry_run: true });

                expect(wrapper.find("[data-test=move-artifact]").isVisible()).toBe(false);
                expect(wrapper.find("[data-test=confirm-move-artifact]").isVisible()).toBe(true);
            });

            it.each([
                ["be disabled", "the move is not possible", false, false, true],
                ["be disabled", "the move is being processed", true, true, true],
                [
                    "not be disabled",
                    "the move is possible and is not being processed",
                    true,
                    false,
                    false,
                ],
            ])(
                "The [Confirm] button should %s when %s",
                async (what, when, is_move_possible, is_processing_move, is_disabled) => {
                    const wrapper = getWrapper();

                    await useModalStore().$patch({ is_processing_move });
                    await useDryRunStore().$patch({
                        fields_migrated: is_move_possible ? [{} as ArtifactField] : [],
                    });

                    expect(
                        wrapper.find<HTMLButtonElement>("[data-test=confirm-move-artifact]").element
                            .disabled,
                    ).toBe(is_disabled);
                },
            );
        });
    });

    describe("Submit", () => {
        it("When the [Move artifact] button is clicked, then a moveDryRun event should be dispatched", async () => {
            const wrapper = getWrapper();
            const modal_store = useModalStore();

            await modal_store.$patch({ is_processing_move: false });
            await useSelectorsStore().$patch({ selected_tracker_id: 12 });

            wrapper.find("[data-test=move-artifact]").trigger("click");

            expect(modal_store.moveDryRun).toHaveBeenCalledTimes(1);
            expect(modal_store.moveDryRun).toHaveBeenCalledWith(artifact_id);
        });

        it("When the [Confirm] button is clicked, then a move event should be dispatched", async () => {
            const wrapper = getWrapper();
            const modal_store = useModalStore();

            await modal_store.$patch({ is_processing_move: false });
            await useDryRunStore().$patch({
                fields_migrated: [{} as ArtifactField],
            });

            wrapper.find("[data-test=confirm-move-artifact]").trigger("click");

            expect(modal_store.move).toHaveBeenCalledTimes(1);
            expect(modal_store.move).toHaveBeenCalledWith(artifact_id);
        });
    });
});
