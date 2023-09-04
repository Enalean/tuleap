/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { UpdateFunction } from "hybrids";
import { define, dispatch, html } from "hybrids";
import {
    getChangesetsCommentMessage,
    getCommentsSectionTitle,
    getEmptyCommentsMessage,
} from "../../../gettext-catalog";
import { loadTooltips } from "@tuleap/tooltip";
import type { CommentsControllerType } from "../../../domain/comments/CommentsController";
import { CommentsPresenter } from "./CommentsPresenter";
import "./FollowupEditor";
import { getCommentTemplate } from "./CommentTemplate";
import type { NewComment } from "../../../domain/comments/NewComment";
import type { FormattedTextControllerType } from "../../../domain/common/FormattedTextController";

type MapOfClasses = Record<string, boolean>;

export const onValueChanged = (host: HostElement, event: CustomEvent<NewComment>): void => {
    dispatch(host, "new-comment", { detail: event.detail });
};

export const getNewCommentClasses = (is_comment_order_inverted: boolean): MapOfClasses => ({
    "artifact-modal-followups-add-form": true,
    "invert-order": is_comment_order_inverted,
});

const getNewCommentTemplate = (
    host: InternalModalCommentsSection,
): UpdateFunction<InternalModalCommentsSection> => {
    if (!host.presenter.preferences.is_allowed_to_add_comment) {
        return html``;
    }
    return html`<tuleap-artifact-modal-followup-editor
        class="${getNewCommentClasses(host.presenter.preferences.is_comment_order_inverted)}"
        format="${host.presenter.preferences.text_format}"
        controller="${host.formattedTextController}"
        onvalue-changed="${onValueChanged}"
        data-test="add-comment-form"
    ></tuleap-artifact-modal-followup-editor>`;
};

export const getSectionClasses = (is_comment_order_inverted: boolean): MapOfClasses => ({
    "tuleap-artifact-modal-followups-comments": true,
    "invert-order": is_comment_order_inverted,
});

export const getSectionTemplate = (
    host: InternalModalCommentsSection,
): UpdateFunction<InternalModalCommentsSection> => {
    if (host.presenter.is_loading) {
        return html`<div>
            <i
                class="fa-solid fa-circle-notch fa-spin"
                aria-hidden="true"
                data-test="comments-spinner"
            ></i>
        </div>`;
    }
    if (host.presenter.comments.length === 0) {
        return html`<div
                class="tuleap-artifact-modal-followups-comments-empty"
                data-test="comments-empty"
            >
                ${getEmptyCommentsMessage()}
            </div>
            ${getNewCommentTemplate(host)}`;
    }

    return html`<div
            class="${getSectionClasses(host.presenter.preferences.is_comment_order_inverted)}"
        >
            ${host.presenter.comments.map((comment) =>
                getCommentTemplate(comment, host.presenter.preferences),
            )}
        </div>
        ${getNewCommentTemplate(host)}`;
};

export type ModalCommentsSection = {
    readonly controller: CommentsControllerType;
    readonly formattedTextController: FormattedTextControllerType;
};
type InternalModalCommentsSection = ModalCommentsSection & {
    presenter: CommentsPresenter;
    content(): HTMLElement;
};
export type HostElement = InternalModalCommentsSection & HTMLElement;

export const ModalCommentsSection = define<InternalModalCommentsSection>({
    tag: "tuleap-artifact-modal-comments-section",
    presenter: undefined,
    controller: {
        set(host, controller: CommentsControllerType) {
            const preferences = controller.getPreferences();
            host.presenter = CommentsPresenter.buildLoading(preferences);
            controller.getComments().then((comments) => {
                host.presenter = CommentsPresenter.fromCommentsAndPreferences(
                    comments,
                    preferences,
                );
                host.content();
                loadTooltips(host);
            });
            return controller;
        },
    },
    formattedTextController: undefined,
    content: (host) =>
        html` <h2
                class="tlp-modal-subtitle tuleap-artifact-modal-followups-title"
                title="${getChangesetsCommentMessage()}"
            >
                <i
                    class="fa-regular fa-comments tuleap-artifact-modal-followups-title-icon"
                    aria-hidden="true"
                ></i>
                ${getCommentsSectionTitle()}
            </h2>
            ${getSectionTemplate(host)}`,
});
