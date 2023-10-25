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

import { describe, beforeEach, expect, it } from "vitest";
import { PullRequestCommentPresenterStub } from "../../tests/stubs/PullRequestCommentPresenterStub";
import { PullRequestCommentRepliesStore } from "./PullRequestCommentRepliesStore";
import type { PullRequestCommentPresenter } from "./PullRequestCommentPresenter";

describe("PullRequestCommentRepliesStore", () => {
    let comment_1: PullRequestCommentPresenter,
        comment_2: PullRequestCommentPresenter,
        comment_3: PullRequestCommentPresenter,
        comments: PullRequestCommentPresenter[];

    beforeEach(() => {
        comment_1 = PullRequestCommentPresenterStub.buildInlineCommentWithData({
            id: 8,
        });
        comment_2 = PullRequestCommentPresenterStub.buildGlobalCommentWithData({
            id: 9,
        });
        comment_3 = PullRequestCommentPresenterStub.buildInlineCommentWithData({
            id: 10,
        });
        comments = [
            comment_1,
            comment_2,
            comment_3,
            PullRequestCommentPresenterStub.buildInlineCommentWithData({
                id: 1,
                parent_id: comment_1.id,
                post_date: "2022-11-03T14:00:57+01:00",
            }),
            PullRequestCommentPresenterStub.buildInlineCommentWithData({
                id: 2,
                parent_id: comment_1.id,
                post_date: "2022-11-03T14:50:57+01:00",
            }),
            PullRequestCommentPresenterStub.buildInlineCommentWithData({
                id: 3,
                parent_id: comment_1.id,
                post_date: "2022-11-03T14:30:57+01:00",
            }),
            PullRequestCommentPresenterStub.buildGlobalCommentWithData({
                id: 1,
                parent_id: comment_2.id,
                post_date: "2022-11-03T14:30:57+01:00",
            }),
            PullRequestCommentPresenterStub.buildGlobalCommentWithData({
                id: 2,
                parent_id: comment_2.id,
                post_date: "2022-11-03T15:30:57+01:00",
            }),
            PullRequestCommentPresenterStub.buildGlobalCommentWithData({
                id: 3,
                parent_id: comment_2.id,
                post_date: "2022-11-03T14:15:57+01:00",
            }),
        ];
    });

    it("Given a comment, then it should return the replies associated to this comment sorted by post_date if there are some", () => {
        const store = PullRequestCommentRepliesStore(comments);

        const comment_1_replies = store.getCommentReplies(comment_1);
        const comment_2_replies = store.getCommentReplies(comment_2);
        const comment_3_replies = store.getCommentReplies(comment_3);

        expect(comment_1_replies.map(({ id }) => id)).toStrictEqual([1, 3, 2]);
        expect(comment_2_replies.map(({ id }) => id)).toStrictEqual([3, 1, 2]);
        expect(comment_3_replies.map(({ id }) => id)).toStrictEqual([]);
    });

    it("should add a root comment", () => {
        const new_comment = PullRequestCommentPresenterStub.buildInlineComment();
        const store = PullRequestCommentRepliesStore([]);

        store.addRootComment(new_comment);

        expect(store.getCommentReplies(new_comment)).toStrictEqual([]);
        expect(store.getAllRootComments()).toContain(new_comment);
    });

    it("should add a reply to a global comment", () => {
        const store = PullRequestCommentRepliesStore(comments);
        const new_comment = PullRequestCommentPresenterStub.buildGlobalCommentWithData({
            id: 1,
            parent_id: comment_2.id,
            post_date: "2022-11-03T14:00:57+01:00",
        });

        store.addReplyToComment(comment_2, new_comment);

        expect(store.getCommentReplies(comment_2)).toContain(new_comment);
    });

    it("should add a reply to an inline-comment", () => {
        const store = PullRequestCommentRepliesStore(comments);
        const new_comment = PullRequestCommentPresenterStub.buildInlineCommentWithData({
            id: 1,
            parent_id: comment_3.id,
            post_date: "2022-11-03T14:00:57+01:00",
        });

        store.addReplyToComment(comment_3, new_comment);

        expect(store.getCommentReplies(comment_3)).toContain(new_comment);
    });
});
