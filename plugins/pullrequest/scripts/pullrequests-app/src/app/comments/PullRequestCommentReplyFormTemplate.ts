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
import type { PullRequestComment } from "./PullRequestComment";
import {
    getReplyToCommentButtonText,
    getCancelButtonText,
    getCommentTextAreaPlaceholderText,
} from "../gettext-catalog";

export const getReplyFormTemplate = (
    host: PullRequestComment
): UpdateFunction<PullRequestComment> => {
    if (!host.is_reply_form_displayed) {
        return html``;
    }

    const onClickHideReplyForm = (host: PullRequestComment): void => {
        host.controller.hideReplyForm(host);
    };

    return html`
        <div class="pull-request-comment-reply-form" data-test="pull-request-comment-reply-form">
            <div class="pull-request-comment pull-request-comment-follow-up-content">
                <div class="tlp-avatar">
                    <img
                        src="${host.currentUser.avatar_url}"
                        class="media-object"
                        aria-hidden="true"
                    />
                </div>
                <div class="pull-request-comment-content">
                    <textarea
                        class="pull-request-comment-reply-textarea tlp-textarea"
                        rows="10"
                        placeholder="${getCommentTextAreaPlaceholderText()}"
                    ></textarea>
                    <div
                        class="pull-request-comment-footer"
                        data-test="pull-request-comment-footer"
                    >
                        <button
                            type="button"
                            class="pull-request-comment-footer-action-button tlp-button-small tlp-button-primary tlp-button-outline"
                            onclick="${onClickHideReplyForm}"
                            data-test="button-cancel-reply"
                        >
                            ${getCancelButtonText()}
                        </button>
                        <button
                            type="button"
                            class="pull-request-comment-footer-action-button tlp-button-small tlp-button-primary disabled"
                            disabled
                        >
                            ${getReplyToCommentButtonText()}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
};
