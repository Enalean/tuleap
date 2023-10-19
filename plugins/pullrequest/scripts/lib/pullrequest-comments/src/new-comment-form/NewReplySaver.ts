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
import { TYPE_INLINE_COMMENT, FORMAT_COMMONMARK } from "@tuleap/plugin-pullrequest-constants";
import type { NewCommentFormPresenter } from "./NewCommentFormPresenter";
import type {
    ReplyCreationContext,
    ReplyToCommentOnFileContext,
    ReplyToGlobalCommentContext,
    SaveComment,
} from "./types";

const saveReplyToComment = (
    context: ReplyToGlobalCommentContext,
    new_comment: NewCommentFormPresenter,
): ResultAsync<PullRequestComment, Fault> =>
    postJSON<NewGlobalComment>(uri`/api/v1/pull_requests/${context.pull_request_id}/comments`, {
        user_id: context.user_id,
        parent_id: context.root_comment.id,
        content: new_comment.comment_content,
        format: FORMAT_COMMONMARK,
    });

const saveReplyToInlineComment = (
    context: ReplyToCommentOnFileContext,
    new_comment: NewCommentFormPresenter,
): ResultAsync<PullRequestComment, Fault> => {
    return postJSON<NewCommentOnFile>(
        uri`/api/v1/pull_requests/${context.pull_request_id}/inline-comments`,
        {
            user_id: context.user_id,
            parent_id: context.root_comment.id,
            content: new_comment.comment_content,
            file_path: context.root_comment.file.file_path,
            position: context.root_comment.file.position,
            unidiff_offset: context.root_comment.file.unidiff_offset,
            format: FORMAT_COMMONMARK,
        },
    ).map((new_inline_comment) => ({
        type: TYPE_INLINE_COMMENT,
        is_outdated: false,
        ...new_inline_comment,
    }));
};

export const NewReplySaver = (): SaveComment => ({
    saveComment: (
        new_comment: NewCommentFormPresenter,
        context: ReplyCreationContext,
    ): ResultAsync<PullRequestComment, Fault> =>
        context.type === TYPE_INLINE_COMMENT
            ? saveReplyToInlineComment(context, new_comment)
            : saveReplyToComment(context, new_comment),
});
