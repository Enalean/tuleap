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
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    BUILD_STATUS_PENDING,
    BUILD_STATUS_SUCCESS,
    PULL_REQUEST_MERGE_STATUS_CONFLICT,
    PULL_REQUEST_MERGE_STATUS_FF,
    PULL_REQUEST_MERGE_STATUS_NOT_FF,
    PULL_REQUEST_MERGE_STATUS_UNKNOWN,
    PULL_REQUEST_STATUS_MERGED,
    PULL_REQUEST_STATUS_REVIEW,
} from "@tuleap/plugin-pullrequest-constants";
import * as tuleap_api from "../../../api/tuleap-rest-querier";
import { getGlobalTestOptions } from "../../../../tests/helpers/global-options-for-tests";
import PullRequestMergeButton from "./PullRequestMergeButton.vue";
import PullRequestMergeWarningModal from "../abandon/PullRequestMergeWarningModal.vue";
import * as strict_inject from "@tuleap/vue-strict-inject";
import type { DisplayErrorCallback, PostPullRequestUpdateCallback } from "../../../constants";
import {
    DISPLAY_TULEAP_API_ERROR,
    ARE_MERGE_COMMITS_ALLOWED_IN_REPOSITORY,
    POST_PULL_REQUEST_UPDATE_CALLBACK,
    PULL_REQUEST_ID_KEY,
} from "../../../constants";

const current_pull_request_id = 15;

const getPullRequest = (pull_request_data: Partial<PullRequest>): PullRequest =>
    ({
        id: current_pull_request_id,
        user_can_merge: true,
        status: PULL_REQUEST_STATUS_REVIEW,
        merge_status: PULL_REQUEST_MERGE_STATUS_FF,
        reference_src: "d592fa08f3604c6fc81c69c1a3b4426cff83a73b",
        reference_dest: "66728d6153adbd267f3b1b3a1250bab6bd2ee3d0",
        ...pull_request_data,
    }) as PullRequest;

const isButtonDisabled = (wrapper: VueWrapper): boolean =>
    wrapper.find("[data-test=merge-button]").attributes("disabled") !== undefined;

const isButtonOutlined = (wrapper: VueWrapper): boolean =>
    wrapper.find("[data-test=merge-button]").classes("tlp-button-outline") === true;

vi.mock("@tuleap/vue-strict-inject");
vi.mock("./tuleap-rest-querier");

