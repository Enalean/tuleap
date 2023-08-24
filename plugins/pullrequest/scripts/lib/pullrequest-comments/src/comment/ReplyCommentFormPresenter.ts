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

import type { PullRequestPresenter } from "./PullRequestPresenter";
import type { CurrentPullRequestUserPresenter } from "../types";

export interface ReplyCommentFormPresenter {
    readonly pull_request_id: number;
    readonly comment_content: string;
    readonly comment_author: CurrentPullRequestUserPresenter;
    readonly is_being_submitted: boolean;
    readonly is_submittable: boolean;
}

export const ReplyCommentFormPresenter = {
    buildEmpty: (
        comment_author: CurrentPullRequestUserPresenter,
        pull_request: PullRequestPresenter
    ): ReplyCommentFormPresenter => ({
        pull_request_id: pull_request.pull_request_id,
        comment_author,
        comment_content: "",
        is_being_submitted: false,
        is_submittable: false,
    }),
    updateContent: (
        presenter: ReplyCommentFormPresenter,
        content: string
    ): ReplyCommentFormPresenter => ({
        ...presenter,
        comment_content: content,
        is_submittable: content.length > 0,
    }),
    buildSubmitted: (presenter: ReplyCommentFormPresenter): ReplyCommentFormPresenter => ({
        ...presenter,
        is_being_submitted: true,
    }),
    buildNotSubmitted: (presenter: ReplyCommentFormPresenter): ReplyCommentFormPresenter => ({
        ...presenter,
        is_being_submitted: false,
    }),
};
