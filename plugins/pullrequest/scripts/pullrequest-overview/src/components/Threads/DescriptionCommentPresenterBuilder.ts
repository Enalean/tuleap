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

import type { PullRequest, User } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { PullRequestDescriptionCommentPresenter } from "@tuleap/plugin-pullrequest-comments";

export const DescriptionCommentPresenterBuilder = {
    fromPullRequestAndItsAuthor: (
        pull_request: PullRequest,
        author: User
    ): PullRequestDescriptionCommentPresenter => ({
        author: {
            id: author.id,
            avatar_url: author.avatar_url,
            user_url: author.user_url,
            display_name: author.display_name,
        },
        pull_request_id: pull_request.id,
        content: pull_request.description,
        raw_content: pull_request.raw_description,
        post_date: pull_request.creation_date,
        can_user_update_description:
            pull_request.user_can_merge || pull_request.user_can_update_title_and_description,
    }),
};
