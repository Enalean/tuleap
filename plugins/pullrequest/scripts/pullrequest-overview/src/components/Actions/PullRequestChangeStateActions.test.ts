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
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    PULL_REQUEST_MERGE_STATUS_CONFLICT,
    PULL_REQUEST_MERGE_STATUS_FF,
    PULL_REQUEST_MERGE_STATUS_NOT_FF,
    PULL_REQUEST_MERGE_STATUS_UNKNOWN,
    PULL_REQUEST_STATUS_ABANDON,
    PULL_REQUEST_STATUS_MERGED,
    PULL_REQUEST_STATUS_REVIEW,
} from "@tuleap/plugin-pullrequest-constants";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { ARE_MERGE_COMMITS_ALLOWED_IN_REPOSITORY } from "../../constants";
import { getGlobalTestOptions } from "../../../tests/helpers/global-options-for-tests";

import PullRequestChangeStateActions from "./PullRequestChangeStateActions.vue";

const getPullRequest = (pull_request_data: Partial<PullRequest>): PullRequest =>
    ({
        user_can_merge: true,
        status: PULL_REQUEST_STATUS_REVIEW,
        merge_status: PULL_REQUEST_MERGE_STATUS_FF,
        reference_src: "d592fa08f3604c6fc81c69c1a3b4426cff83a73b",
        reference_dest: "66728d6153adbd267f3b1b3a1250bab6bd2ee3d0",
        ...pull_request_data,
    }) as PullRequest;

vi.mock("@tuleap/vue-strict-inject");

describe("PullRequestChangeStateActions", () => {
    let are_merge_commits_allowed_in_repository: boolean;

    beforeEach(() => {
        are_merge_commits_allowed_in_repository = true;
    });

    const getWrapper = (pull_request_data: Partial<PullRequest> = {}): VueWrapper => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation((key): unknown => {
            switch (key) {
                case ARE_MERGE_COMMITS_ALLOWED_IN_REPOSITORY:
                    return are_merge_commits_allowed_in_repository;
                default:
                    throw new Error("Tried to strictInject a value while it was not mocked");
            }
        });

        return shallowMount(PullRequestChangeStateActions, {
            global: {
                ...getGlobalTestOptions(),
            },
            props: {
                pull_request: getPullRequest(pull_request_data),
            },
        });
    };

    describe("Hidden/displayed section", () => {
        it("Should not be rendered when the pull-request is in review and the user has not the permission to merge", () => {
            const wrapper = getWrapper({
                status: PULL_REQUEST_STATUS_REVIEW,
                user_can_merge: false,
            });

            expect(wrapper.element.children).toBeUndefined();
        });

        it.each([
            [
                "when the pull-request is in review and the user can merge",
                {
                    status: PULL_REQUEST_STATUS_REVIEW,
                    user_can_merge: true,
                },
            ],
            ["when the pull-request is already merged", { status: PULL_REQUEST_STATUS_MERGED }],
            ["when the pull-request is abandoned", { status: PULL_REQUEST_STATUS_ABANDON }],
        ])("Should be rendered %s", (when, pull_request_data) => {
            const wrapper = getWrapper(pull_request_data);

            expect(wrapper.find("[data-test=state-action-buttons]").exists()).toBe(true);
        });
    });

    describe("Warnings", () => {
        it("displays a warning when the merge destination is unknown", () => {
            const wrapper = getWrapper({ merge_status: PULL_REQUEST_MERGE_STATUS_UNKNOWN });
            expect(wrapper.find("[data-test=merge-status-warning]").exists()).toBe(true);
        });

        it("displays a warning when the source and destination are the same reference", () => {
            const wrapper = getWrapper({
                reference_src: "d592fa08f3604c6fc81c69c1a3b4426cff83a73b",
                reference_dest: "d592fa08f3604c6fc81c69c1a3b4426cff83a73b",
            });
            expect(wrapper.find("[data-test=merge-status-warning]").exists()).toBe(true);
        });

        it("does not display a warning when the pull-request is not in review", () => {
            const wrapper = getWrapper({
                status: PULL_REQUEST_STATUS_ABANDON,
                merge_status: PULL_REQUEST_MERGE_STATUS_UNKNOWN,
                reference_src: "d592fa08f3604c6fc81c69c1a3b4426cff83a73b",
                reference_dest: "d592fa08f3604c6fc81c69c1a3b4426cff83a73b",
            });
            expect(wrapper.find("[data-test=merge-status-warning]").exists()).toBe(false);
        });
    });

    describe("Errors", () => {
        it("displays an error when the merge is not fast forward and merge commits are forbidden", () => {
            are_merge_commits_allowed_in_repository = false;

            const wrapper = getWrapper({ merge_status: PULL_REQUEST_MERGE_STATUS_NOT_FF });
            expect(wrapper.find("[data-test=merge-status-error]").exists()).toBe(true);
        });

        it("displays an error when there is a merge conflict", () => {
            const wrapper = getWrapper({ merge_status: PULL_REQUEST_MERGE_STATUS_CONFLICT });
            expect(wrapper.find("[data-test=merge-status-error]").exists()).toBe(true);
        });

        it("does not display errors when the pull-request is not in review", () => {
            const wrapper = getWrapper({
                status: PULL_REQUEST_STATUS_ABANDON,
                merge_status: PULL_REQUEST_MERGE_STATUS_CONFLICT,
            });
            expect(wrapper.find("[data-test=merge-status-error]").exists()).toBe(false);
        });
    });

    describe("it displays no warning or error", () => {
        it("when the pull-request is fast-forward", () => {
            const wrapper = getWrapper({ merge_status: PULL_REQUEST_MERGE_STATUS_FF });

            expect(wrapper.find("[data-test=merge-status-warning]").exists()).toBe(false);
            expect(wrapper.find("[data-test=merge-status-error]").exists()).toBe(false);
        });

        it("when the pull-request is not fast-forward but merge commits are allowed", () => {
            are_merge_commits_allowed_in_repository = true;

            const wrapper = getWrapper({ merge_status: PULL_REQUEST_MERGE_STATUS_NOT_FF });

            expect(wrapper.find("[data-test=merge-status-warning]").exists()).toBe(false);
            expect(wrapper.find("[data-test=merge-status-error]").exists()).toBe(false);
        });
    });
});
