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
import type { NewCommentForm } from "./NewCommentForm";
import { getCommentAvatarTemplate } from "../templates/CommentAvatarTemplate";

import "../writing-zone/WritingZone";

export const getSubmitButton = (
    host: NewCommentForm,
    gettext_provider: GettextProvider,
): UpdateFunction<NewCommentForm> => {
    const is_disabled = host.presenter.is_saving_comment || host.presenter.comment.length === 0;
    const onClickSave = (): void => {
        host.controller.saveNewComment(host);
    };

    return html`
        <button
            class="pull-request-comment-footer-action-button tlp-button-small tlp-button-primary"
            disabled="${is_disabled}"
            onclick="${onClickSave}"
            data-test="submit-new-comment-button"
        >
            ${gettext_provider.gettext("Comment")}
            ${host.presenter.is_saving_comment &&
            html`
                <i
                    class="fa-solid fa-circle-notch fa-spin tlp-button-icon-right"
                    aria-hidden="true"
                    data-test="comment-being-saved-spinner"
                ></i>
            `}
        </button>
    `;
};

export const getCancelButton = (
    host: NewCommentForm,
    gettext_provider: GettextProvider,
): UpdateFunction<NewCommentForm> => {
    if (!host.presenter.is_cancel_allowed) {
        return html``;
    }

    const onClickCancel = (): void => {
        host.controller.cancelNewComment(host);
    };

    return html`
        <button
            type="button"
            class="pull-request-comment-footer-action-button tlp-button-small tlp-button-primary tlp-button-outline"
            disabled="${host.presenter.is_saving_comment}"
            onclick="${onClickCancel}"
            data-test="cancel-new-comment-button"
        >
            ${gettext_provider.gettext("Cancel")}
        </button>
    `;
};

export const getNewCommentFormContent = (
    host: NewCommentForm,
    gettext_provider: GettextProvider,
): UpdateFunction<NewCommentForm> => html`
    <div class="pull-request-comment pull-request-new-comment-component">
        ${getCommentAvatarTemplate(host.presenter.author)}
        <div class="pull-request-comment-content" data-test="new-comment-form-content">
            ${host.writing_zone}
            <div class="pull-request-comment-footer" data-test="pull-request-comment-footer">
                ${getCancelButton(host, gettext_provider)}
                ${getSubmitButton(host, gettext_provider)}
            </div>
        </div>
    </div>
`;
