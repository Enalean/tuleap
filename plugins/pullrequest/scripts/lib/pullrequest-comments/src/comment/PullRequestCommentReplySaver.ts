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
import type {
    PullRequestComment,
    NewCommentOnFile,
    NewGlobalComment,
} from "@tuleap/plugin-pullrequest-rest-api-types";
import { TYPE_INLINE_COMMENT } from "@tuleap/plugin-pullrequest-constants";
import { getContentFormat } from "../helpers/content-format";
import type { ReplyCommentFormPresenter } from "./ReplyCommentFormPresenter";
import type {
    PullRequestCommentPresenter,
    PullRequestGlobalCommentPresenter,
    PullRequestInlineCommentPresenter,
} from "./PullRequestCommentPresenter";

export interface SaveNewReplyToComment {
    saveReply(
        root_comment: PullRequestCommentPresenter,
        new_reply: ReplyCommentFormPresenter,
        is_comments_markdown_mode_enabled: boolean,
    ): ResultAsync<PullRequestComment, Fault>;
}

const saveReplyToComment = (
    root_comment: PullRequestGlobalCommentPresenter,
    new_reply: ReplyCommentFormPresenter,
    is_comments_markdown_mode_enabled: boolean,
): ResultAsync<PullRequestComment, Fault> =>
    postJSON<NewGlobalComment>(uri`/api/v1/pull_requests/${new_reply.pull_request_id}/comments`, {
        user_id: new_reply.comment_author.user_id,
        parent_id: root_comment.id,
        content: new_reply.comment_content,
        format: getContentFormat(is_comments_markdown_mode_enabled),
    }).map((new_comment) => ({ ...new_comment }));

const saveReplyToInlineComment = (
    root_comment: PullRequestInlineCommentPresenter,
    new_reply: ReplyCommentFormPresenter,
    is_comments_markdown_mode_enabled: boolean,
): ResultAsync<PullRequestComment, Fault> => {
    return postJSON<NewCommentOnFile>(
        uri`/api/v1/pull_requests/${new_reply.pull_request_id}/inline-comments`,
        {
            user_id: new_reply.comment_author.user_id,
            parent_id: root_comment.id,
            content: new_reply.comment_content,
            file_path: root_comment.file.file_path,
            position: root_comment.file.position,
            unidiff_offset: root_comment.file.unidiff_offset,
            format: getContentFormat(is_comments_markdown_mode_enabled),
        },
    ).map((new_inline_comment) => ({
        type: TYPE_INLINE_COMMENT,
        is_outdated: false,
        ...new_inline_comment,
    }));
};

export const PullRequestCommentNewReplySaver = (): SaveNewReplyToComment => ({
    saveReply: (
        root_comment: PullRequestCommentPresenter,
        new_reply: ReplyCommentFormPresenter,
        is_comments_markdown_mode_enabled: boolean,
    ): ResultAsync<PullRequestComment, Fault> =>
        root_comment.type === TYPE_INLINE_COMMENT
            ? saveReplyToInlineComment(root_comment, new_reply, is_comments_markdown_mode_enabled)
            : saveReplyToComment(root_comment, new_reply, is_comments_markdown_mode_enabled),
});
