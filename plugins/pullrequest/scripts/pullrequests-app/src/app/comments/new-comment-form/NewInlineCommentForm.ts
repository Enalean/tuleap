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

import { define, html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import type { FileDiffWidgetType } from "../../file-diff/types";
import type { PullRequestInlineCommentPresenter } from "../PullRequestCommentPresenter";
import type { SaveNewInlineComment } from "./NewInlineCommentSaver";
import { getCancelButtonText, getNewInlineCommentSubmitButtonText } from "../../gettext-catalog";
import { PullRequestCommentPresenter } from "../PullRequestCommentPresenter";
import type { FileDiffCommentPayload } from "../types";

export const TAG_NAME: FileDiffWidgetType = "tuleap-pullrequest-new-comment-form";
export type HostElement = NewInlineCommentForm & HTMLElement;

export interface NewInlineCommentForm {
    readonly content: () => HTMLElement;
    readonly element_height: number;
    readonly after_render_once: unknown;
    readonly post_rendering_callback: () => void;
    readonly on_cancel_callback: () => void;
    readonly post_submit_callback: (new_comment: PullRequestInlineCommentPresenter) => void;
    readonly comment_saver: SaveNewInlineComment;
    comment: string;
    is_saving_comment: boolean;
}

const onClickSaveComment = (host: NewInlineCommentForm): void => {
    host.is_saving_comment = true;
    host.comment_saver
        .postComment(host.comment)
        .match(
            (payload: FileDiffCommentPayload) => {
                host.post_submit_callback(PullRequestCommentPresenter.fromFileDiffComment(payload));
            },
            (fault) => {
                // Do nothing for the moment, we have no way to display a Fault yet
                // eslint-disable-next-line no-console
                console.error(String(fault));
            }
        )
        .finally(() => (host.is_saving_comment = false));
};

const onClickCancel = (host: NewInlineCommentForm): void => {
    host.on_cancel_callback();
};

const onTextAreaInput = (host: NewInlineCommentForm, event: InputEvent): void => {
    const textarea = event.target;
    if (!(textarea instanceof HTMLTextAreaElement)) {
        return;
    }

    host.comment = textarea.value;
};

export const getSubmitButton = (
    host: NewInlineCommentForm
): UpdateFunction<NewInlineCommentForm> => {
    const button_classes = {
        "tlp-button-icon": true,
        "fa-regular": !host.is_saving_comment,
        "fa-comment": !host.is_saving_comment,
        "fa-solid": host.is_saving_comment,
        "fa-spin": host.is_saving_comment,
        "fa-circle-notch": host.is_saving_comment,
    };
    const is_disabled = host.is_saving_comment || host.comment.length === 0;
    return html`
        <button
            class="tlp-button-primary"
            disabled="${is_disabled}"
            onclick="${onClickSaveComment}"
            data-test="submit-new-comment-button"
        >
            <i class="${button_classes}" data-test="submit-button-icon" aria-hidden="true"></i>
            ${getNewInlineCommentSubmitButtonText()}
        </button>
    `;
};

export const getCancelButton = (
    host: NewInlineCommentForm
): UpdateFunction<NewInlineCommentForm> => html`
    <button
        type="button"
        class="tlp-button-primary tlp-button-outline"
        disabled="${host.is_saving_comment}"
        onclick="${onClickCancel}"
        data-test="cancel-new-comment-button"
    >
        <i class="tlp-button-icon fa-solid fa-times" aria-hidden="true"></i>
        ${getCancelButtonText()}
    </button>
`;

export const form_height_descriptor = {
    get: (host: NewInlineCommentForm): number => host.content().getBoundingClientRect().height,
    observe(host: NewInlineCommentForm): void {
        host.post_rendering_callback();
    },
};

const form_first_render_descriptor = {
    get: (host: NewInlineCommentForm): unknown => host.content(),
    observe(host: NewInlineCommentForm): void {
        host.content()
            .querySelector<HTMLInputElement>(".pull-request-new-inline-comment-text")
            ?.focus();
    },
};

define<NewInlineCommentForm>({
    tag: TAG_NAME,
    post_submit_callback: undefined,
    on_cancel_callback: undefined,
    post_rendering_callback: undefined,
    element_height: form_height_descriptor,
    after_render_once: form_first_render_descriptor,
    comment_saver: undefined,
    comment: "",
    is_saving_comment: false,
    content: (host) => html`
        <div class="pull-request-new-inline-comment">
            <i class="fa fa-plus-circle"></i>
            <div class="arrow"></div>
            <div class="pull-request-new-inline-comment-content">
                <textarea
                    class="tlp-textarea pull-request-new-inline-comment-text"
                    value="${host.comment}"
                    oninput="${onTextAreaInput}"
                ></textarea>

                <div class="pull-request-new-inline-comment-controls">
                    ${getSubmitButton(host)} ${getCancelButton(host)}
                </div>
            </div>
        </div>
    `,
});
