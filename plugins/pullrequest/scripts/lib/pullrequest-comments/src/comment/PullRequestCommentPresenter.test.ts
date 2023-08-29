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

import { describe, expect, it } from "vitest";
import type { User, CommentOnFile, GlobalComment } from "@tuleap/plugin-pullrequest-rest-api-types";
import { PullRequestCommentPresenter } from "./PullRequestCommentPresenter";
import { PullRequestCommentPresenterStub } from "../../tests/stubs/PullRequestCommentPresenterStub";
import {
    TYPE_GLOBAL_COMMENT,
    TYPE_INLINE_COMMENT,
    FORMAT_COMMONMARK,
} from "@tuleap/plugin-pullrequest-constants";

describe("PullRequestCommentPresenterBuilder", () => {
    it("should build a CommentReplyPresenter from a GlobalComment", () => {
        const parent_comment = PullRequestCommentPresenterStub.buildGlobalComment();
        const new_comment_payload: GlobalComment = {
            id: 13,
            type: TYPE_GLOBAL_COMMENT,
            post_date: "2020/07/13 16:16",
            content: "",
            post_processed_content: "",
            format: FORMAT_COMMONMARK,
            user: {} as User,
            parent_id: 12,
            color: "",
        };

        const presenter = PullRequestCommentPresenter.fromCommentReply(
            parent_comment,
            new_comment_payload
        );

        expect(presenter).toStrictEqual({
            ...new_comment_payload,
        });
    });

    it("should build a CommentReplyPresenter from a CommentOnFile", () => {
        const parent_comment = PullRequestCommentPresenterStub.buildInlineComment();
        const base_comment = {
            id: 13,
            type: TYPE_INLINE_COMMENT,
            post_date: "2020/07/13 16:16",
            content: "",
            post_processed_content: "",
            format: FORMAT_COMMONMARK,
            user: {} as User,
            parent_id: 12,
            color: "",
            is_outdated: false,
        };
        const new_comment_on_file_payload: CommentOnFile = {
            ...base_comment,
            file_path: parent_comment.file.file_path,
            position: parent_comment.file.position,
            unidiff_offset: parent_comment.file.unidiff_offset,
        };

        const presenter = PullRequestCommentPresenter.fromCommentReply(
            parent_comment,
            new_comment_on_file_payload
        );

        expect(presenter).toStrictEqual({
            ...base_comment,
            file: parent_comment.file,
        });
    });
});
