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

import { TYPE_INLINE_COMMENT, TYPE_GLOBAL_COMMENT } from "@tuleap/plugin-pullrequest-constants";
import type { ReplyCreationContext } from "../new-comment-form/types";
import type { CurrentPullRequestUserPresenter } from "../types";
import type { PullRequestCommentPresenter } from "./PullRequestCommentPresenter";
import type { PullRequestPresenter } from "./PullRequestPresenter";

export const ReplyContext = {
    fromComment(
        comment: PullRequestCommentPresenter,
        current_user: CurrentPullRequestUserPresenter,
        current_pull_request: PullRequestPresenter,
    ): ReplyCreationContext {
        if (comment.type === TYPE_INLINE_COMMENT) {
            return {
                user_id: current_user.user_id,
                type: TYPE_INLINE_COMMENT,
                root_comment: comment,
                pull_request_id: current_pull_request.pull_request_id,
            };
        }

        return {
            user_id: current_user.user_id,
            type: TYPE_GLOBAL_COMMENT,
            root_comment: comment,
            pull_request_id: current_pull_request.pull_request_id,
        };
    },
};
