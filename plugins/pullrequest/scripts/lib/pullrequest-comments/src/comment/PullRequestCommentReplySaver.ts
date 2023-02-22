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

import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { postJSON, uri } from "@tuleap/fetch-result";
import type { ReplyCommentFormPresenter } from "./ReplyCommentFormPresenter";
import type {
    CommentReplyPayload,
    PullRequestCommentPresenter,
} from "./PullRequestCommentPresenter";

export interface SaveNewComment {
    saveReply(
        root_comment: PullRequestCommentPresenter,
        new_reply: ReplyCommentFormPresenter
    ): ResultAsync<CommentReplyPayload, Fault>;
}

const saveReplyToComment = (
    root_comment: PullRequestCommentPresenter,
    new_reply: ReplyCommentFormPresenter
): ResultAsync<CommentReplyPayload, Fault> =>
    postJSON<CommentReplyPayload>(
        uri`/api/v1/pull_requests/${new_reply.pull_request_id}/comments`,
        {
            user_id: new_reply.comment_author.user_id,
            parent_id: root_comment.id,
            content: new_reply.comment_content,
        }
    );

const saveReplyToInlineComment = (
    root_comment: PullRequestCommentPresenter,
    new_reply: ReplyCommentFormPresenter
): ResultAsync<CommentReplyPayload, Fault> => {
    let file_info;

    if (root_comment.is_file_diff_comment) {
        file_info = {
            file_path: root_comment.file_path,
            position: root_comment.position,
            unidiff_offset: root_comment.unidiff_offset,
        };
    } else if (root_comment.file) {
        file_info = {
            file_path: root_comment.file.file_path,
            position: root_comment.file.position,
            unidiff_offset: root_comment.file.unidiff_offset,
        };
    } else {
        throw new Error(
            "Tried to save new comment as inline-comment while the root comment is not an inline-comment"
        );
    }

    return postJSON<CommentReplyPayload>(
        uri`/api/v1/pull_requests/${new_reply.pull_request_id}/inline-comments`,
        {
            user_id: new_reply.comment_author.user_id,
            parent_id: root_comment.id,
            content: new_reply.comment_content,
            ...file_info,
        }
    );
};

export const PullRequestCommentNewReplySaver = (): SaveNewComment => ({
    saveReply: (
        root_comment: PullRequestCommentPresenter,
        new_reply: ReplyCommentFormPresenter
    ): ResultAsync<CommentReplyPayload, Fault> =>
        root_comment.is_inline_comment
            ? saveReplyToInlineComment(root_comment, new_reply)
            : saveReplyToComment(root_comment, new_reply),
});
