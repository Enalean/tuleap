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

import { html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import { getReplyToCommentButtonText } from "../gettext-catalog";
import type { PullRequestComment } from "./PullRequestComment";
import type { PullRequestCommentPresenter } from "./PullRequestCommentPresenter";
import { TYPE_EVENT_COMMENT } from "./PullRequestCommentPresenter";

export const buildFooterForComment = (
    host: PullRequestComment,
    comment: PullRequestCommentPresenter
): UpdateFunction<PullRequestComment> => {
    if (comment.type === TYPE_EVENT_COMMENT) {
        return html``;
    }

    const onClickToggleReplyForm = (host: PullRequestComment): void => {
        host.controller.showReplyForm(host);
    };

    return html`
        <div class="pull-request-comment-footer" data-test="pull-request-comment-footer">
            <button
                type="button"
                class="pull-request-comment-footer-action-button tlp-button-small tlp-button-primary tlp-button-outline"
                onclick="${onClickToggleReplyForm}"
                data-test="button-reply-to-comment"
            >
                ${getReplyToCommentButtonText()}
            </button>
        </div>
    `;
};

export const getCommentFooter = (host: PullRequestComment): UpdateFunction<PullRequestComment> =>
    buildFooterForComment(host, host.comment);
