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

import type { PullRequestComment } from "./PullRequestComment";
import type { FocusReplyToCommentTextArea } from "./PullRequestCommentReplyFormFocusHelper";
import type { StorePullRequestCommentReplies } from "./PullRequestCommentRepliesStore";

export interface ControlPullRequestComment {
    showReplyForm: (host: PullRequestComment) => void;
    hideReplyForm: (host: PullRequestComment) => void;
    displayReplies: (host: PullRequestComment) => void;
}

export const PullRequestCommentController = (
    focus_helper: FocusReplyToCommentTextArea,
    replies_store: StorePullRequestCommentReplies
): ControlPullRequestComment => ({
    showReplyForm: (host: PullRequestComment): void => {
        host.is_reply_form_displayed = true;

        focus_helper.focusFormReplyToCommentTextArea(host);
    },
    hideReplyForm: (host: PullRequestComment): void => {
        host.is_reply_form_displayed = false;
    },
    displayReplies: (host: PullRequestComment): void => {
        host.replies = replies_store.getCommentReplies(host.comment);
    },
});
