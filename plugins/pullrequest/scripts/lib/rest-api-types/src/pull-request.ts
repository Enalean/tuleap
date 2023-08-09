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
    BuildStatus,
    PullRequestMergeStatusType,
    PullRequestStatusAbandonedType,
    PullRequestStatusMergedType,
    PullRequestStatusReviewType,
    PullRequestStatusType,
} from "@tuleap/plugin-pullrequest-constants";
import type { User, ProjectReference } from "@tuleap/core-rest-api-types";

export interface PullRequestStatusInfo {
    readonly status_type: PullRequestStatusType;
    readonly status_date: string;
    readonly status_updater: User;
}

interface CommonPullRequest {
    readonly id: number;
    readonly title: string;
    readonly raw_title: string;
    readonly creation_date: string;
    readonly short_stat: {
        readonly lines_added: number;
        readonly lines_removed: number;
    };
    readonly reference_src: string;
    readonly reference_dest: string;
    readonly branch_src: string;
    readonly branch_dest: string;
    readonly last_build_status: BuildStatus;
    readonly last_build_date: string;
    readonly user_id: number;
    readonly repository_dest: {
        readonly clone_http_url: string;
        readonly clone_ssh_url: string;
    };
    readonly head_reference: string;
    readonly description: string;
    readonly raw_description: string;
    readonly user_can_merge: boolean;
    readonly user_can_reopen: boolean;
    readonly user_can_abandon: boolean;
    readonly user_can_update_labels: boolean;
    readonly user_can_update_title_and_description: boolean;
    readonly status: PullRequestStatusType;
    readonly merge_status: PullRequestMergeStatusType;
    readonly status_info: PullRequestStatusInfo | null;
    readonly repository: {
        readonly project: ProjectReference;
    };
}

export type PullRequestInReview = CommonPullRequest & {
    readonly status: PullRequestStatusReviewType;
    readonly status_info: null;
};

export type PullRequestMerged = CommonPullRequest & {
    readonly status: PullRequestStatusMergedType;
    readonly status_info: PullRequestStatusInfo & {
        readonly status_type: PullRequestStatusMergedType;
    };
};

export type PullRequestAbandoned = CommonPullRequest & {
    readonly status: PullRequestStatusAbandonedType;
    readonly status_info: PullRequestStatusInfo & {
        readonly status_type: PullRequestStatusAbandonedType;
    };
};

export type PullRequest = PullRequestInReview | PullRequestMerged | PullRequestAbandoned;

export interface ReviewersCollection {
    readonly users: ReadonlyArray<User>;
}
