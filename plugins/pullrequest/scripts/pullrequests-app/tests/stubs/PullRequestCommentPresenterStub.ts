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

import type {
    PullRequestCommentPresenter,
    PullRequestInlineCommentPresenter,
    PullRequestGlobalCommentPresenter,
} from "../../src/app/comments/PullRequestCommentPresenter";
import {
    TYPE_EVENT_COMMENT,
    TYPE_GLOBAL_COMMENT,
    TYPE_INLINE_COMMENT,
    INLINE_COMMENT_POSITION_RIGHT,
} from "../../src/app/comments/PullRequestCommentPresenter";

const comment_presenter_base: PullRequestGlobalCommentPresenter = {
    id: 12,
    user: {
        avatar_url: "https://example.com/John/Doe/avatar.png",
        display_name: "John Doe",
        user_url: "https://example.com/John/Doe/profile.html",
    },
    post_date: "a moment ago",
    content: "Please rebase",
    is_inline_comment: false,
    is_outdated: false,
    parent_id: 0,
    type: TYPE_GLOBAL_COMMENT,
    is_file_diff_comment: false,
};

const file = {
    file_path: "README.md",
    file_url: "url/to/readme.md",
    unidiff_offset: 8,
    position: INLINE_COMMENT_POSITION_RIGHT,
};

export const PullRequestCommentPresenterStub = {
    buildInlineCommentOutdated: (): PullRequestCommentPresenter => ({
        ...comment_presenter_base,
        file: { ...file },
        is_outdated: true,
        type: TYPE_INLINE_COMMENT,
        is_inline_comment: true,
    }),

    buildInlineComment: (): PullRequestCommentPresenter => ({
        ...comment_presenter_base,
        file: { ...file },
        type: TYPE_INLINE_COMMENT,
        is_inline_comment: true,
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

    buildWithData: (
        data: Partial<PullRequestGlobalCommentPresenter>
    ): PullRequestCommentPresenter => ({
        ...comment_presenter_base,
        ...data,
    }),

    buildFileDiffCommentPresenter: (): PullRequestInlineCommentPresenter => ({
        ...comment_presenter_base,
        type: TYPE_INLINE_COMMENT,
        is_inline_comment: true,
        unidiff_offset: 8,
        file_path: "README.md",
        position: INLINE_COMMENT_POSITION_RIGHT,
        is_file_diff_comment: true,
    }),
};
