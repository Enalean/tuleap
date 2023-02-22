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
import { getReplyFormTemplate } from "./PullRequestCommentReplyFormTemplate";
import { getCommentReplyTemplate } from "./PullRequestCommentReplyTemplate";
import type { PullRequestCommentPresenter } from "./PullRequestCommentPresenter";
import { PullRequestCommentRepliesCollectionPresenter } from "./PullRequestCommentRepliesCollectionPresenter";
import type { ReplyCommentFormPresenter } from "./ReplyCommentFormPresenter";
import { gettext_provider } from "../gettext-provider";
import type { HelpRelativeDatesDisplay } from "../types";

export const PULL_REQUEST_COMMENT_ELEMENT_TAG_NAME = "tuleap-pullrequest-comment";
export type HostElement = PullRequestComment & HTMLElement;

type MapOfClasses = Record<string, boolean>;

export interface PullRequestComment {
    readonly comment: PullRequestCommentPresenter;
    readonly content: () => HTMLElement;
    readonly after_render_once: unknown;
    readonly element_height: number;
    readonly post_rendering_callback: (() => void) | undefined;
    readonly relativeDateHelper: HelpRelativeDatesDisplay;
    readonly controller: ControlPullRequestComment;
    replies: PullRequestCommentRepliesCollectionPresenter;
    reply_comment_presenter: ReplyCommentFormPresenter | null;
}

const getCommentClasses = (host: PullRequestComment): MapOfClasses => {
    const classes: MapOfClasses = {
        "pull-request-comment": true,
    };

    classes[host.comment.type] = true;

    return classes;
};

const getCommentContentClasses = (host: PullRequestComment): MapOfClasses => {
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
    host: PullRequestComment,
    presenter: PullRequestCommentRepliesCollectionPresenter | undefined
): PullRequestCommentRepliesCollectionPresenter => {
    if (!presenter) {
        return PullRequestCommentRepliesCollectionPresenter.buildEmpty();
    }

    return presenter;
};

export const setNewCommentState = (
    host: PullRequestComment,
    presenter: ReplyCommentFormPresenter | undefined
): ReplyCommentFormPresenter | null => {
    if (!presenter) {
        return null;
    }

    return presenter;
};

export const after_render_once_descriptor = {
    get: (host: PullRequestComment): unknown => host.content(),
    observe(): void {
        loadTooltips();
    },
};

export const element_height_descriptor = {
    get: (host: PullRequestComment): number => host.content().getBoundingClientRect().height,
    observe(host: PullRequestComment): void {
        host.post_rendering_callback?.();
    },
};

export const PullRequestComment = define<PullRequestComment>({
    tag: PULL_REQUEST_COMMENT_ELEMENT_TAG_NAME,
    comment: undefined,
    post_rendering_callback: undefined,
    relativeDateHelper: undefined,
    after_render_once: after_render_once_descriptor,
    element_height: element_height_descriptor,
    controller: {
        set: (host, controller: ControlPullRequestComment) => {
            if (host.comment) {
                controller.displayReplies(host);
            }

            return controller;
        },
    },
    replies: {
        set: setReplies,
    },
    reply_comment_presenter: {
        set: setNewCommentState,
    },
    content: (host) => html`
        <div class="pull-request-comment-component">
            <div class="${getCommentClasses(host)}" data-test="pullrequest-comment">
                <div class="tlp-avatar-medium">
                    <img
                        src="${host.comment.user.avatar_url}"
                        class="media-object"
                        aria-hidden="true"
                    />
                </div>

                <div class="${getCommentContentClasses(host)}">
                    ${getCommentBody(host, gettext_provider)}
                    ${getCommentFooter(host, gettext_provider)}
                </div>
            </div>

            <div class="pull-request-comment-follow-ups">
                ${host.replies.map((reply: PullRequestCommentPresenter) =>
                    getCommentReplyTemplate(host, reply, gettext_provider)
                )}
                ${getReplyFormTemplate(host, gettext_provider)}
            </div>
        </div>
    `,
});
