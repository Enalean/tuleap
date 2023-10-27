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

import { describe, it, expect, beforeEach } from "vitest";
import { EditionFormPresenter } from "./EditionFormPresenter";
import { PullRequestCommentPresenterStub } from "../../../tests/stubs/PullRequestCommentPresenterStub";
import type { PullRequestCommentPresenter } from "../PullRequestCommentPresenter";

describe("EditionFormPresenter", () => {
    let comment: PullRequestCommentPresenter, previous_presenter: EditionFormPresenter;

    beforeEach(() => {
        comment = PullRequestCommentPresenterStub.buildGlobalCommentWithData({
            id: 110,
            raw_content: "Please rebase",
        });
        previous_presenter = EditionFormPresenter.fromComment(comment);
    });

    it("fromComment() should build an initial presenter from the given comment", () => {
        expect(EditionFormPresenter.fromComment(comment)).toStrictEqual({
            comment_id: comment.id,
            comment_type: comment.type,
            edited_content: comment.raw_content,
            is_submittable: true,
            is_being_submitted: false,
        });
    });

    it("should build a new presenter from the provided one with the provided content", () => {
        const new_content = "Please rebase onto the master branch";

        expect(EditionFormPresenter.buildUpdated(previous_presenter, new_content)).toStrictEqual({
            comment_id: comment.id,
            comment_type: comment.type,
            edited_content: new_content,
            is_submittable: true,
            is_being_submitted: false,
        });
    });

    it("When the comment content is cleared, then is_submittable should be false", () => {
        const new_content = "";

        expect(EditionFormPresenter.buildUpdated(previous_presenter, new_content)).toStrictEqual({
            comment_id: comment.id,
            comment_type: comment.type,
            edited_content: new_content,
            is_submittable: false,
            is_being_submitted: false,
        });
    });

    it("buildSubmitted() should build a new presenter from the provided one with is_being_submitted = true", () => {
        const presenter = EditionFormPresenter.buildSubmitted(previous_presenter);

        expect(presenter).toStrictEqual({
            comment_id: comment.id,
            comment_type: comment.type,
            edited_content: comment.raw_content,
            is_submittable: true,
            is_being_submitted: true,
        });
    });

    it("buildNotSubmitted() should build a new presenter from the provided one with is_being_submitted = false", () => {
        const presenter = EditionFormPresenter.buildNotSubmitted(
            EditionFormPresenter.buildSubmitted(previous_presenter),
        );

        expect(presenter).toStrictEqual({
            comment_id: comment.id,
            comment_type: comment.type,
            edited_content: comment.raw_content,
            is_submittable: true,
            is_being_submitted: false,
        });
    });
});
