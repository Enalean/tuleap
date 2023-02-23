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

import { postJSON, uri } from "@tuleap/fetch-result";
import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import type { FileDiffCommentPayload } from "../PullRequestCommentPresenterBuilder";
import type { InlineCommentContext } from "./NewInlineCommentContext";

export interface SaveNewInlineComment {
    postComment: (content: string) => ResultAsync<FileDiffCommentPayload, Fault>;
}

export const NewInlineCommentSaver = (
    inline_comment_context: InlineCommentContext
): SaveNewInlineComment => ({
    postComment: (content: string): ResultAsync<FileDiffCommentPayload, Fault> =>
        postJSON<FileDiffCommentPayload>(
            uri`/api/v1/pull_requests/${inline_comment_context.pull_request_id}/inline-comments`,
            {
                file_path: inline_comment_context.file_path,
                unidiff_offset: inline_comment_context.unidiff_offset,
                position: inline_comment_context.position,
                content: content,
            }
        ),
});
