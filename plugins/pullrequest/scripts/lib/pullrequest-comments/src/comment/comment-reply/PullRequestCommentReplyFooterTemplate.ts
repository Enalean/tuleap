/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
import { FORMAT_COMMONMARK } from "@tuleap/plugin-pullrequest-constants";
import type { InternalPullRequestCommentReply, HostElement } from "./PullRequestCommentReply";

const shouldDisplayEditButton = (host: InternalPullRequestCommentReply): boolean =>
    host.is_comment_edition_enabled &&
    host.comment.format === FORMAT_COMMONMARK &&
    host.comment.user.id === host.controller.getCurrentUserId();

const getEditButton = (
    gettext_provider: GettextProvider,
): UpdateFunction<InternalPullRequestCommentReply> => {
    const onClickToggleEditionForm = (host: InternalPullRequestCommentReply): void => {
        host.controller.showEditionForm(host);
    };

    return html`
        <button
            type="button"
            class="pull-request-comment-footer-action-button tlp-button-small tlp-button-primary tlp-button-outline"
            onclick="${onClickToggleEditionForm}"
            data-test="button-edit-comment"
        >
            ${gettext_provider.gettext("Edit")}
        </button>
    `;
};

const getReplyButton = (
    gettext_provider: GettextProvider,
): UpdateFunction<InternalPullRequestCommentReply> => {
    const onClickToggleReplyForm = (host: HostElement): void => {
        host.controller.showReplyForm(host);
    };

    return html`
        <button
            type="button"
            class="pull-request-comment-footer-action-button tlp-button-small tlp-button-primary tlp-button-outline"
            onclick="${onClickToggleReplyForm}"
            data-test="button-reply-to-comment"
        >
            ${gettext_provider.gettext("Reply")}
        </button>
    `;
};

export const buildFooterForComment = (
    host: InternalPullRequestCommentReply,
    gettext_provider: GettextProvider,
): UpdateFunction<InternalPullRequestCommentReply> => {
    const is_edit_button_displayed = shouldDisplayEditButton(host);
    const is_reply_button_displayed = host.is_last_reply;
    if (!is_edit_button_displayed && !is_reply_button_displayed) {
        return html``;
    }

    return html`
        <div class="pull-request-comment-footer" data-test="pull-request-comment-footer">
            ${is_edit_button_displayed ? getEditButton(gettext_provider) : html``}
            ${is_reply_button_displayed ? getReplyButton(gettext_provider) : html``}
        </div>
    `;
};
