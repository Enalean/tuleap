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
    PullRequestInReview,
    PullRequestMerged,
    PullRequestAbandoned,
    User,
} from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    FORMAT_COMMONMARK,
    BUILD_STATUS_SUCCESS,
    PULL_REQUEST_MERGE_STATUS_FF,
    PULL_REQUEST_STATUS_REVIEW,
    PULL_REQUEST_STATUS_MERGED,
    PULL_REQUEST_STATUS_ABANDON,
} from "@tuleap/plugin-pullrequest-constants";

type PullRequestStubData = Omit<PullRequest, "status" | "status_info">;

const user: User = {
    id: 105,
    display_name: "John Doe (jdoe)",
    user_url: "url/to/user_profile.html",
    avatar_url: "url/to/user_avatar.png",
};

const default_pull_request: PullRequest = {
    id: 12,
    reference_src: "source-reference",
    reference_dest: "destination-reference",
    head_reference: "head-reference",
    branch_src: "source-branch",
    branch_dest: "master",
    creation_date: "2023-12-18T10:30:00Z",
    title: "Please pull my request",
    raw_title: "Please pull my request",
    description: "The pull-request's **description**",
    raw_description: "The pull-request's **description**",
    post_processed_description: "The pull-request's <em>description</em>",
    description_format: FORMAT_COMMONMARK,
    is_git_reference_broken: false,
    last_build_date: "2023-12-18T10:40:00Z",
    last_build_status: BUILD_STATUS_SUCCESS,
    merge_status: PULL_REQUEST_MERGE_STATUS_FF,
    status: PULL_REQUEST_STATUS_REVIEW,
    repository: {
        project: {
            id: 102,
            icon: "",
            uri: "/uri/to/git-project",
            label: "Git project",
        },
    },
    user_id: 102,
    user_can_abandon: true,
    user_can_merge: true,
    user_can_reopen: true,
    user_can_update_labels: true,
    user_can_update_title_and_description: true,
    status_info: null,
    short_stat: {
        lines_added: 12,
        lines_removed: 1000,
    },
    repository_dest: {
        clone_ssh_url: "ssh://gitolite@example.com/git-project/the-repo.git",
        clone_http_url: "https://example.com/plugins/git/git-project/the-repo.git",
    },
    creator: user,
};

type StubPullRequest = {
    buildOpenPullRequest(optional_data?: Partial<PullRequestStubData>): PullRequestInReview;
    buildMergedPullRequest(optional_data?: Partial<PullRequestStubData>): PullRequestMerged;
    buildAbandonedPullRequest(optional_data?: Partial<PullRequestStubData>): PullRequestAbandoned;
};

export const PullRequestStub: StubPullRequest = {
    buildOpenPullRequest: (optional_data = {}): PullRequestInReview => ({
        ...default_pull_request,
        status: PULL_REQUEST_STATUS_REVIEW,
        status_info: null,
        ...optional_data,
    }),
    buildMergedPullRequest: (optional_data = {}): PullRequestMerged => ({
        ...default_pull_request,
        status: PULL_REQUEST_STATUS_MERGED,
        status_info: {
            status_type: PULL_REQUEST_STATUS_MERGED,
            status_updater: user,
            status_date: "2023-12-18T11:10:00Z",
        },
        ...optional_data,
    }),
    buildAbandonedPullRequest: (optional_data = {}): PullRequestAbandoned => ({
        ...default_pull_request,
        status: PULL_REQUEST_STATUS_ABANDON,
        status_info: {
            status_type: PULL_REQUEST_STATUS_ABANDON,
            status_updater: user,
            status_date: "2023-12-18T11:10:00Z",
        },
        ...optional_data,
    }),
};
