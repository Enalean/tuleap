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

import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { patchJSON, uri } from "@tuleap/fetch-result";
import type {
    CommentOnFile,
    GlobalComment,
    PullRequestComment,
} from "@tuleap/plugin-pullrequest-rest-api-types";
import { TYPE_GLOBAL_COMMENT } from "@tuleap/plugin-pullrequest-constants";
import type { EditionFormPresenter } from "./EditionFormPresenter";

export type SaveEditedComment = {
    saveEditedComment(form_presenter: EditionFormPresenter): ResultAsync<PullRequestComment, Fault>;
};

export const EditedCommentSaver = (): SaveEditedComment => ({
    saveEditedComment: (
        presenter: EditionFormPresenter,
    ): ResultAsync<PullRequestComment, Fault> => {
        if (presenter.comment_type === TYPE_GLOBAL_COMMENT) {
            return patchJSON<GlobalComment>(
                uri`/api/v1/pull_request_comments/${presenter.comment_id}`,
                {
                    content: presenter.edited_content,
                },
            );
        }

        return patchJSON<CommentOnFile>(
            uri`/api/v1/pull_request_inline_comments/${presenter.comment_id}`,
            {
                content: presenter.edited_content,
            },
        );
    },
});
