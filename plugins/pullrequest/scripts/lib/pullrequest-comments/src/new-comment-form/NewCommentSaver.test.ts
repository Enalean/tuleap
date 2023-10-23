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

import { describe, it, expect, vi, beforeEach } from "vitest";
import { okAsync } from "neverthrow";
import * as tuleap_api from "@tuleap/fetch-result";
import {
    INLINE_COMMENT_POSITION_RIGHT,
    TYPE_GLOBAL_COMMENT,
    TYPE_INLINE_COMMENT,
    FORMAT_COMMONMARK,
} from "@tuleap/plugin-pullrequest-constants";
import { NewCommentSaver } from "./NewCommentSaver";
import { uri } from "@tuleap/fetch-result";
import { CurrentPullRequestUserPresenterStub } from "../../tests/stubs/CurrentPullRequestUserPresenterStub";
import { NewCommentFormComponentConfigStub } from "../../tests/stubs/NewCommentFormComponentConfigStub";
import type { CommentOnFileCreationContext, GlobalCommentCreationContext } from "./types";
import { NewCommentFormPresenter } from "./NewCommentFormPresenter";

vi.mock("@tuleap/fetch-result");

const user = {
    avatar_url: "url/to/user_profile.html",
    user_url: "url/to/user_avatar.png",
    display_name: "Joe l'Asticot",
};

describe("NewInlineCommentSaver", () => {
    let presenter: NewCommentFormPresenter;

    beforeEach(() => {
        presenter = NewCommentFormPresenter.updateContent(
            NewCommentFormPresenter.buildFromAuthor(
                CurrentPullRequestUserPresenterStub.withDefault(),
                NewCommentFormComponentConfigStub.withCancelActionAllowed(),
            ),
            "This is fine",
        );
    });

    it("should save the new inline comment and return a CommentOnFile", async () => {
        const postSpy = vi.spyOn(tuleap_api, "postJSON").mockReturnValue(
            okAsync({
                id: 50,
                post_date: "2023-03-07T18:30:00Z",
                content: "This is fine",
                user,
                parent_id: 0,
                color: "",
            }),
        );

        const comment_saver = NewCommentSaver();
        const context: CommentOnFileCreationContext = {
            type: TYPE_INLINE_COMMENT,
            pull_request_id: 1,
            user_id: 102,
            comment_context: {
                position: INLINE_COMMENT_POSITION_RIGHT,
                file_path: "README.md",
                unidiff_offset: 55,
            },
        };

        const result = await comment_saver.saveComment(presenter, context);
        if (!result.isOk()) {
            throw new Error("Expected an ok");
        }

        expect(postSpy).toHaveBeenCalledWith(uri`/api/v1/pull_requests/1/inline-comments`, {
            file_path: "README.md",
            unidiff_offset: 55,
            position: INLINE_COMMENT_POSITION_RIGHT,
            content: "This is fine",
            user_id: 102,
            format: FORMAT_COMMONMARK,
        });

        expect(result.value).toStrictEqual({
            id: 50,
            type: TYPE_INLINE_COMMENT,
            is_outdated: false,
            post_date: "2023-03-07T18:30:00Z",
            content: "This is fine",
            user,
            parent_id: 0,
            color: "",
        });
    });

    it("should save the new comment and return a GlobalComment", async () => {
        const postSpy = vi.spyOn(tuleap_api, "postJSON").mockReturnValue(
            okAsync({
                id: 50,
                type: TYPE_GLOBAL_COMMENT,
                post_date: "2023-03-07T18:30:00Z",
                content: "This is fine",
                user,
                parent_id: 0,
                color: "",
            }),
        );

        const comment_saver = NewCommentSaver();
        const context: GlobalCommentCreationContext = {
            type: TYPE_GLOBAL_COMMENT,
            user_id: 102,
            pull_request_id: 1,
        };

        const result = await comment_saver.saveComment(presenter, context);
        if (!result.isOk()) {
            throw new Error("Expected an ok");
        }

        expect(postSpy).toHaveBeenCalledWith(uri`/api/v1/pull_requests/1/comments`, {
            content: "This is fine",
            user_id: 102,
            format: FORMAT_COMMONMARK,
        });

        expect(result.value).toStrictEqual({
            id: 50,
            type: TYPE_GLOBAL_COMMENT,
            post_date: "2023-03-07T18:30:00Z",
            content: "This is fine",
            user,
            parent_id: 0,
            color: "",
        });
    });
});
