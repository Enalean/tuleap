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
import { loadTooltips } from "@tuleap/tooltip";
import type { ControlPullRequestComment } from "./PullRequestCommentController";
import { getCommentBody } from "./PullRequestCommentBodyTemplate";
import { getCommentFooter } from "./PullRequestCommentFooterTemplate";
import { getCommentAvatarTemplate } from "../templates/CommentAvatarTemplate";
import type { PullRequestCommentPresenter } from "./PullRequestCommentPresenter";
import { PullRequestCommentRepliesCollectionPresenter } from "./PullRequestCommentRepliesCollectionPresenter";
import { gettext_provider } from "../gettext-provider";
import type { HelpRelativeDatesDisplay } from "../helpers/relative-dates-helper";
import type { ElementContainingAWritingZone } from "../types";

export const PULL_REQUEST_COMMENT_ELEMENT_TAG_NAME = "tuleap-pullrequest-comment";
export type HostElement = PullRequestCommentComponentType &
    ElementContainingAWritingZone<PullRequestCommentComponentType> &
    HTMLElement;

type MapOfClasses = Record<string, boolean>;

export type PullRequestCommentComponentType = {
    readonly comment: PullRequestCommentPresenter;
    readonly content: () => HTMLElement;
    readonly after_render_once: unknown;
    readonly element_height: number;
    readonly post_rendering_callback: (() => void) | undefined;
    readonly controller: ControlPullRequestComment;
    readonly is_comment_edition_enabled: boolean;
    relative_date_helper: HelpRelativeDatesDisplay;
    replies: PullRequestCommentRepliesCollectionPresenter;
    is_reply_form_shown: boolean;
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
    get: (host: PullRequestCommentComponentType): unknown => host.content(),
    observe(host: HostElement): void {
        loadTooltips(host, false);
    },
};

export const element_height_descriptor = {
    get: (host: PullRequestCommentComponentType): number =>
        host.content().getBoundingClientRect().height,
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

export const PullRequestCommentComponent = define<PullRequestCommentComponentType>({
    tag: PULL_REQUEST_COMMENT_ELEMENT_TAG_NAME,
    is_comment_edition_enabled: false,
    is_reply_form_shown: false,
    comment: undefined,
    post_rendering_callback: undefined,
    relative_date_helper: undefined,
    after_render_once: after_render_once_descriptor,
    element_height: element_height_descriptor,
    controller: {
        set: (host, controller: ControlPullRequestComment) => {
            host.relative_date_helper = controller.getRelativeDateHelper();
            if (host.comment) {
                controller.displayReplies(host);
            }

            return controller;
        },
    },
    replies: {
        set: setReplies,
    },
    content: (host) => html`
        <div class="pull-request-comment-component">
            <div class="${getCommentClasses(host)}" data-test="pullrequest-comment">
                ${getCommentAvatarTemplate(host.comment.user)}

                <div class="${getCommentContentClasses(host)}">
                    ${getCommentBody(host, gettext_provider)}
                    ${getCommentFooter(host, gettext_provider)}
                </div>
            </div>

            <div class="pull-request-comment-follow-ups">
                ${host.replies.map(
                    (reply: PullRequestCommentPresenter) => html`
                        <tuleap-pullrequest-comment-reply
                            comment="${reply}"
                            is_comment_edition_enabled="${host.is_comment_edition_enabled}"
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
    `,
});
