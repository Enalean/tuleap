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

import type { PullRequestCommentPresenter } from "../../src/app/comments/PullRequestCommentPresenter";
import {
    TYPE_EVENT_COMMENT,
    TYPE_GLOBAL_COMMENT,
    TYPE_INLINE_COMMENT,
} from "../../src/app/comments/PullRequestCommentPresenter";

const comment_presenter_base = {
    id: 12,
    user: {
        avatar_url: "https://example.com/John/Doe/avatar.png",
        display_name: "John Doe",
        user_url: "https://example.com/John/Doe/profile.html",
    },
    post_date: "a moment ago",
    content: "Please rebase",
    is_inline_comment: true,
    is_outdated: false,
    parent_id: 0,
    type: TYPE_GLOBAL_COMMENT,
};

export const PullRequestCommentPresenterStub = {
    buildInlineCommentOutdated: (): PullRequestCommentPresenter => ({
        ...comment_presenter_base,
        file: {
            file_path: "README.md",
            file_url: "url/to/readme.md",
        },
        is_outdated: true,
        type: TYPE_INLINE_COMMENT,
    }),

    buildInlineComment: (): PullRequestCommentPresenter => ({
        ...comment_presenter_base,
        file: {
            file_path: "README.md",
            file_url: "url/to/readme.md",
        },
        type: TYPE_INLINE_COMMENT,
    }),

    buildGlobalComment: (): PullRequestCommentPresenter => ({
        ...comment_presenter_base,
        is_inline_comment: false,
        type: TYPE_GLOBAL_COMMENT,
    }),

    buildPullRequestEventComment: (): PullRequestCommentPresenter => ({
        ...comment_presenter_base,
        is_inline_comment: false,
        type: TYPE_EVENT_COMMENT,
    }),

    buildWithData: (data: Partial<PullRequestCommentPresenter>): PullRequestCommentPresenter => ({
        ...comment_presenter_base,
        ...data,
    }),
};
