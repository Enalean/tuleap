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

import { describe, expect, it, vi } from "vitest";
import { okAsync } from "neverthrow";
import * as tuleap_api from "@tuleap/fetch-result";
import { PullRequestCommentNewReplySaver } from "./PullRequestCommentReplySaver";
import type { ReplyCommentFormPresenter } from "./ReplyCommentFormPresenter";
import { PullRequestCommentPresenterStub } from "../../tests/stubs/PullRequestCommentPresenterStub";
import { uri } from "@tuleap/fetch-result";
import {
    TYPE_GLOBAL_COMMENT,
    TYPE_INLINE_COMMENT,
    FORMAT_COMMONMARK,
} from "@tuleap/plugin-pullrequest-constants";
import type { NewCommentOnFile, NewGlobalComment } from "@tuleap/plugin-pullrequest-rest-api-types";

vi.mock("@tuleap/fetch-result");

const current_user_id = 104;
const getFormPresenter = (): ReplyCommentFormPresenter =>
    ({
        pull_request_id: 144,
        comment_content: "Please rebase",
        comment_author: {
            user_id: current_user_id,
        },
    } as ReplyCommentFormPresenter);

const is_comments_markdown_mode_enabled = true;

describe("PullRequestCommentReplySaver", () => {
    it("Given a ReplyCommentFormPresenter and a global comment, then it should save it as a regular comment", async () => {
        const root_comment = PullRequestCommentPresenterStub.buildGlobalComment();
        const new_comment: NewGlobalComment = {
            id: 50,
            type: TYPE_GLOBAL_COMMENT,
            post_date: "2023-03-07T18:30:00Z",
            content: "This is fine",
            user: root_comment.user,
            parent_id: root_comment.id,
            color: "red-wine",
            post_processed_content: "",
            format: FORMAT_COMMONMARK,
        };

        const postSpy = vi.spyOn(tuleap_api, "postJSON").mockReturnValue(okAsync(new_comment));

        const result = await PullRequestCommentNewReplySaver().saveReply(
            root_comment,
            getFormPresenter(),
            is_comments_markdown_mode_enabled
        );

        if (!result.isOk()) {
            throw new Error("Expected an OK");
        }

        expect(postSpy).toHaveBeenCalledWith(uri`/api/v1/pull_requests/144/comments`, {
            content: "Please rebase",
            parent_id: root_comment.id,
            user_id: current_user_id,
            format: FORMAT_COMMONMARK,
        });

        expect(result.value).toStrictEqual({ ...new_comment });
    });

    it("Given a ReplyCommentFormPresenter and an inline-comment, then it should save it as an inline-comment", async () => {
        const root_comment = PullRequestCommentPresenterStub.buildInlineComment();
        const new_inline_comment: NewCommentOnFile = {
            id: 50,
            post_date: "2023-03-07T18:30:00Z",
            content: "This is fine",
            user: root_comment.user,
            parent_id: root_comment.id,
            color: "red-wine",
            file_path: root_comment.file.file_path,
            position: root_comment.file.position,
            unidiff_offset: root_comment.file.unidiff_offset,
            format: FORMAT_COMMONMARK,
            post_processed_content: "",
        };

        const postSpy = vi
            .spyOn(tuleap_api, "postJSON")
            .mockReturnValue(okAsync(new_inline_comment));

        const form_presenter = getFormPresenter();

        const result = await PullRequestCommentNewReplySaver().saveReply(
            root_comment,
            form_presenter,
            is_comments_markdown_mode_enabled
        );

        if (!result.isOk()) {
            throw new Error("Expected an OK");
        }

        expect(postSpy).toHaveBeenCalledWith(uri`/api/v1/pull_requests/144/inline-comments`, {
            content: "Please rebase",
            parent_id: root_comment.id,
            user_id: current_user_id,
            file_path: root_comment.file.file_path,
            position: root_comment.file.position,
            unidiff_offset: root_comment.file.unidiff_offset,
            format: FORMAT_COMMONMARK,
        });

        expect(result.value).toStrictEqual({
            type: TYPE_INLINE_COMMENT,
            is_outdated: false,
            ...new_inline_comment,
        });
    });
});
