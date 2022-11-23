/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import {
    getUserAbandonedPullRequest,
    getUserMergePullRequest,
    getUserRebasePullRequest,
    getUserUpdatePullRequest,
} from "../gettext-catalog";
import type { FileDiffCommentPayload, InlineCommentPosition, PullRequestUser } from "./types";

export type CommentType = "inline-comment" | "comment" | "timeline-event";
export const TYPE_INLINE_COMMENT: CommentType = "inline-comment";
export const TYPE_GLOBAL_COMMENT: CommentType = "comment";
export const TYPE_EVENT_COMMENT: CommentType = "timeline-event";

export interface State {
    readonly href: (name: string, url_parameters: Record<string, unknown>) => string;
}

export interface PullRequestData {
    readonly id: number;
}

export interface TimelineEventPayload {
    readonly id: number;
    readonly is_inline_comment: boolean;
    readonly post_date: string;
    readonly content: string;
    readonly type: CommentType;
    readonly event_type?: string;
    readonly is_outdated: boolean;
    readonly user: PullRequestUser;
    readonly parent_id: number;
    readonly file_path?: string;
    readonly position?: InlineCommentPosition;
    readonly unidiff_offset?: number;
}

export interface CommentReplyPayload {
    readonly id: number;
    readonly content: string;
    readonly user: PullRequestUser;
    readonly post_date: string;
    readonly parent_id: number;
}

interface PullRequestCommentFile {
    readonly file_path: string;
    readonly file_url: string;
    readonly position: InlineCommentPosition;
    readonly unidiff_offset: number;
}

interface CommonComment {
    readonly id: number;
    readonly user: PullRequestUser;
    readonly content: string;
    readonly type: CommentType;
    readonly is_outdated: boolean;
    readonly is_inline_comment: boolean;
    readonly post_date: string;
    readonly file?: PullRequestCommentFile;
    readonly parent_id: number;
}

export interface PullRequestGlobalCommentPresenter extends CommonComment {
    readonly is_file_diff_comment: false;
}

export interface PullRequestInlineCommentPresenter extends CommonComment {
    readonly unidiff_offset: number;
    readonly position: InlineCommentPosition;
    readonly file_path: string;
    readonly is_file_diff_comment: true;
}

export type PullRequestCommentPresenter =
    | PullRequestGlobalCommentPresenter
    | PullRequestInlineCommentPresenter;

export const PullRequestCommentPresenter = {
    fromFileDiffComment: (comment: FileDiffCommentPayload): PullRequestInlineCommentPresenter => ({
        id: comment.id,
        user: comment.user,
        post_date: comment.post_date,
        content: replaceLineReturns(comment.content),
        type: TYPE_INLINE_COMMENT,
        is_outdated: false,
        is_inline_comment: true,
        unidiff_offset: comment.unidiff_offset,
        position: comment.position,
        file_path: comment.file_path,
        parent_id: comment.parent_id,
        is_file_diff_comment: true,
    }),
    fromTimelineEvent: (
        $state: State,
        event: TimelineEventPayload,
        pull_request: PullRequestData
    ): PullRequestCommentPresenter => {
        const is_inline_comment = event.type === TYPE_INLINE_COMMENT;
        const file =
            is_inline_comment && event.file_path && event.position && event.unidiff_offset
                ? {
                      file: {
                          file_url: $state.href("diff", {
                              id: pull_request.id,
                              file_path: event.file_path,
                          }),
                          file_path: event.file_path,
                          unidiff_offset: event.unidiff_offset,
                          position: event.position,
                      },
                  }
                : {};

        return {
            id: event.id,
            user: event.user,
            content: getContentMessage(event),
            type: event.type,
            is_outdated: event.is_outdated,
            is_inline_comment,
            post_date: event.post_date,
            parent_id: event.parent_id,
            ...file,
            is_file_diff_comment: false,
        };
    },
    fromCommentReply: (
        parent_comment: PullRequestCommentPresenter,
        reply: CommentReplyPayload
    ): PullRequestCommentPresenter => ({
        id: reply.id,
        user: reply.user,
        post_date: reply.post_date,
        content: replaceLineReturns(reply.content),
        type: parent_comment.type,
        is_outdated: false,
        is_inline_comment: parent_comment.is_inline_comment,
        parent_id: reply.parent_id,
        is_file_diff_comment: false,
    }),
};

function replaceLineReturns(content: string): string {
    return content.replace(/(?:\r\n|\r|\n)/g, "<br/>");
}

function getContentMessage(event: TimelineEventPayload): string {
    if (event.type === TYPE_GLOBAL_COMMENT || event.type === TYPE_INLINE_COMMENT) {
        return replaceLineReturns(event.content);
    }

    return getTimelineEventMessage(event);
}

function getTimelineEventMessage(event: TimelineEventPayload): string {
    switch (event.event_type) {
        case "update":
            return getUserUpdatePullRequest();
        case "rebase":
            return getUserRebasePullRequest();
        case "merge":
            return getUserMergePullRequest();
        case "abandon":
            return getUserAbandonedPullRequest();
        default:
            return "";
    }
}
