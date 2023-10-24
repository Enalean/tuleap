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

import { TYPE_INLINE_COMMENT } from "@tuleap/plugin-pullrequest-constants";
import type { CommentOnFile } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { PullRequestInlineCommentPresenter } from "@tuleap/plugin-pullrequest-comments";

export const PullRequestCommentPresenterBuilder = {
    fromFileDiffComment: (comment: CommentOnFile): PullRequestInlineCommentPresenter => ({
        id: comment.id,
        user: comment.user,
        post_date: comment.post_date,
        content: replaceLineReturns(comment.content),
        raw_content: comment.raw_content,
        post_processed_content: comment.post_processed_content,
        format: comment.format,
        type: TYPE_INLINE_COMMENT,
        is_outdated: false,
        parent_id: comment.parent_id,
        color: comment.color,
        file: {
            file_url: "",
            file_path: comment.file_path,
            unidiff_offset: comment.unidiff_offset,
            position: comment.position,
            is_displayed: false,
        },
    }),
};

function replaceLineReturns(content: string): string {
    return content.replace(/(?:\r\n|\r|\n)/g, "<br/>");
}
