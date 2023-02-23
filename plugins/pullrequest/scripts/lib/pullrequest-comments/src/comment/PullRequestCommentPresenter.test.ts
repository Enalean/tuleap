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
import { PullRequestCommentPresenter } from "./PullRequestCommentPresenter";
import type { CommentReplyPayload } from "./PullRequestCommentPresenter";
import type { PullRequestUser } from "../types";
import { PullRequestCommentPresenterStub } from "../../tests/stubs/PullRequestCommentPresenterStub";

describe("PullRequestCommentPresenterBuilder", () => {
    it("should build a CommentReplyPresenter from a new comment payload", () => {
        const parent_comment = PullRequestCommentPresenterStub.buildGlobalComment();
        const new_comment_payload: CommentReplyPayload = {
            id: 13,
            post_date: "2020/07/13 16:16",
            content: "",
            user: {} as PullRequestUser,
            parent_id: 12,
            color: "",
        };

        const presenter = PullRequestCommentPresenter.fromCommentReply(
            parent_comment,
            new_comment_payload
        );

        expect(presenter.type).toBe("comment");
        expect(presenter.is_outdated).toBe(false);
        expect(presenter.is_inline_comment).toBe(false);
        expect(presenter.parent_id).toBe(12);
    });
});
