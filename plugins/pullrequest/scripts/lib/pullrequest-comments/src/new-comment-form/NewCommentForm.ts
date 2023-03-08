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

import { define, html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import type { Fault } from "@tuleap/fault";
import type { PullRequestComment } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { SaveNewComment } from "./NewCommentSaver";
import { gettext_provider } from "../gettext-provider";

export const PULL_REQUEST_NEW_COMMENT_FORM_ELEMENT_TAG_NAME = "tuleap-pullrequest-new-comment-form";
export type HostElement = NewCommentForm & HTMLElement;

export interface NewCommentFormComponentConfig {
    readonly is_cancel_allowed: boolean;
    readonly is_autofocus_enabled: boolean;
}

export interface NewCommentFormAuthorPresenter {
    readonly avatar_url: string;
}

export interface NewCommentForm {
    readonly content: () => HTMLElement;
    readonly element_height: number;
    readonly after_render_once: unknown;
    readonly post_rendering_callback: (() => void) | undefined;
    readonly on_cancel_callback: (() => void) | undefined;
    readonly error_callback: ((fault: Fault) => void) | undefined;
    readonly post_submit_callback: (new_comment_payload: PullRequestComment) => void;
    readonly comment_saver: SaveNewComment;
    readonly config: NewCommentFormComponentConfig;
    readonly author_presenter: NewCommentFormAuthorPresenter;
    comment: string;
    is_saving_comment: boolean;
}

const onClickSaveComment = (host: NewCommentForm): void => {
    host.is_saving_comment = true;
    host.comment_saver
        .postComment(host.comment)
        .match(
            (payload: PullRequestComment) => {
                host.post_submit_callback(payload);
                host.comment = "";
            },
            (fault) => {
                if (host.error_callback) {
                    host.error_callback(fault);
                    return;
                }

                // eslint-disable-next-line no-console
                console.error(String(fault));
            }
        )
        .finally(() => (host.is_saving_comment = false));
};

const onClickCancel = (host: NewCommentForm): void => {
    host.on_cancel_callback?.();
};

const onTextAreaInput = (host: NewCommentForm, event: InputEvent): void => {
    const textarea = event.target;
    if (!(textarea instanceof HTMLTextAreaElement)) {
        return;
    }

    host.comment = textarea.value;
};

export const getSubmitButton = (host: NewCommentForm): UpdateFunction<NewCommentForm> => {
    const is_disabled = host.is_saving_comment || host.comment.length === 0;

    return html`
        <button
            class="pull-request-comment-footer-action-button tlp-button-small tlp-button-primary"
            disabled="${is_disabled}"
            onclick="${onClickSaveComment}"
            data-test="submit-new-comment-button"
        >
            ${gettext_provider.gettext("Comment")}
            ${host.is_saving_comment &&
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

export const getCancelButton = (host: NewCommentForm): UpdateFunction<NewCommentForm> => {
    if (!host.config.is_cancel_allowed) {
        return html``;
    }

    return html`
        <button
            type="button"
            class="pull-request-comment-footer-action-button tlp-button-small tlp-button-primary tlp-button-outline"
            disabled="${host.is_saving_comment}"
            onclick="${onClickCancel}"
            data-test="cancel-new-comment-button"
        >
            ${gettext_provider.gettext("Cancel")}
        </button>
    `;
};

export const form_height_descriptor = {
    get: (host: NewCommentForm): number => host.content().getBoundingClientRect().height,
    observe(host: NewCommentForm): void {
        host.post_rendering_callback?.();
    },
};

const form_first_render_descriptor = {
    get: (host: NewCommentForm): unknown => host.content(),
    observe(host: NewCommentForm): void {
        if (!host.config.is_autofocus_enabled) {
            return;
        }

        host.content()
            .querySelector<HTMLInputElement>(".pull-request-new-comment-textarea")
            ?.focus();
    },
};

export const NewInlineCommentFormComponent = define<NewCommentForm>({
    tag: PULL_REQUEST_NEW_COMMENT_FORM_ELEMENT_TAG_NAME,
    post_submit_callback: undefined,
    on_cancel_callback: undefined,
    post_rendering_callback: undefined,
    error_callback: undefined,
    element_height: form_height_descriptor,
    after_render_once: form_first_render_descriptor,
    comment_saver: undefined,
    comment: "",
    is_saving_comment: false,
    config: undefined,
    author_presenter: undefined,
    content: (host) => html`
        <div class="pull-request-comment pull-request-new-comment-component">
            <div class="tlp-avatar-medium">
                <img
                    src="${host.author_presenter.avatar_url}"
                    class="media-object"
                    aria-hidden="true"
                />
            </div>
            <div class="pull-request-comment-content pull-request-new-comment-form">
                <textarea
                    class="pull-request-comment-reply-textarea tlp-textarea pull-request-new-comment-textarea"
                    rows="10"
                    value="${host.comment}"
                    oninput="${onTextAreaInput}"
                    placeholder="${gettext_provider.gettext("Say something…")}"
                ></textarea>

                <div class="pull-request-comment-footer" data-test="pull-request-comment-footer">
                    ${getSubmitButton(host)} ${getCancelButton(host)}
                </div>
            </div>
        </div>
    `,
});
