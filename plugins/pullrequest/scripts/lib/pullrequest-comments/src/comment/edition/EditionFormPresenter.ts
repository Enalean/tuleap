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

import type { CommentType } from "@tuleap/plugin-pullrequest-constants";
import type { PullRequestCommentPresenter } from "../PullRequestCommentPresenter";

export type EditionFormPresenter = {
    readonly comment_id: number;
    readonly comment_type: CommentType;
    readonly edited_content: string;
    readonly is_submittable: boolean;
    readonly is_being_submitted: boolean;
};

export const EditionFormPresenter = {
    fromComment: (comment: PullRequestCommentPresenter): EditionFormPresenter => ({
        comment_id: comment.id,
        comment_type: comment.type,
        edited_content: comment.raw_content,
        is_submittable: true,
        is_being_submitted: false,
    }),
    buildUpdated: (presenter: EditionFormPresenter, new_content: string): EditionFormPresenter => ({
        ...presenter,
        edited_content: new_content,
        is_submittable: new_content.length > 0,
    }),
    buildSubmitted: (presenter: EditionFormPresenter): EditionFormPresenter => ({
        ...presenter,
        is_being_submitted: true,
    }),
    buildNotSubmitted: (presenter: EditionFormPresenter): EditionFormPresenter => ({
        ...presenter,
        is_being_submitted: false,
    }),
};
