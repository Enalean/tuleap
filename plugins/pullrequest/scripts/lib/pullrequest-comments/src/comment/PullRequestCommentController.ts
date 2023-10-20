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

import type { PullRequestComment } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { CurrentPullRequestUserPresenter, PullRequestCommentErrorCallback } from "../types";
import { RelativeDatesHelper } from "../helpers/relative-dates-helper";
import type { HelpRelativeDatesDisplay } from "../helpers/relative-dates-helper";
import type { ControlNewCommentForm } from "../new-comment-form/NewCommentFormController";
import { NewCommentFormController } from "../new-comment-form/NewCommentFormController";
import type { SaveComment } from "../new-comment-form/types";
import type { ControlPullRequestCommentReply } from "./comment-reply/PullRequestCommentReplyController";
import { PullRequestCommentReplyController } from "./comment-reply/PullRequestCommentReplyController";
import type { PullRequestCommentComponentType } from "./PullRequestComment";
import type { StorePullRequestCommentReplies } from "./PullRequestCommentRepliesStore";
import { PullRequestCommentPresenter } from "./PullRequestCommentPresenter";
import type { PullRequestPresenter } from "./PullRequestPresenter";
import { ReplyContext } from "./ReplyContext";

export type ControlPullRequestComment = {
    showReplyForm(host: PullRequestCommentComponentType): void;
    hideReplyForm(host: PullRequestCommentComponentType): void;
    showEditionForm(host: PullRequestCommentComponentType): void;
    hideEditionForm(host: PullRequestCommentComponentType): void;
    displayReplies(host: PullRequestCommentComponentType): void;
    getRelativeDateHelper(): HelpRelativeDatesDisplay;
    buildReplyController(): ControlPullRequestCommentReply;
    buildReplyCreationController(host: PullRequestCommentComponentType): ControlNewCommentForm;
    getProjectId(): number;
    getCurrentUserId(): number;
};

export const PullRequestCommentController = (
    replies_store: StorePullRequestCommentReplies,
    save_reply: SaveComment,
    current_user: CurrentPullRequestUserPresenter,
    current_pull_request: PullRequestPresenter,
    on_error_callback?: PullRequestCommentErrorCallback,
): ControlPullRequestComment => ({
    showReplyForm: (host: PullRequestCommentComponentType): void => {
        host.is_reply_form_shown = true;
    },
    hideReplyForm: (host: PullRequestCommentComponentType): void => {
        host.is_reply_form_shown = false;
    },
    showEditionForm: (host: PullRequestCommentComponentType): void => {
        host.is_edition_form_shown = true;
    },
    hideEditionForm: (host: PullRequestCommentComponentType): void => {
        host.is_edition_form_shown = false;
    },
    displayReplies: (host: PullRequestCommentComponentType): void => {
        host.replies = replies_store.getCommentReplies(host.comment);
    },
    getRelativeDateHelper: (): HelpRelativeDatesDisplay =>
        RelativeDatesHelper(
            current_user.preferred_date_format,
            current_user.preferred_relative_date_display,
            current_user.user_locale,
        ),

    buildReplyController: (): ControlPullRequestCommentReply =>
        PullRequestCommentReplyController(current_user, current_pull_request),

    buildReplyCreationController: (
        host: PullRequestCommentComponentType,
    ): ControlNewCommentForm => {
        return NewCommentFormController(
            save_reply,
            current_user,
            {
                is_cancel_allowed: true,
                project_id: current_pull_request.project_id,
                is_autofocus_enabled: true,
            },
            ReplyContext.fromComment(host.comment, current_user, current_pull_request),
            (comment_payload: PullRequestComment) => {
                host.is_reply_form_shown = false;

                replies_store.addReplyToComment(
                    host.comment,
                    PullRequestCommentPresenter.fromCommentReply(host.comment, comment_payload),
                );

                host.replies = replies_store.getCommentReplies(host.comment);
                host.comment.color = comment_payload.color;
            },
            (fault) => {
                if (on_error_callback) {
                    on_error_callback(fault);
                    return;
                }
                // eslint-disable-next-line no-console
                console.error(String(fault));
            },
            () => {
                host.is_reply_form_shown = false;
            },
        );
    },

    getProjectId: () => current_pull_request.project_id,

    getCurrentUserId: (): number => current_user.user_id,
});