describe("PullRequestMergeButton", () => {
    let are_merge_commits_allowed_in_repository: boolean,
        on_error_callback: DisplayErrorCallback,
        post_update_callback: PostPullRequestUpdateCallback;

    beforeEach(() => {
        are_merge_commits_allowed_in_repository = true;
        on_error_callback = vi.fn();
        post_update_callback = vi.fn();
    });

    const getWrapper = (pull_request_data: Partial<PullRequest> = {}): VueWrapper => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation((key): unknown => {
            switch (key) {
                case ARE_MERGE_COMMITS_ALLOWED_IN_REPOSITORY:
                    return are_merge_commits_allowed_in_repository;
                case DISPLAY_TULEAP_API_ERROR:
                    return on_error_callback;
                case POST_PULL_REQUEST_UPDATE_CALLBACK:
                    return post_update_callback;
                case PULL_REQUEST_ID_KEY:
                    return current_pull_request_id;
                default:
                    throw new Error("Tried to strictInject a value while it was not mocked");
            }
        });

        return shallowMount(PullRequestMergeButton, {
            global: {
                ...getGlobalTestOptions(),
            },
            props: {
                pull_request: getPullRequest(pull_request_data),
            },
        });
    };

    describe("should not be displayed", () => {
        it("When the pull-request is already merged", () => {
            const wrapper = getWrapper({ status: PULL_REQUEST_STATUS_MERGED });
            expect(wrapper.element.children).toBeUndefined();
        });

        it("When the user has not the right to merge", () => {
            const wrapper = getWrapper({
                status: PULL_REQUEST_STATUS_MERGED,
                user_can_merge: false,
            });
            expect(wrapper.element.children).toBeUndefined();
        });

        it("When the pull request git reference is broken", () => {
            const wrapper = getWrapper({
                status: PULL_REQUEST_STATUS_MERGED,
                user_can_merge: true,
                is_git_reference_broken: true,
            });
            expect(wrapper.element.children).toBeUndefined();
        });
    });

    describe("should be disabled and outlined", () => {
        it("When the pull-request is not fast-forward, and merge commits are not allowed", () => {
            are_merge_commits_allowed_in_repository = false;
            const wrapper = getWrapper({ merge_status: PULL_REQUEST_MERGE_STATUS_NOT_FF });

            expect(isButtonDisabled(wrapper)).toBe(true);
            expect(isButtonOutlined(wrapper)).toBe(true);
        });

        it("When there is a merge conflict", () => {
            const wrapper = getWrapper({ merge_status: PULL_REQUEST_MERGE_STATUS_CONFLICT });
            expect(isButtonDisabled(wrapper)).toBe(true);
            expect(isButtonOutlined(wrapper)).toBe(true);
        });

        it("When the merge is unknown", () => {
            const wrapper = getWrapper({ merge_status: PULL_REQUEST_MERGE_STATUS_UNKNOWN });
            expect(isButtonDisabled(wrapper)).toBe(true);
            expect(isButtonOutlined(wrapper)).toBe(true);
        });

        it("When the source reference and the destination reference are the same", () => {
            const wrapper = getWrapper({
                reference_src: "d592fa08f3604c6fc81c69c1a3b4426cff83a73b",
                reference_dest: "d592fa08f3604c6fc81c69c1a3b4426cff83a73b",
            });
            expect(isButtonDisabled(wrapper)).toBe(true);
            expect(isButtonOutlined(wrapper)).toBe(true);
        });
    });

    describe("should be enabled", () => {
        it.each([[true], [false]])(
            "When the pull-request is fast-forward and are_merge_commits_allowed_in_repository is %s",
            (is_fast_forward_only) => {
                are_merge_commits_allowed_in_repository = is_fast_forward_only;

                const wrapper = getWrapper({
                    status: PULL_REQUEST_STATUS_REVIEW,
                    merge_status: PULL_REQUEST_MERGE_STATUS_FF,
                });
                expect(isButtonDisabled(wrapper)).toBe(false);
            },
        );

        it("When the pull-request is not fast-forward but merging not fast-forward pull-requests is allowed", () => {
            are_merge_commits_allowed_in_repository = true;

            const wrapper = getWrapper({
                status: PULL_REQUEST_STATUS_REVIEW,
                merge_status: PULL_REQUEST_MERGE_STATUS_NOT_FF,
            });
            expect(isButtonDisabled(wrapper)).toBe(false);
        });
    });

    describe("should not be outlined", () => {
        it("when the merge is fast-forward and the last CI build is successful", () => {
            const wrapper = getWrapper({
                status: PULL_REQUEST_STATUS_REVIEW,
                merge_status: PULL_REQUEST_MERGE_STATUS_FF,
                last_build_status: BUILD_STATUS_SUCCESS,
            });
            expect(isButtonOutlined(wrapper)).toBe(false);
        });
    });

    describe("merge", () => {
        it(`Given that the pull-request is fast-forward, and that the CI is happy
            When the user clicks on the button
            Then it should merge the pull-request
            And call the post_pull_request_update callback`, async () => {
            const updated_pull_request = getPullRequest({
                status: PULL_REQUEST_STATUS_MERGED,
            });

            vi.spyOn(tuleap_api, "mergePullRequest").mockReturnValue(okAsync(updated_pull_request));

            await getWrapper({
                status: PULL_REQUEST_STATUS_REVIEW,
                merge_status: PULL_REQUEST_MERGE_STATUS_FF,
                last_build_status: BUILD_STATUS_SUCCESS,
            })
                .find<HTMLButtonElement>("[data-test=merge-button]")
                .element.click();

            expect(tuleap_api.mergePullRequest).toHaveBeenCalledOnce();
            expect(tuleap_api.mergePullRequest).toHaveBeenCalledWith(current_pull_request_id);

            expect(post_update_callback).toHaveBeenCalledOnce();
            expect(post_update_callback).toHaveBeenCalledWith(updated_pull_request);
        });

        it(`Given that the pull-request is fast-forward, but the CI is not happy
            When the user clicks on the button
            Then it should open the merge merge modal to ask for confirmation`, async () => {
            vi.spyOn(tuleap_api, "mergePullRequest");

            const wrapper = getWrapper({
                status: PULL_REQUEST_STATUS_REVIEW,
                merge_status: PULL_REQUEST_MERGE_STATUS_FF,
                last_build_status: BUILD_STATUS_PENDING,
            });

            await wrapper.find<HTMLButtonElement>("[data-test=merge-button]").element.click();

            expect(tuleap_api.mergePullRequest).not.toHaveBeenCalled();
            expect(wrapper.findComponent(PullRequestMergeWarningModal).exists()).toBe(true);
        });

        it(`Given that the pull-request is not fast-forward but merge commits are allowed
            When the user clicks on the button
            Then it should open the merge merge modal to ask for confirmation`, async () => {
            vi.spyOn(tuleap_api, "mergePullRequest");

            const wrapper = getWrapper({
                status: PULL_REQUEST_STATUS_REVIEW,
                merge_status: PULL_REQUEST_MERGE_STATUS_NOT_FF,
                last_build_status: BUILD_STATUS_SUCCESS,
            });

            await wrapper.find<HTMLButtonElement>("[data-test=merge-button]").element.click();

            expect(tuleap_api.mergePullRequest).not.toHaveBeenCalled();
            expect(wrapper.findComponent(PullRequestMergeWarningModal).exists()).toBe(true);
        });

        it(`When an error occurres while merging the pull-request
            Then it should trigger the on_error_callback with the current fault`, async () => {
            const tuleap_api_error = Fault.fromMessage("Forbidden");

            vi.spyOn(tuleap_api, "mergePullRequest").mockReturnValue(errAsync(tuleap_api_error));

            await getWrapper({
                status: PULL_REQUEST_STATUS_REVIEW,
                merge_status: PULL_REQUEST_MERGE_STATUS_FF,
                last_build_status: BUILD_STATUS_SUCCESS,
            })
                .find<HTMLButtonElement>("[data-test=merge-button]")
                .element.click();

            expect(on_error_callback).toHaveBeenCalledOnce();
            expect(on_error_callback).toHaveBeenCalledWith(tuleap_api_error);
        });
    });
});
