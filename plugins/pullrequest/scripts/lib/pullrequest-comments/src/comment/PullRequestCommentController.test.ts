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
import { SaveCommentStub } from "../../tests/stubs/SaveCommentStub";
import { CurrentPullRequestUserPresenterStub } from "../../tests/stubs/CurrentPullRequestUserPresenterStub";
import { PullRequestCommentPresenterStub } from "../../tests/stubs/PullRequestCommentPresenterStub";
import { NewCommentFormPresenterStub } from "../../tests/stubs/NewCommentFormPresenterStub";
import { PullRequestCommentRepliesCollectionPresenter } from "./PullRequestCommentRepliesCollectionPresenter";
import { PullRequestCommentPresenter } from "./PullRequestCommentPresenter";
import { PullRequestCommentRepliesStore } from "./PullRequestCommentRepliesStore";
import { CurrentPullRequestPresenterStub } from "../../tests/stubs/CurrentPullRequestPresenterStub";
import type { SaveComment } from "../new-comment-form/types";
import type { HostElement as NewCommentFormElement } from "../new-comment-form/NewCommentForm";
import type { ControlWritingZone } from "../writing-zone/WritingZoneController";
import type { CurrentPullRequestUserPresenter, PullRequestCommentErrorCallback } from "../types";
import type { PullRequestPresenter } from "./PullRequestPresenter";

describe("PullRequestCommentController", () => {
    let replies_store: StorePullRequestCommentReplies,
        on_error_callback: PullRequestCommentErrorCallback,
        current_pull_request: PullRequestPresenter,
        current_user: CurrentPullRequestUserPresenter;

    beforeEach(() => {
        replies_store = PullRequestCommentRepliesStore([]);
        on_error_callback = vi.fn();
        current_pull_request = CurrentPullRequestPresenterStub.withDefault();
        current_user = CurrentPullRequestUserPresenterStub.withDefault();
    });

    const getController = (save_reply: SaveComment): ControlPullRequestComment =>
        PullRequestCommentController(
            replies_store,
            save_reply,
            current_user,
            current_pull_request,
            on_error_callback,
        );

    const saveReply = (host: HostElement, save_reply: SaveComment): Promise<void> => {
        const reply_creation_controller =
            getController(save_reply).buildReplyCreationController(host);
        return reply_creation_controller.saveNewComment({
            presenter: NewCommentFormPresenterStub.buildWithContent("Please don't"),
            writing_zone_controller: {
                resetWritingZone: () => {
                    // Do nothing,
                },
            } as unknown as ControlWritingZone,
        } as NewCommentFormElement);
    };

    it("should show the reply to comment form and sets the focus on the textarea", () => {
        const content = document.implementation.createHTMLDocument().createElement("div");
        const host = {
            comment: PullRequestCommentPresenterStub.buildGlobalComment(),
            content: () => content,
        } as unknown as HostElement;

        getController(SaveCommentStub.withDefault()).showReplyForm(host);

        expect(host.is_reply_form_shown).toBe(true);
    });

    it("should hide the reply to comment form", () => {
        const host = {
            comment: PullRequestCommentPresenterStub.buildGlobalComment(),
        } as unknown as PullRequestCommentComponentType;

        getController(SaveCommentStub.withDefault()).hideReplyForm(host);

        expect(host.is_reply_form_shown).toBe(false);
    });

    it("When a reply has been created, then it should hide the form and add the new comment reply to the collection of replies", async () => {
        const comment = PullRequestCommentPresenterStub.buildGlobalComment();
        const host = {
            comment,
            is_reply_form_shown: true,
            replies: PullRequestCommentRepliesCollectionPresenter.fromReplies([]),
            post_reply_save_callback: vi.fn(),
        } as unknown as HostElement;

        const new_comment_reply_payload = {
            id: 12,
            type: TYPE_GLOBAL_COMMENT,
            content: "Please don't",
        } as PullRequestComment;

        await saveReply(host, SaveCommentStub.withResponsePayload(new_comment_reply_payload));

        expect(host.is_reply_form_shown).toBe(false);
        expect(host.replies).toStrictEqual([
            PullRequestCommentPresenter.fromCommentReply(comment, new_comment_reply_payload),
        ]);
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

        const host = {
            comment: PullRequestCommentPresenterStub.buildGlobalComment(),
            reply_comment_presenter: NewCommentFormPresenterStub.buildWithContent("Please don't"),
            replies: PullRequestCommentRepliesCollectionPresenter.fromReplies([]),
            post_reply_save_callback: vi.fn(),
        } as unknown as HostElement;

        await saveReply(host, SaveCommentStub.withResponsePayload(new_comment_reply_payload));

        expect(host.is_reply_form_shown).toBe(false);
        expect(host.comment.color).toBe("flamingo-pink");
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

        getController(SaveCommentStub.withDefault()).displayReplies(host);

        expect(host.replies).toHaveLength(3);
    });

    it("should trigger the on_error_callback when it is defined and an error occurred while saving a reply", async () => {
        const tuleap_api_fault = Fault.fromMessage("You cannot");

        await saveReply(
            {
                comment: PullRequestCommentPresenterStub.buildGlobalComment(),
                reply_comment_presenter:
                    NewCommentFormPresenterStub.buildWithContent("Please don't"),
            } as unknown as HostElement,
            SaveCommentStub.withFault(tuleap_api_fault),
        );

        expect(on_error_callback).toHaveBeenCalledWith(tuleap_api_fault);
    });

    it("getProjectId() should return the current project id", () => {
        expect(getController(SaveCommentStub.withDefault()).getProjectId()).toBe(105);
    });

    it("getCurrentUserId() should return the current user id", () => {
        expect(getController(SaveCommentStub.withDefault()).getCurrentUserId()).toBe(104);
    });
});
