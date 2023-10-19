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

import { postJSON, uri } from "@tuleap/fetch-result";
import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import type {
    NewCommentOnFile,
    NewGlobalComment,
    PullRequestComment,
} from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    TYPE_GLOBAL_COMMENT,
    TYPE_INLINE_COMMENT,
    FORMAT_COMMONMARK,
} from "@tuleap/plugin-pullrequest-constants";
import type { NewCommentFormPresenter } from "./NewCommentFormPresenter";
import type { CommentCreationContext, SaveComment } from "./types";

export const NewCommentSaver = (): SaveComment => ({
    saveComment: (
        new_comment: NewCommentFormPresenter,
        comment_creation_context: CommentCreationContext,
    ): ResultAsync<PullRequestComment, Fault> => {
        if (comment_creation_context.type === TYPE_GLOBAL_COMMENT) {
            return postJSON<NewGlobalComment>(
                uri`/api/v1/pull_requests/${comment_creation_context.pull_request_id}/comments`,
                {
                    user_id: comment_creation_context.user_id,
                    content: new_comment.comment_content,
                    format: FORMAT_COMMONMARK,
                },
            ).map((comment) => ({
                ...comment,
            }));
        }

        const { comment_context } = comment_creation_context;

        return postJSON<NewCommentOnFile>(
            uri`/api/v1/pull_requests/${comment_creation_context.pull_request_id}/inline-comments`,
            {
                file_path: comment_context.file_path,
                unidiff_offset: comment_context.unidiff_offset,
                position: comment_context.position,
                content: new_comment.comment_content,
                user_id: comment_creation_context.user_id,
                format: FORMAT_COMMONMARK,
            },
        ).map((comment) => {
            return {
                type: TYPE_INLINE_COMMENT,
                is_outdated: false,
                ...comment,
            };
        });
    },
});
