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
import { StorePullRequestCommentRepliesStub } from "../../../tests/stubs/StorePullRequestCommentRepliesStub";

describe("PullRequestCommentController", () => {
    let focus_helper: FocusReplyToCommentTextArea, replies_store: StorePullRequestCommentReplies;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });

        focus_helper = FocusTextReplyToCommentAreaStub();
        replies_store = StorePullRequestCommentRepliesStub.withNoReplies();
    });

    it("should show the reply to comment form and sets the focus on the textarea", () => {
        const host = {
            is_reply_form_displayed: false,
        } as unknown as HostElement;

        PullRequestCommentController(focus_helper, replies_store).showReplyForm(host);

        expect(host.is_reply_form_displayed).toBe(true);
        expect(focus_helper.focusFormReplyToCommentTextArea).toHaveBeenCalledTimes(1);
    });

    it("should hide the reply to comment form", () => {
        const host = {
            is_reply_form_displayed: true,
        } as unknown as PullRequestComment;

        PullRequestCommentController(focus_helper, replies_store).hideReplyForm(host);

        expect(host.is_reply_form_displayed).toBe(false);
    });

    it("should display the replies associated to the comment", () => {
        const host = {
            replies: [],
        } as unknown as PullRequestComment;

        PullRequestCommentController(
            focus_helper,
            StorePullRequestCommentRepliesStub.withReplies()
        ).displayReplies(host);

        expect(host.replies).toHaveLength(3);
    });
});
