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

import type { HostElement, PullRequestComment } from "./PullRequestComment";
import { PullRequestCommentController } from "./PullRequestCommentController";
import { setCatalog } from "../gettext-catalog";
import { FocusTextReplyToCommentAreaStub } from "../../../tests/stubs/FocusTextReplyToCommentAreaStub";
import type { FocusReplyToCommentTextArea } from "./PullRequestCommentReplyFormFocusHelper";
import type { StorePullRequestCommentReplies } from "./PullRequestCommentRepliesStore";
import type { CommentReplyPayload } from "./PullRequestCommentPresenter";
import { SaveNewCommentStub } from "../../../tests/stubs/SaveNewCommentStub";
import { CurrentPullRequestUserPresenterStub } from "../../../tests/stubs/CurrentPullRequestUserPresenterStub";
import { PullRequestCommentPresenterStub } from "../../../tests/stubs/PullRequestCommentPresenterStub";
import { ReplyCommentFormPresenterStub } from "../../../tests/stubs/ReplyCommentFormPresenterStub";
import { PullRequestCommentRepliesCollectionPresenter } from "./PullRequestCommentRepliesCollectionPresenter";
import { PullRequestCommentPresenter, TYPE_INLINE_COMMENT } from "./PullRequestCommentPresenter";
import { PullRequestCommentRepliesStore } from "./PullRequestCommentRepliesStore";

describe("PullRequestCommentController", () => {
    let focus_helper: FocusReplyToCommentTextArea,
        replies_store: StorePullRequestCommentReplies,
        new_comment_saver: SaveNewCommentStub,
        new_comment_reply_payload: CommentReplyPayload;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid, getPlural: (nb, msgid) => msgid });

        new_comment_reply_payload = {
            id: 13,
            content: "Please don't",
            color: "",
        } as CommentReplyPayload;

        focus_helper = FocusTextReplyToCommentAreaStub();
        replies_store = PullRequestCommentRepliesStore([]);
        new_comment_saver = SaveNewCommentStub.withResponsePayload(new_comment_reply_payload);
    });

    it("should show the reply to comment form and sets the focus on the textarea", () => {
        const host = {
            currentPullRequest: CurrentPullRequestUserPresenterStub.withDefault(),
            comment: PullRequestCommentPresenterStub.buildGlobalComment(),
        } as unknown as HostElement;

        PullRequestCommentController(focus_helper, replies_store, new_comment_saver).showReplyForm(
            host
        );

        expect(host.reply_comment_presenter).not.toBeNull();
        expect(focus_helper.focusFormReplyToCommentTextArea).toHaveBeenCalledTimes(1);
    });

    it("should hide the reply to comment form", () => {
        new_comment_saver = SaveNewCommentStub.withResponsePayload(new_comment_reply_payload);
        const host = {
            comment: PullRequestCommentPresenterStub.buildGlobalComment(),
        } as unknown as PullRequestComment;

        PullRequestCommentController(focus_helper, replies_store, new_comment_saver).hideReplyForm(
            host
        );

        expect(host.reply_comment_presenter).toBeNull();
    });

    it("Should update the host reply_comment_presenter content", () => {
        const host = {
            currentPullRequest: CurrentPullRequestUserPresenterStub.withDefault(),
            comment: PullRequestCommentPresenterStub.buildGlobalComment(),
            reply_comment_presenter: ReplyCommentFormPresenterStub.buildEmpty(),
        } as unknown as HostElement;

        PullRequestCommentController(
            focus_helper,
            replies_store,
            new_comment_saver
        ).updateCurrentReply(host, "Please rebase");

        expect(host.reply_comment_presenter?.comment_content).toBe("Please rebase");
    });

    it("Should save the new comment, hide the form and add the new comment reply in the collection of replies", async () => {
        const comment = PullRequestCommentPresenterStub.buildGlobalComment();
        const host = {
            currentPullRequest: CurrentPullRequestUserPresenterStub.withDefault(),
            comment,
            reply_comment_presenter: ReplyCommentFormPresenterStub.buildWithContent("Please don't"),
            replies: PullRequestCommentRepliesCollectionPresenter.fromReplies([]),
        } as unknown as HostElement;

        await PullRequestCommentController(
            focus_helper,
            replies_store,
            new_comment_saver
        ).saveReply(host);

        expect(new_comment_saver.getNbCalls()).toBe(1);
        expect(new_comment_saver.getLastCallParams()).toStrictEqual(
            ReplyCommentFormPresenterStub.buildBeingSubmitted("Please don't")
        );
        expect(host.reply_comment_presenter).toBeNull();
        expect(host.replies).toStrictEqual([
            PullRequestCommentPresenter.fromCommentReply(comment, new_comment_reply_payload),
        ]);
    });

    it(`Given a root comment with no answer yet
        When a reply has been added to this comment
        Then the root comment is assigned the color provided in the response payload`, async () => {
        const new_comment_reply_payload = {
            id: 13,
            content: "Please don't",
            color: "flamingo-pink",
        } as CommentReplyPayload;
        new_comment_saver = SaveNewCommentStub.withResponsePayload(new_comment_reply_payload);

        const comment = PullRequestCommentPresenterStub.buildGlobalComment();
        const host = {
            currentPullRequest: CurrentPullRequestUserPresenterStub.withDefault(),
            comment,
            reply_comment_presenter: ReplyCommentFormPresenterStub.buildWithContent("Please don't"),
            replies: PullRequestCommentRepliesCollectionPresenter.fromReplies([]),
        } as unknown as HostElement;

        await PullRequestCommentController(
            focus_helper,
            replies_store,
            new_comment_saver
        ).saveReply(host);

        expect(host.reply_comment_presenter).toBeNull();
        expect(host.comment.color).toBe("flamingo-pink");
    });

    it(`should display the replies associated to the comment`, () => {
        const host = {
            replies: [],
            comment: PullRequestCommentPresenterStub.buildWithData({
                id: 12,
                type: TYPE_INLINE_COMMENT,
            }),
        } as unknown as PullRequestComment;

        PullRequestCommentController(
            focus_helper,
            PullRequestCommentRepliesStore([
                PullRequestCommentPresenterStub.buildWithData({
                    parent_id: 12,
                    type: TYPE_INLINE_COMMENT,
                }),
                PullRequestCommentPresenterStub.buildWithData({
                    parent_id: 12,
                    type: TYPE_INLINE_COMMENT,
                }),
                PullRequestCommentPresenterStub.buildWithData({
                    parent_id: 12,
                    type: TYPE_INLINE_COMMENT,
                }),
            ]),
            new_comment_saver
        ).displayReplies(host);

        expect(host.replies).toHaveLength(3);
    });
});
