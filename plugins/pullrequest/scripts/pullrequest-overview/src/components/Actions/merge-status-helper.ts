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

import type {
    PullRequest,
    PullRequestAbandoned,
    PullRequestInReview,
    PullRequestMerged,
} from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    BUILD_STATUS_SUCCESS,
    PULL_REQUEST_MERGE_STATUS_CONFLICT,
    PULL_REQUEST_MERGE_STATUS_FF,
    PULL_REQUEST_MERGE_STATUS_UNKNOWN,
    PULL_REQUEST_STATUS_ABANDON,
    PULL_REQUEST_STATUS_MERGED,
    PULL_REQUEST_STATUS_REVIEW,
} from "@tuleap/plugin-pullrequest-constants";

export const isPullRequestInReview = (
    pull_request: PullRequest,
): pull_request is PullRequestInReview => pull_request.status === PULL_REQUEST_STATUS_REVIEW;

export const isPullRequestAlreadyMerged = (
    pull_request: PullRequest,
): pull_request is PullRequestMerged => pull_request.status === PULL_REQUEST_STATUS_MERGED;

export const isPullRequestAbandoned = (
    pull_request: PullRequest,
): pull_request is PullRequestAbandoned => pull_request.status === PULL_REQUEST_STATUS_ABANDON;

export const isSameReferenceMerge = (pull_request: PullRequest): boolean =>
    pull_request.reference_src === pull_request.reference_dest;

export const isMergeConflicting = (pull_request: PullRequest): boolean =>
    pull_request.merge_status === PULL_REQUEST_MERGE_STATUS_CONFLICT;

export const isUnknownMerge = (pull_request: PullRequest): boolean =>
    pull_request.merge_status === PULL_REQUEST_MERGE_STATUS_UNKNOWN;

export const isFastForwardMerge = (pull_request: PullRequest): boolean =>
    pull_request.merge_status === PULL_REQUEST_MERGE_STATUS_FF;

export const hasUserPermissionToMerge = (pull_request: PullRequest): boolean =>
    pull_request.user_can_merge;

export const isCIHappy = (pull_request: PullRequest): boolean =>
    pull_request.last_build_status === BUILD_STATUS_SUCCESS;

export const isPullRequestBroken = (pull_request: PullRequest): boolean =>
    pull_request.is_git_reference_broken;

export const canPullRequestBeMerged = (
    pull_request: PullRequest,
    are_merge_commits_allowed_in_repository: boolean,
): boolean => {
    if (
        isPullRequestBroken(pull_request) ||
        !isPullRequestInReview(pull_request) ||
        !hasUserPermissionToMerge(pull_request) ||
        isSameReferenceMerge(pull_request) ||
        isMergeConflicting(pull_request) ||
        isPullRequestAlreadyMerged(pull_request) ||
        isUnknownMerge(pull_request)
    ) {
        return false;
    }

    if (isFastForwardMerge(pull_request)) {
        return true;
    }

    return !isFastForwardMerge(pull_request) && are_merge_commits_allowed_in_repository;
};
