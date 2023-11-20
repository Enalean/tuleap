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
import DOMPurify from "dompurify";
import type { GettextProvider } from "@tuleap/gettext";
import { FORMAT_COMMONMARK, TYPE_INLINE_COMMENT } from "@tuleap/plugin-pullrequest-constants";
import { getCommentAvatarTemplate } from "../../templates/CommentAvatarTemplate";
import { getHeaderTemplate } from "../../templates/CommentHeaderTemplate";
import type { PullRequestCommentPresenter } from "../PullRequestCommentPresenter";
import { buildFooterForComment } from "./PullRequestCommentReplyFooterTemplate";
import type { InternalPullRequestCommentReply } from "./PullRequestCommentReply";

type MapOfClasses = Record<string, boolean>;

export const REPLY_ELEMENT_ROOT_CLASSNAME = "pull-request-comment-follow-up";

export const getCommentContentClasses = (host: InternalPullRequestCommentReply): MapOfClasses => {
    const classes: MapOfClasses = {
        "pull-request-comment-content": true,
    };

    if (host.parent_element.comment.color && !host.is_in_edition_mode) {
        classes[`pull-request-comment-content-color`] = true;
        classes[`tlp-swatch-${host.parent_element.comment.color}`] = true;
    }

    return classes;
};

export const getFollowUpClasses = (host: InternalPullRequestCommentReply): MapOfClasses => {
    const classes: MapOfClasses = {
        [REPLY_ELEMENT_ROOT_CLASSNAME]: true,
    };

    if (host.parent_element.comment.color) {
        classes[`pull-request-comment-follow-up-color`] = true;
        classes[`tlp-swatch-${host.parent_element.comment.color}`] = true;
    }

    return classes;
};

export const getBodyClasses = (host: InternalPullRequestCommentReply): MapOfClasses => ({
    "pull-request-comment-outdated":
        host.comment.type === TYPE_INLINE_COMMENT && host.comment.is_outdated,
});

const getContent = (comment: PullRequestCommentPresenter): string => {
    if (comment.format === FORMAT_COMMONMARK) {
        return DOMPurify.sanitize(comment.post_processed_content, {
            ADD_TAGS: ["tlp-syntax-highlighting"],
        });
    }

    return DOMPurify.sanitize(comment.content);
};

const getCommentContentTemplate = (
    host: InternalPullRequestCommentReply,
    gettext_provider: GettextProvider,
): UpdateFunction<InternalPullRequestCommentReply> => {
    if (host.is_in_edition_mode) {
        return html` <tuleap-pullrequest-comment-edition-form
            class="pull-request-comment-content"
            controller="${host.controller.buildCommentEditionController(host)}"
            comment="${host.comment}"
            project_id="${host.controller.getProjectId()}"
        ></tuleap-pullrequest-comment-edition-form>`;
    }

    return html`
        <div class="${getCommentContentClasses(host)}">
            <div class="${getBodyClasses(host)}" data-test="pull-request-comment-body">
                <div class="pull-request-comment-content-info">
                    ${getHeaderTemplate(
                        host.comment.user,
                        host.relative_date_helper,
                        gettext_provider,
                        host.comment.post_date,
                        host.comment.last_edition_date,
                    )}
                </div>
                <p
                    class="pull-request-comment-text"
                    data-test="pull-request-comment-text"
                    innerHTML="${getContent(host.comment)}"
                ></p>
            </div>
            ${buildFooterForComment(host, gettext_provider)}
        </div>
    `;
};

export const getCommentReplyTemplate = (
    host: InternalPullRequestCommentReply,
    gettext_provider: GettextProvider,
): UpdateFunction<InternalPullRequestCommentReply> => html`
    <div class="${getFollowUpClasses(host)}" data-test="comment-reply">
        <div class="pull-request-comment pull-request-comment-follow-up-content">
            ${getCommentAvatarTemplate(host.comment.user)}
            ${getCommentContentTemplate(host, gettext_provider)}
        </div>
    </div>
`;
