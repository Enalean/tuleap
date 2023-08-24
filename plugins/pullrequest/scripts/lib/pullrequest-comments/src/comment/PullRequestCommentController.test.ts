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

import { describe, beforeEach, expect, it, vi } from "vitest";

import { Fault } from "@tuleap/fault";
import type { PullRequestComment } from "@tuleap/plugin-pullrequest-rest-api-types";
import { TYPE_GLOBAL_COMMENT } from "@tuleap/plugin-pullrequest-constants";

import type { HostElement, PullRequestCommentComponentType } from "./PullRequestComment";
import type { ControlPullRequestComment } from "./PullRequestCommentController";
import { PullRequestCommentController } from "./PullRequestCommentController";
import type { StorePullRequestCommentReplies } from "./PullRequestCommentRepliesStore";
import { SaveNewReplyToCommentStub } from "../../tests/stubs/SaveNewReplyToCommentStub";
import { CurrentPullRequestUserPresenterStub } from "../../tests/stubs/CurrentPullRequestUserPresenterStub";
import { PullRequestCommentPresenterStub } from "../../tests/stubs/PullRequestCommentPresenterStub";
import { ReplyCommentFormPresenterStub } from "../../tests/stubs/ReplyCommentFormPresenterStub";
import { PullRequestCommentRepliesCollectionPresenter } from "./PullRequestCommentRepliesCollectionPresenter";
import { PullRequestCommentPresenter } from "./PullRequestCommentPresenter";
import { PullRequestCommentRepliesStore } from "./PullRequestCommentRepliesStore";
import { CurrentPullRequestPresenterStub } from "../../tests/stubs/CurrentPullRequestPresenterStub";
import type { SaveNewReplyToComment } from "./PullRequestCommentReplySaver";
import type { PullRequestCommentErrorCallback } from "../types";
import { ReplyCommentFormPresenter } from "./ReplyCommentFormPresenter";

