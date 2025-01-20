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

import type { UpdateFunction } from "hybrids";
import { define, html } from "hybrids";
import { loadTooltips } from "@tuleap/tooltip";
import { getCommentAvatarTemplate } from "../templates/CommentAvatarTemplate";
import type { HelpRelativeDatesDisplay } from "../helpers/relative-dates-helper";
import { gettext_provider } from "../gettext-provider";
import type { ControlPullRequestComment } from "./PullRequestCommentController";
import { getCommentBody } from "./PullRequestCommentBodyTemplate";
import { getCommentFooter } from "./PullRequestCommentFooterTemplate";
import type { PullRequestCommentPresenter } from "./PullRequestCommentPresenter";
import { PullRequestCommentRepliesCollectionPresenter } from "./PullRequestCommentRepliesCollectionPresenter";

export const PULL_REQUEST_COMMENT_ELEMENT_TAG_NAME = "tuleap-pullrequest-comment";
export type HostElement = PullRequestCommentComponentType & HTMLElement;

type MapOfClasses = Record<string, boolean>;

export type PullRequestCommentComponentType = {
    render(): HTMLElement;
    readonly after_render_once: unknown;
    readonly element_height: number;
    readonly post_rendering_callback: (() => void) | undefined;
    readonly controller: ControlPullRequestComment;
    comment: PullRequestCommentPresenter;
    relative_date_helper: HelpRelativeDatesDisplay;
    replies: PullRequestCommentRepliesCollectionPresenter;
    is_reply_form_shown: boolean;
    is_edition_form_shown: boolean;
};

const getCommentClasses = (host: PullRequestCommentComponentType): MapOfClasses => {
    const classes: MapOfClasses = {
        "pull-request-comment": true,
    };

    classes[host.comment.type] = true;

    return classes;
};

const getCommentContentClasses = (host: PullRequestCommentComponentType): MapOfClasses => {
    const classes: MapOfClasses = {
        "pull-request-comment-content": true,
    };

    if (host.comment.color) {
        classes[`pull-request-comment-content-color`] = true;
        classes[`tlp-swatch-${host.comment.color}`] = true;
    }

    return classes;
};

export const setReplies = (
    host: PullRequestCommentComponentType,
    presenter: PullRequestCommentRepliesCollectionPresenter | undefined,
): PullRequestCommentRepliesCollectionPresenter => {
    if (!presenter) {
        return PullRequestCommentRepliesCollectionPresenter.buildEmpty();
    }

    return presenter;
};

export const after_render_once_descriptor = {
    value: (host: PullRequestCommentComponentType): unknown => host.render(),
    observe(host: HostElement): void {
        loadTooltips(host, false);
    },
};

export const element_height_descriptor = {
    value: (host: PullRequestCommentComponentType): number =>
        host.render().getBoundingClientRect().height,
    observe(host: PullRequestCommentComponentType): void {
        setTimeout(() => {
            host.post_rendering_callback?.();
        });
    },
};

const isLastReply = (
    host: PullRequestCommentComponentType,
    comment: PullRequestCommentPresenter,
): boolean => {
    if (host.replies.length === 0) {
        return true;
    }

    return host.replies[host.replies.length - 1].id === comment.id;
};

const getCommentContent = (
    host: PullRequestCommentComponentType,
): UpdateFunction<PullRequestCommentComponentType> => {
    if (host.is_edition_form_shown) {
        return html` <tuleap-pullrequest-comment-edition-form
            class="pull-request-comment-content"
            controller="${host.controller.buildCommentEditionController(host)}"
            comment="${host.comment}"
            project_id="${host.controller.getProjectId()}"
        ></tuleap-pullrequest-comment-edition-form>`;
    }

    return html`
        <div class="${getCommentContentClasses(host)}">
            ${getCommentBody(host, gettext_provider)} ${getCommentFooter(host, gettext_provider)}
        </div>
    `;
};

export const renderComment = (
    host: PullRequestCommentComponentType,
): UpdateFunction<PullRequestCommentComponentType> => html`
    <div class="pull-request-comment-component">
        <div class="${getCommentClasses(host)}" data-test="pullrequest-comment">
            ${getCommentAvatarTemplate(host.comment.user)} ${getCommentContent(host)}
        </div>

        <div class="pull-request-comment-follow-ups">
            ${host.replies.map(
                (reply: PullRequestCommentPresenter) => html`
                    <tuleap-pullrequest-comment-reply
                        comment="${reply}"
                        controller="${host.controller.buildReplyController()}"
                        onshow-reply-form="${(): void => host.controller.showReplyForm(host)}"
                        onhide-reply-form="${(): void => host.controller.hideReplyForm(host)}"
                        is_last_reply="${isLastReply(host, reply)}"
                    ></tuleap-pullrequest-comment-reply>
                `,
            )}
            ${host.is_reply_form_shown &&
            html`
                <tuleap-pullrequest-new-comment-form
                    class="pull-request-comment-reply-form"
                    controller="${host.controller.buildReplyCreationController(host)}"
                ></tuleap-pullrequest-new-comment-form>
            `}
        </div>
    </div>
`;

export const PullRequestCommentComponent = define<PullRequestCommentComponentType>({
    tag: PULL_REQUEST_COMMENT_ELEMENT_TAG_NAME,
    is_reply_form_shown: false,
    is_edition_form_shown: false,
    comment: {
        value: (host, value) => value,
        observe: (host) => {
            if (host.comment) {
                host.controller.displayReplies(host);
            }
        },
    },

    post_rendering_callback: undefined,
    relative_date_helper: (host: PullRequestCommentComponentType) => {
        return host.controller.getRelativeDateHelper();
    },
    after_render_once: after_render_once_descriptor,
    element_height: element_height_descriptor,
    controller: (host, controller: ControlPullRequestComment) => controller,
    replies: setReplies,
    render: renderComment,
});
