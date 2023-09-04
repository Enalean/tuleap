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
import type { PullRequestCommentComponentType } from "./PullRequestComment";
import { buildBodyForComment } from "./PullRequestCommentBodyTemplate";
import { buildFooterForComment } from "./PullRequestCommentFooterTemplate";
import { getCommentAvatarTemplate } from "../templates/CommentAvatarTemplate";
import type { PullRequestCommentPresenter } from "./PullRequestCommentPresenter";
import type { GettextProvider } from "@tuleap/gettext";

type MapOfClasses = Record<string, boolean>;

export const REPLY_ELEMENT_ROOT_CLASSNAME = "pull-request-comment-follow-up";

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

const getFollowUpClasses = (host: PullRequestCommentComponentType): MapOfClasses => {
    const classes: MapOfClasses = {
        [REPLY_ELEMENT_ROOT_CLASSNAME]: true,
    };

    if (host.comment.color) {
        classes[`pull-request-comment-follow-up-color`] = true;
        classes[`tlp-swatch-${host.comment.color}`] = true;
    }

    return classes;
};

export const getCommentReplyTemplate = (
    host: PullRequestCommentComponentType,
    reply: PullRequestCommentPresenter,
    gettext_provider: GettextProvider,
): UpdateFunction<PullRequestCommentComponentType> => html`
    <div class="${getFollowUpClasses(host)}">
        <div class="pull-request-comment pull-request-comment-follow-up-content">
            ${getCommentAvatarTemplate(reply.user)}
            <div class="${getCommentContentClasses(host)}">
                ${buildBodyForComment(host, reply, gettext_provider)}
                ${buildFooterForComment(host, reply, gettext_provider)}
            </div>
        </div>
    </div>
`;