describe("PullRequestCommentController", () => {
    let replies_store: StorePullRequestCommentReplies,
        on_error_callback: PullRequestCommentErrorCallback;

    beforeEach(() => {
        replies_store = PullRequestCommentRepliesStore([]);
        on_error_callback = vi.fn();
    });

    const getController = (save_new_comment: SaveNewReplyToComment): ControlPullRequestComment =>
        PullRequestCommentController(
            replies_store,
            save_new_comment,
            CurrentPullRequestUserPresenterStub.withDefault(),
            CurrentPullRequestPresenterStub.withDefault(),
            on_error_callback
        );

    it("should show the reply to comment form and sets the focus on the textarea", () => {
        const content = document.implementation.createHTMLDocument().createElement("div");
        const host = {
            comment: PullRequestCommentPresenterStub.buildGlobalComment(),
            content: () => content,
        } as unknown as HostElement;

        getController(SaveNewReplyToCommentStub.withDefault()).showReplyForm(host);

        expect(host.reply_comment_presenter).not.toBeNull();
    });

    it("should hide the reply to comment form", () => {
        const host = {
            comment: PullRequestCommentPresenterStub.buildGlobalComment(),
        } as unknown as PullRequestCommentComponentType;

        getController(SaveNewReplyToCommentStub.withDefault()).hideReplyForm(host);

        expect(host.reply_comment_presenter).toBeNull();
    });

    it("Should update the host reply_comment_presenter content when handleWritingZoneContentChange is called", () => {
        const host = {
            comment: PullRequestCommentPresenterStub.buildGlobalComment(),
            reply_comment_presenter: ReplyCommentFormPresenterStub.buildEmpty(),
        } as unknown as HostElement;

        getController(SaveNewReplyToCommentStub.withDefault()).handleWritingZoneContentChange(
            host,
            "Please rebase"
        );

        expect(host.reply_comment_presenter?.comment_content).toBe("Please rebase");
    });

    it("Should save the new comment, hide the form and add the new comment reply in the collection of replies", async () => {
        const comment = PullRequestCommentPresenterStub.buildGlobalComment();
        const host = {
            comment,
            reply_comment_presenter: ReplyCommentFormPresenterStub.buildWithContent("Please don't"),
            replies: PullRequestCommentRepliesCollectionPresenter.fromReplies([]),
            post_reply_save_callback: vi.fn(),
        } as unknown as HostElement;

        const new_comment_reply_payload = {
            id: 12,
            type: TYPE_GLOBAL_COMMENT,
            content: "Please don't",
        } as PullRequestComment;
        const new_comment_saver =
            SaveNewReplyToCommentStub.withResponsePayload(new_comment_reply_payload);

        await getController(new_comment_saver).saveReply(host);

        expect(new_comment_saver.getNbCalls()).toBe(1);
        expect(new_comment_saver.getLastCallParams()).toStrictEqual(
            ReplyCommentFormPresenterStub.buildBeingSubmitted("Please don't")
        );
        expect(host.reply_comment_presenter).toBeNull();
        expect(host.replies).toStrictEqual([
            PullRequestCommentPresenter.fromCommentReply(comment, new_comment_reply_payload),
        ]);
        expect(host.post_reply_save_callback).toHaveBeenCalledTimes(1);
    });

    it(`Given a root comment with no answer yet
        When a reply has been added to this comment
        Then the root comment is assigned the color provided in the response payload`, async () => {
        const new_comment_reply_payload = {
            id: 13,
            type: TYPE_GLOBAL_COMMENT,
            content: "Please don't",
            color: "flamingo-pink",
        } as PullRequestComment;
        const new_comment_saver =
            SaveNewReplyToCommentStub.withResponsePayload(new_comment_reply_payload);

        const host = {
            comment: PullRequestCommentPresenterStub.buildGlobalComment(),
            reply_comment_presenter: ReplyCommentFormPresenterStub.buildWithContent("Please don't"),
            replies: PullRequestCommentRepliesCollectionPresenter.fromReplies([]),
            post_reply_save_callback: vi.fn(),
        } as unknown as HostElement;

        await getController(new_comment_saver).saveReply(host);

        expect(host.reply_comment_presenter).toBeNull();
        expect(host.comment.color).toBe("flamingo-pink");
        expect(host.post_reply_save_callback).toHaveBeenCalledTimes(1);
    });

    it(`should display the replies associated to the comment`, () => {
        const host = {
            replies: [],
            comment: PullRequestCommentPresenterStub.buildInlineCommentWithData({
                id: 12,
            }),
        } as unknown as PullRequestCommentComponentType;

        replies_store = PullRequestCommentRepliesStore([
            PullRequestCommentPresenterStub.buildInlineCommentWithData({
                parent_id: 12,
            }),
            PullRequestCommentPresenterStub.buildInlineCommentWithData({
                parent_id: 12,
            }),
            PullRequestCommentPresenterStub.buildInlineCommentWithData({
                parent_id: 12,
            }),
        ]);

        getController(SaveNewReplyToCommentStub.withDefault()).displayReplies(host);

        expect(host.replies).toHaveLength(3);
    });

    it("should trigger the on_error_callback when it is defined and an error occurred while saving a reply", async () => {
        const tuleap_api_fault = Fault.fromMessage("You cannot");
        const save_new_comment = SaveNewReplyToCommentStub.withFault(tuleap_api_fault);
        const reply_comment_presenter =
            ReplyCommentFormPresenterStub.buildWithContent("Please don't");
        const comment = PullRequestCommentPresenterStub.buildGlobalComment();
        const host = {
            comment,
            reply_comment_presenter,
        } as unknown as HostElement;

        await getController(save_new_comment).saveReply(host);

        expect(on_error_callback).toHaveBeenCalledWith(tuleap_api_fault);
        expect(host.reply_comment_presenter).toStrictEqual(
            ReplyCommentFormPresenter.buildNotSubmitted(reply_comment_presenter)
        );
    });
});
