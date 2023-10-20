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

import { describe, expect, it, vi, beforeEach } from "vitest";
import { okAsync } from "neverthrow";
import * as tuleap_api from "@tuleap/fetch-result";
import { uri } from "@tuleap/fetch-result";
import type { NewCommentOnFile, NewGlobalComment } from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    TYPE_GLOBAL_COMMENT,
    TYPE_INLINE_COMMENT,
    FORMAT_COMMONMARK,
} from "@tuleap/plugin-pullrequest-constants";
import { PullRequestCommentPresenterStub } from "../../tests/stubs/PullRequestCommentPresenterStub";
import { CurrentPullRequestUserPresenterStub } from "../../tests/stubs/CurrentPullRequestUserPresenterStub";
import { NewCommentFormComponentConfigStub } from "../../tests/stubs/NewCommentFormComponentConfigStub";
import { CurrentPullRequestPresenterStub } from "../../tests/stubs/CurrentPullRequestPresenterStub";
import { ReplyContext } from "../comment/ReplyContext";
import type { PullRequestPresenter } from "../comment/PullRequestPresenter";
import type { CurrentPullRequestUserPresenter } from "../types";
import { NewCommentFormPresenter } from "./NewCommentFormPresenter";
import { NewReplySaver } from "./NewReplySaver";

vi.mock("@tuleap/fetch-result");

const current_user_id = 104;
const comment_content = "This is fine";

describe("NewReplySaver", () => {
    let presenter: NewCommentFormPresenter,
        current_user: CurrentPullRequestUserPresenter,
        current_pull_request: PullRequestPresenter;

    beforeEach(() => {
        current_user = CurrentPullRequestUserPresenterStub.withUserId(current_user_id);
        current_pull_request = CurrentPullRequestPresenterStub.withDefault();

        presenter = NewCommentFormPresenter.updateContent(
            NewCommentFormPresenter.buildFromAuthor(
                current_user,
                NewCommentFormComponentConfigStub.withCancelActionAllowed(),
            ),
            comment_content,
        );
    });

    it("Given a NewCommentFormPresenter and a global comment, then it should save it as a regular comment", async () => {
        const root_comment = PullRequestCommentPresenterStub.buildGlobalComment();
        const new_comment: NewGlobalComment = {
            id: 50,
            type: TYPE_GLOBAL_COMMENT,
            post_date: "2023-03-07T18:30:00Z",
            content: comment_content,
            raw_content: "This is fine",
            user: root_comment.user,
            parent_id: root_comment.id,
            color: "red-wine",
            post_processed_content: "",
            format: FORMAT_COMMONMARK,
        };

        const postSpy = vi.spyOn(tuleap_api, "postJSON").mockReturnValue(okAsync(new_comment));

        const result = await NewReplySaver().saveComment(
            presenter,
            ReplyContext.fromComment(root_comment, current_user, current_pull_request),
        );

        if (!result.isOk()) {
            throw new Error("Expected an OK");
        }

        expect(postSpy).toHaveBeenCalledWith(uri`/api/v1/pull_requests/144/comments`, {
            content: comment_content,
            parent_id: root_comment.id,
            user_id: current_user_id,
            format: FORMAT_COMMONMARK,
        });

        expect(result.value).toStrictEqual(new_comment);
    });

    it("Given a NewCommentFormPresenter and an inline-comment, then it should save it as an inline-comment", async () => {
        const root_comment = PullRequestCommentPresenterStub.buildInlineComment();
        const new_inline_comment: NewCommentOnFile = {
            id: 50,
            post_date: "2023-03-07T18:30:00Z",
            content: comment_content,
            raw_content: "This is fine",
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

        const result = await NewReplySaver().saveComment(
            presenter,
            ReplyContext.fromComment(root_comment, current_user, current_pull_request),
        );

        if (!result.isOk()) {
            throw new Error("Expected an OK");
        }

        expect(postSpy).toHaveBeenCalledWith(uri`/api/v1/pull_requests/144/inline-comments`, {
            content: comment_content,
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
