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

import {
    TYPE_INLINE_COMMENT,
    INLINE_COMMENT_POSITION_RIGHT,
    FORMAT_COMMONMARK,
} from "@tuleap/plugin-pullrequest-constants";
import type { CommentOnFile, User } from "@tuleap/plugin-pullrequest-rest-api-types";
import { PullRequestCommentPresenterBuilder } from "./PullRequestCommentPresenterBuilder";
import { setCatalog } from "../gettext-catalog";

const user: User = {
    id: 102,
    user_url: "url/to/user_profile.html",
    avatar_url: "url/to/user_avatar.png",
    display_name: "Joe l'Asticot",
};

describe("PullRequestCommentPresenterBuilder", () => {
    beforeEach(() => {
        setCatalog({ getPlural: (nb, msgid) => msgid });
    });

    it("Builds a presenter from a file-diff comment payload", () => {
        const file_diff_comment: CommentOnFile = {
            id: 12,
            post_date: "2020/07/13 16:16",
            content: "my comment",
            post_processed_content: "<p>my comment</p>",
            format: FORMAT_COMMONMARK,
            user,
            file_path: "README.md",
            unidiff_offset: 8,
            position: INLINE_COMMENT_POSITION_RIGHT,
            parent_id: 0,
            color: "graffiti-yellow",
            type: TYPE_INLINE_COMMENT,
            is_outdated: false,
        };
        const result = PullRequestCommentPresenterBuilder.fromFileDiffComment(file_diff_comment);
        expect(result.type).toBe(TYPE_INLINE_COMMENT);
        expect(result.is_outdated).toBe(false);
        expect(result.file).toStrictEqual({
            file_url: "",
            position: file_diff_comment.position,
            file_path: file_diff_comment.file_path,
            unidiff_offset: file_diff_comment.unidiff_offset,
            is_displayed: false,
        });
    });
});
