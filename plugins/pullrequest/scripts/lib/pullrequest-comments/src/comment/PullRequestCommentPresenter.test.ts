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
import type {
    User,
    CommentOnFile,
    GlobalComment,
    EditedComment,
} from "@tuleap/plugin-pullrequest-rest-api-types";
import { PullRequestCommentPresenter } from "./PullRequestCommentPresenter";
import type { PullRequestInlineCommentPresenter } from "./PullRequestCommentPresenter";
import { PullRequestCommentPresenterStub } from "../../tests/stubs/PullRequestCommentPresenterStub";
import {
    TYPE_GLOBAL_COMMENT,
    TYPE_INLINE_COMMENT,
    FORMAT_COMMONMARK,
} from "@tuleap/plugin-pullrequest-constants";

const isPullRequestInlineCommentPresenter = (
    presenter: PullRequestCommentPresenter,
): presenter is PullRequestInlineCommentPresenter => presenter.type === TYPE_INLINE_COMMENT;

describe("PullRequestCommentPresenterBuilder", () => {
    it("should build a CommentReplyPresenter from a GlobalComment", () => {
        const parent_comment = PullRequestCommentPresenterStub.buildGlobalComment();
        const new_comment_payload: GlobalComment = {
            id: 13,
            type: TYPE_GLOBAL_COMMENT,
            post_date: "2020-07-13T16:16:00Z",
            last_edition_date: "2020-07-13T17:00:00Z",
            content: "",
            raw_content: "",
            post_processed_content: "",
            format: FORMAT_COMMONMARK,
            user: {} as User,
            parent_id: 12,
            color: "",
        };

        const presenter = PullRequestCommentPresenter.fromCommentReply(
            parent_comment,
            new_comment_payload,
        );

        expect(presenter.id).toStrictEqual(new_comment_payload.id);
        expect(presenter.type).toStrictEqual(new_comment_payload.type);
        expect(presenter.post_date).toStrictEqual(new_comment_payload.post_date);
        expect(presenter.last_edition_date.unwrapOr(null)).toStrictEqual(
            new_comment_payload.last_edition_date,
        );
        expect(presenter.content).toStrictEqual(new_comment_payload.content);
        expect(presenter.raw_content).toStrictEqual(new_comment_payload.raw_content);
        expect(presenter.post_processed_content).toStrictEqual(
            new_comment_payload.post_processed_content,
        );
        expect(presenter.format).toStrictEqual(new_comment_payload.format);
        expect(presenter.user).toStrictEqual(new_comment_payload.user);
        expect(presenter.parent_id).toStrictEqual(new_comment_payload.parent_id);
        expect(presenter.color).toStrictEqual(new_comment_payload.color);
    });

    it("should build a CommentReplyPresenter from a CommentOnFile", () => {
        const parent_comment = PullRequestCommentPresenterStub.buildInlineComment();
        const base_comment = {
            id: 13,
            type: TYPE_INLINE_COMMENT,
            post_date: "2020-07-13T16:16:00Z",
            last_edition_date: "2020-07-13T17:00:00Z",
            content: "",
            raw_content: "",
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
            new_comment_on_file_payload,
        );

        if (!isPullRequestInlineCommentPresenter(presenter)) {
            throw new Error("Expected a PullRequestInlineCommentPresenter");
        }

        expect(presenter.id).toStrictEqual(base_comment.id);
        expect(presenter.type).toStrictEqual(base_comment.type);
        expect(presenter.post_date).toStrictEqual(base_comment.post_date);
        expect(presenter.last_edition_date.unwrapOr(null)).toStrictEqual(
            base_comment.last_edition_date,
        );
        expect(presenter.content).toStrictEqual(base_comment.content);
        expect(presenter.raw_content).toStrictEqual(base_comment.raw_content);
        expect(presenter.post_processed_content).toStrictEqual(base_comment.post_processed_content);
        expect(presenter.format).toStrictEqual(base_comment.format);
        expect(presenter.user).toStrictEqual(base_comment.user);
        expect(presenter.parent_id).toStrictEqual(base_comment.parent_id);
        expect(presenter.color).toStrictEqual(base_comment.color);
        expect(presenter.file).toStrictEqual(parent_comment.file);
    });

    describe("fromEditedComment()", () => {
        it("Given the original comment and the edit comment, Then it should return a new presenter containing the edited content", () => {
            const edited_comment_payload: EditedComment = {
                id: 13,
                type: TYPE_GLOBAL_COMMENT,
                post_date: "2020-07-13T16:16:00Z",
                last_edition_date: "2020-07-13T17:00:00Z",
                content: "Please rebase on top of the **master** branch",
                post_processed_content: `Please rebase on top of the <em>master</em> branch`,
                raw_content: "Please rebase on top of the **master** branch",
                format: FORMAT_COMMONMARK,
                user: {} as User,
                parent_id: 12,
                color: "",
            };
            const original_comment = PullRequestCommentPresenterStub.buildGlobalCommentWithData({
                content: "**Please rebase**",
                post_processed_content: `<em>Please rebase</em>`,
                raw_content: "**Please rebase**",
            });

            const new_presenter = PullRequestCommentPresenter.fromEditedComment(
                original_comment,
                edited_comment_payload,
            );

            expect(new_presenter.id).toStrictEqual(original_comment.id);
            expect(new_presenter.type).toStrictEqual(original_comment.type);
            expect(new_presenter.post_date).toStrictEqual(original_comment.post_date);
            expect(new_presenter.last_edition_date.unwrapOr(null)).toStrictEqual(
                edited_comment_payload.last_edition_date,
            );
            expect(new_presenter.content).toStrictEqual(edited_comment_payload.content);
            expect(new_presenter.raw_content).toStrictEqual(edited_comment_payload.raw_content);
            expect(new_presenter.post_processed_content).toStrictEqual(
                edited_comment_payload.post_processed_content,
            );
            expect(new_presenter.format).toStrictEqual(original_comment.format);
            expect(new_presenter.user).toStrictEqual(original_comment.user);
            expect(new_presenter.parent_id).toStrictEqual(original_comment.parent_id);
            expect(new_presenter.color).toStrictEqual(original_comment.color);
        });
    });
});
