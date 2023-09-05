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

import type { User, PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { CommentTextFormat } from "@tuleap/plugin-pullrequest-constants";

export interface PullRequestDescriptionCommentPresenter {
    readonly pull_request_id: number;
    readonly project_id: number;
    readonly author: User;
    readonly post_date: string;
    readonly content: string;
    readonly post_processed_content: string;
    readonly format: CommentTextFormat;
    readonly raw_content: string;
    readonly can_user_update_description: boolean;
}

export const PullRequestDescriptionCommentPresenter = {
    fromPullRequestWithUpdatedDescription: (
        presenter: PullRequestDescriptionCommentPresenter,
        pull_request: PullRequest,
    ): PullRequestDescriptionCommentPresenter => ({
        ...presenter,
        content: pull_request.description,
        raw_content: pull_request.raw_description,
        post_processed_content: pull_request.post_processed_description,
        format: pull_request.description_format,
    }),
};
