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
import { sanitize } from "dompurify";

export const TAG_NAME = "tuleap-pullrequest-comment";
export type HostElement = PullRequestComment & HTMLElement;

type MapOfClasses = Record<string, boolean>;

export interface PullRequestUser {
    readonly avatar_url: string;
    readonly display_name: string;
    readonly user_url: string;
}

interface PullRequestCommentFile {
    readonly file_path: string;
    readonly file_url: string;
}

export interface PullRequestCommentPresenter {
    readonly user: PullRequestUser;
    readonly post_date: string;
    readonly content: string;
    readonly type: "inline-comment" | "comment" | "timeline-event";
    readonly is_outdated: boolean;
    readonly file?: PullRequestCommentFile;
    readonly is_inline_comment: boolean;
}

interface PullRequestComment {
    readonly comment: PullRequestCommentPresenter;
    readonly content: () => HTMLElement;
    readonly post_rendering_callback: () => void;
}

const getCommentClasses = (host: PullRequestComment): MapOfClasses => {
    const classes: MapOfClasses = {
        "pull-request-event": true,
        "is-outdated": host.comment.is_outdated,
        "is-inline-comment": host.comment.is_inline_comment,
    };

    classes[host.comment.type] = true;

    return classes;
};

const displayFileNameIfNeeded = (host: PullRequestComment): UpdateFunction<PullRequestComment> => {
    if (!host.comment.file) {
        return html``;
    }

    if (!host.comment.is_outdated) {
        return html`
            <span
                class="pull-request-event-file-path"
                data-test="pullrequest-comment-with-link-to-file"
            >
                <a href="${host.comment.file.file_url}">
                    <i class="fa-regular fa-file-alt" aria-hidden="true"></i> ${host.comment.file
                        .file_path}
                </a>
            </span>
        `;
    }

    return html`
        <span class="pull-request-event-file-path" data-test="pullrequest-comment-only-file-name">
            <i class="fa-regular fa-file-alt" aria-hidden="true"></i> ${host.comment.file.file_path}
        </span>
    `;
};

function renderFactory(fn: (host: HostElement) => UpdateFunction<PullRequestComment>) {
    return (host: HostElement): UpdateFunction<PullRequestComment> => {
        const component = fn(host);
        if (host.post_rendering_callback) {
            // Wait for component to be returned to trigger the callback
            setTimeout(() => host.post_rendering_callback());
        }

        return component;
    };
}

export const PullRequestComment = define<PullRequestComment>({
    tag: TAG_NAME,
    comment: undefined,
    post_rendering_callback: undefined,
    content: renderFactory(
        (host) => html`
            <div class="${getCommentClasses(host)}" data-test="pullrequest-comment">
                <div class="tlp-avatar">
                    <img
                        src="${host.comment.user.avatar_url}"
                        class="media-object"
                        aria-hidden="true"
                    />
                </div>

                <div class="pull-request-event-content">
                    <div class="pull-request-event-content-info">
                        <a href="${host.comment.user.user_url}">${host.comment.user.display_name}</a
                        >,
                        <span class="tlp-text-muted">${host.comment.post_date}</span>
                    </div>

                    ${displayFileNameIfNeeded(host)}

                    <p innerHTML="${sanitize(host.comment.content)}"></p>
                </div>
            </div>
        `
    ),
});
