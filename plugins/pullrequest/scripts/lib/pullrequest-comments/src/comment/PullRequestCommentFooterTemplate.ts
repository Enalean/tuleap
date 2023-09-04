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
import type { GettextProvider } from "@tuleap/gettext";
import { TYPE_EVENT_PULLREQUEST_ACTION } from "@tuleap/plugin-pullrequest-constants";
import type { PullRequestCommentComponentType } from "./PullRequestComment";
import type { PullRequestCommentPresenter } from "./PullRequestCommentPresenter";

const isLastReply = (
    host: PullRequestCommentComponentType,
    comment: PullRequestCommentPresenter,
): boolean => {
    if (host.replies.length === 0) {
        return host.comment.id !== comment.id;
    }

    return host.replies[host.replies.length - 1].id !== comment.id;
};

export const buildFooterForComment = (
    host: PullRequestCommentComponentType,
    comment: PullRequestCommentPresenter,
    gettext_provider: GettextProvider,
): UpdateFunction<PullRequestCommentComponentType> => {
    if (comment.type === TYPE_EVENT_PULLREQUEST_ACTION) {
        return html``;
    }

    if (isLastReply(host, comment)) {
        return html``;
    }

    const onClickToggleReplyForm = (host: PullRequestCommentComponentType): void => {
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
                ${gettext_provider.gettext("Reply")}
            </button>
        </div>
    `;
};

export const getCommentFooter = (
    host: PullRequestCommentComponentType,
    gettext_provider: GettextProvider,
): UpdateFunction<PullRequestCommentComponentType> =>
    buildFooterForComment(host, host.comment, gettext_provider);
