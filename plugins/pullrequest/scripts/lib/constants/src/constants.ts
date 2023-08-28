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

export type BuildStatus = "unknown" | "pending" | "fail" | "success";

export const BUILD_STATUS_UNKNOWN: BuildStatus = "unknown";
export const BUILD_STATUS_PENDING: BuildStatus = "pending";
export const BUILD_STATUS_FAILED: BuildStatus = "fail";
export const BUILD_STATUS_SUCCESS: BuildStatus = "success";

export type InlineCommentPosition = "left" | "right";

export const INLINE_COMMENT_POSITION_LEFT: InlineCommentPosition = "left";
export const INLINE_COMMENT_POSITION_RIGHT: InlineCommentPosition = "right";

export type GlobalCommentType = "comment";
export type InlineCommentType = "inline-comment";
export type CommentType = GlobalCommentType | InlineCommentType;

export const TYPE_GLOBAL_COMMENT: GlobalCommentType = "comment";
export const TYPE_INLINE_COMMENT: InlineCommentType = "inline-comment";

export type PullRequestActionEventType = "timeline-event";
export type ReviewerChangeEventType = "reviewer-change";
export type PullRequestEventType = PullRequestActionEventType | ReviewerChangeEventType;

export const TYPE_EVENT_PULLREQUEST_ACTION: PullRequestActionEventType = "timeline-event";
export const TYPE_EVENT_REVIEWER_CHANGE: ReviewerChangeEventType = "reviewer-change";

export type ActionOnPullRequestEventType = "update" | "rebase" | "merge" | "abandon" | "reopen";

export const EVENT_TYPE_UPDATE: ActionOnPullRequestEventType = "update";
export const EVENT_TYPE_REBASE: ActionOnPullRequestEventType = "rebase";
export const EVENT_TYPE_MERGE: ActionOnPullRequestEventType = "merge";
export const EVENT_TYPE_ABANDON: ActionOnPullRequestEventType = "abandon";
export const EVENT_TYPE_REOPEN: ActionOnPullRequestEventType = "reopen";

export type PullRequestMergeStatusType =
    | "conflict"
    | "no_fastforward"
    | "fastforward"
    | "unknown-merge-status";

export const PULL_REQUEST_MERGE_STATUS_CONFLICT: PullRequestMergeStatusType = "conflict";
export const PULL_REQUEST_MERGE_STATUS_NOT_FF: PullRequestMergeStatusType = "no_fastforward";
export const PULL_REQUEST_MERGE_STATUS_FF: PullRequestMergeStatusType = "fastforward";
export const PULL_REQUEST_MERGE_STATUS_UNKNOWN: PullRequestMergeStatusType = "unknown-merge-status";

export type PullRequestStatusReviewType = "review";
export type PullRequestStatusMergedType = "merge";
export type PullRequestStatusAbandonedType = "abandon";

export type PullRequestStatusType =
    | PullRequestStatusReviewType
    | PullRequestStatusMergedType
    | PullRequestStatusAbandonedType;

export const PULL_REQUEST_STATUS_REVIEW: PullRequestStatusReviewType = "review";
export const PULL_REQUEST_STATUS_MERGED: PullRequestStatusMergedType = "merge";
export const PULL_REQUEST_STATUS_ABANDON: PullRequestStatusAbandonedType = "abandon";

export type CommentTextFormat = "text" | "commonmark";
export const FORMAT_TEXT: CommentTextFormat = "text";
export const FORMAT_COMMONMARK: CommentTextFormat = "commonmark";
