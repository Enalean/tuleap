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

import { describe, it, expect } from "vitest";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    BUILD_STATUS_FAILED,
    BUILD_STATUS_PENDING,
    BUILD_STATUS_SUCCESS,
    BUILD_STATUS_UNKNOWN,
    PULL_REQUEST_MERGE_STATUS_CONFLICT,
    PULL_REQUEST_MERGE_STATUS_FF,
    PULL_REQUEST_MERGE_STATUS_NOT_FF,
    PULL_REQUEST_MERGE_STATUS_UNKNOWN,
    PULL_REQUEST_STATUS_ABANDON,
    PULL_REQUEST_STATUS_MERGED,
    PULL_REQUEST_STATUS_REVIEW,
} from "@tuleap/plugin-pullrequest-constants";
import {
    canPullRequestBeMerged,
    hasUserPermissionToMerge,
    isPullRequestAlreadyMerged,
    isPullRequestAbandoned,
    isCIHappy,
    isFastForwardMerge,
    isMergeConflicting,
    isPullRequestInReview,
    isSameReferenceMerge,
    isUnknownMerge,
} from "./merge-status-helper";

describe("merge-status-helper", () => {
    it.each([
        [true, PULL_REQUEST_STATUS_REVIEW],
        [false, PULL_REQUEST_STATUS_MERGED],
        [false, PULL_REQUEST_STATUS_ABANDON],
    ])(
        "isPullRequestInReview() should return %s when the pull-request status is %s",
        (expected_result, status) => {
            expect(isPullRequestInReview({ status } as PullRequest)).toBe(expected_result);
        },
    );

    it.each([
        [false, PULL_REQUEST_STATUS_REVIEW],
        [true, PULL_REQUEST_STATUS_MERGED],
        [false, PULL_REQUEST_STATUS_ABANDON],
    ])(
        "isPullRequestAlreadyMerged() should return %s when the pull-request status is %s",
        (expected_result, status) => {
            expect(isPullRequestAlreadyMerged({ status } as PullRequest)).toBe(expected_result);
        },
    );

    it.each([
        [false, PULL_REQUEST_STATUS_REVIEW],
        [false, PULL_REQUEST_STATUS_MERGED],
        [true, PULL_REQUEST_STATUS_ABANDON],
    ])(
        "isPullRequestAbandoned() should return %s when the pull-request status is %s",
        (expected_result, status) => {
            expect(isPullRequestAbandoned({ status } as PullRequest)).toBe(expected_result);
        },
    );

    it.each([
        [
            false,
            "the source is different than the destination",
            "d592fa08f3604c6fc81c69c1a3b4426cff83a73b",
            "66728d6153adbd267f3b1b3a1250bab6bd2ee3d0",
        ],
        [
            true,
            "the source and the destination are the same",
            "d592fa08f3604c6fc81c69c1a3b4426cff83a73b",
            "d592fa08f3604c6fc81c69c1a3b4426cff83a73b",
        ],
    ])(
        "isSameReferenceMerge() should return %s when %s",
        (expected_result, when, reference_src, reference_dest) => {
            expect(
                isSameReferenceMerge({
                    reference_src,
                    reference_dest,
                } as PullRequest),
            ).toBe(expected_result);
        },
    );

    it.each([
        [false, PULL_REQUEST_MERGE_STATUS_FF],
        [false, PULL_REQUEST_MERGE_STATUS_NOT_FF],
        [false, PULL_REQUEST_MERGE_STATUS_UNKNOWN],
        [true, PULL_REQUEST_MERGE_STATUS_CONFLICT],
    ])(
        "isMergeConflicting() should return %s when the pull-request merge_status is %s",
        (expected_result, merge_status) => {
            expect(isMergeConflicting({ merge_status } as PullRequest)).toBe(expected_result);
        },
    );

    it.each([
        [false, PULL_REQUEST_MERGE_STATUS_FF],
        [false, PULL_REQUEST_MERGE_STATUS_NOT_FF],
        [true, PULL_REQUEST_MERGE_STATUS_UNKNOWN],
        [false, PULL_REQUEST_MERGE_STATUS_CONFLICT],
    ])(
        "isUnknownMerge() should return %s when the pull-request merge_status is %s",
        (expected_result, merge_status) => {
            expect(isUnknownMerge({ merge_status } as PullRequest)).toBe(expected_result);
        },
    );

    it.each([
        [true, PULL_REQUEST_MERGE_STATUS_FF],
        [false, PULL_REQUEST_MERGE_STATUS_NOT_FF],
        [false, PULL_REQUEST_MERGE_STATUS_UNKNOWN],
        [false, PULL_REQUEST_MERGE_STATUS_CONFLICT],
    ])(
        "isFastForwardMerge() should return %s when the pull-request merge_status is %s",
        (expected_result, merge_status) => {
            expect(isFastForwardMerge({ merge_status } as PullRequest)).toBe(expected_result);
        },
    );

    it.each([
        [true, true],
        [false, false],
    ])(
        "hasUserPermissionToMerge() should return %s when user_can_merge is %s",
        (expected_result, user_can_merge) => {
            expect(hasUserPermissionToMerge({ user_can_merge } as PullRequest)).toBe(
                expected_result,
            );
        },
    );

    it.each([
        [false, BUILD_STATUS_UNKNOWN],
        [false, BUILD_STATUS_PENDING],
        [false, BUILD_STATUS_FAILED],
        [true, BUILD_STATUS_SUCCESS],
    ])(
        "isCIHappy() should return %s when the last_build_status is %s",
        (should_be_happy, last_build_status) => {
            expect(isCIHappy({ last_build_status } as PullRequest)).toBe(should_be_happy);
        },
    );

    describe("canPullRequestBeMerged()", () => {
        it(`Given that the pull-request:
            - is open
            - is fast-forward
            - has a different source and destination
            - and that the user has the permission to merge
            Then it should return true`, () => {
            const are_merge_commits_allowed_in_repository = true;
            expect(
                canPullRequestBeMerged(
                    {
                        status: PULL_REQUEST_STATUS_REVIEW,
                        merge_status: PULL_REQUEST_MERGE_STATUS_FF,
                        user_can_merge: true,
                        reference_src: "d592fa08f3604c6fc81c69c1a3b4426cff83a73b",
                        reference_dest: "66728d6153adbd267f3b1b3a1250bab6bd2ee3d0",
                    } as PullRequest,
                    are_merge_commits_allowed_in_repository,
                ),
            ).toBe(true);
        });

        it.each([
            [true, true],
            [false, false],
        ])(
            `Given that the pull-request:
            - is open
            - is NOT fast-forward
            - are_merge_commits_allowed_in_repository is %s
            - has a different source and destination
            - and that the user has the permission to merge
            Then it should return %s`,
            (are_merge_commits_allowed_in_repository, expected_result) => {
                expect(
                    canPullRequestBeMerged(
                        {
                            status: PULL_REQUEST_STATUS_REVIEW,
                            merge_status: PULL_REQUEST_MERGE_STATUS_NOT_FF,
                            user_can_merge: true,
                            reference_src: "d592fa08f3604c6fc81c69c1a3b4426cff83a73b",
                            reference_dest: "66728d6153adbd267f3b1b3a1250bab6bd2ee3d0",
                        } as PullRequest,
                        are_merge_commits_allowed_in_repository,
                    ),
                ).toBe(expected_result);
            },
        );
    });
});
