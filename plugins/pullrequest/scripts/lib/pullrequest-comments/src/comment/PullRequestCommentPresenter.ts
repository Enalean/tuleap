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

import type { InlineCommentPosition, PullRequestUser } from "../types";

export type CommentType = "inline-comment" | "comment" | "timeline-event";
export const TYPE_INLINE_COMMENT: CommentType = "inline-comment";
export const TYPE_GLOBAL_COMMENT: CommentType = "comment";
export const TYPE_EVENT_COMMENT: CommentType = "timeline-event";

export interface CommentReplyPayload {
    readonly id: number;
    readonly content: string;
    readonly user: PullRequestUser;
    readonly post_date: string;
    readonly parent_id: number;
    readonly color: string;
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
    color: string;
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
        color: "",
    }),
};

function replaceLineReturns(content: string): string {
    return content.replace(/(?:\r\n|\r|\n)/g, "<br/>");
}
