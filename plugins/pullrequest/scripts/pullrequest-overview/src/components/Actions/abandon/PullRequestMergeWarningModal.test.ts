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

import { describe, it, expect, vi, beforeEach } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import PullRequestMergeWarningModal from "./PullRequestMergeWarningModal.vue";
import { getGlobalTestOptions } from "../../../../tests/helpers/global-options-for-tests";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    BUILD_STATUS_FAILED,
    BUILD_STATUS_PENDING,
    BUILD_STATUS_SUCCESS,
    BUILD_STATUS_UNKNOWN,
    PULL_REQUEST_MERGE_STATUS_FF,
    PULL_REQUEST_MERGE_STATUS_NOT_FF,
} from "@tuleap/plugin-pullrequest-constants";
import * as strict_inject from "@tuleap/vue-strict-inject";
import * as tlp_modal from "@tuleap/tlp-modal";
import type { Modal } from "@tuleap/tlp-modal";
import { ARE_MERGE_COMMITS_ALLOWED_IN_REPOSITORY } from "../../../constants";

vi.mock("@tuleap/vue-strict-inject");
vi.mock("@tuleap/tlp-modal", () => ({
    createModal: vi.fn(),
    EVENT_TLP_MODAL_HIDDEN: "tlp-modal-hidden",
}));

describe("PullRequestMergeWarningModal", () => {
    let merge_callback: () => void, cancel_callback: () => void;

    beforeEach(() => {
        merge_callback = vi.fn();
        cancel_callback = (): void => {
            // Do nothing
        };

        const modal_instance = {
            show: vi.fn(),
            addEventListener: vi.fn(),
        } as unknown as Modal;

        vi.spyOn(tlp_modal, "createModal").mockReturnValue(modal_instance);
    });

    const getWrapper = (pull_request: PullRequest): VueWrapper => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation((key): unknown => {
            switch (key) {
                case ARE_MERGE_COMMITS_ALLOWED_IN_REPOSITORY:
                    return true;
                default:
                    throw new Error("Tried to strictInject a value while it was not mocked");
            }
        });

        return shallowMount(PullRequestMergeWarningModal, {
            global: {
                ...getGlobalTestOptions(),
            },
            props: {
                merge_callback,
                cancel_callback,
                pull_request,
            },
        });
    };

    describe("CI status warnings", () => {
        it.each([[BUILD_STATUS_UNKNOWN], [BUILD_STATUS_PENDING], [BUILD_STATUS_FAILED]])(
            'When the CI is "%s", Then it shows a warning saying that the CI status is not happy',
            (last_build_status) => {
                const wrapper = getWrapper({
                    last_build_status,
                } as PullRequest);

                const ci_validation_warning = wrapper.find(
                    "[data-test=warning-missing-ci-validation]",
                );

                expect(ci_validation_warning.exists()).toBe(true);
                expect(ci_validation_warning.text().trim()).toBe(
                    `The last CI status is ${last_build_status}.`,
                );
            },
        );

        it('When the CI is "success", Then it should not show a CI status warning', () => {
            const wrapper = getWrapper({
                last_build_status: BUILD_STATUS_SUCCESS,
            } as PullRequest);

            expect(wrapper.find("[data-test=warning-missing-ci-validation]").exists()).toBe(false);
        });
    });

    describe("Not fast-forward warning", () => {
        it("When the merge is not fast-forward, Then it should show a warning", () => {
            const wrapper = getWrapper({
                merge_status: PULL_REQUEST_MERGE_STATUS_NOT_FF,
            } as PullRequest);

            expect(wrapper.find("[data-test=warning-not-fast-forward-merge]").exists()).toBe(true);
        });

        it("When the merge is fast-forward, Then it should not show a warning", () => {
            const wrapper = getWrapper({
                merge_status: PULL_REQUEST_MERGE_STATUS_FF,
            } as PullRequest);

            expect(wrapper.find("[data-test=warning-not-fast-forward-merge]").exists()).toBe(false);
        });
    });

    it("When the user clicks on [Merge anyway], Then it should call the merge_callback", () => {
        const wrapper = getWrapper({
            merge_status: PULL_REQUEST_MERGE_STATUS_FF,
        } as PullRequest);

        wrapper.find("[data-test=merge-anyway-button]").trigger("click");

        expect(merge_callback).toHaveBeenCalledOnce();
    });
});
