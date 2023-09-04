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

export const PULL_REQUEST_COMMENT_SKELETON_ELEMENT_TAG_NAME = "tuleap-pullrequest-comment-skeleton";

export type HostElement = PullRequestCommentSkeleton & HTMLElement;

interface PullRequestCommentSkeleton {
    readonly content: () => HTMLElement;
    readonly has_replies: boolean;
}

const getFollowUpsSkeletons = (
    host: PullRequestCommentSkeleton,
): UpdateFunction<PullRequestCommentSkeleton> => {
    if (!host.has_replies) {
        return html``;
    }

    return html`
        <div class="pull-request-comment-follow-ups" data-test="skeleton-follow-ups-section">
            <div class="pull-request-comment-follow-up">
                <div class="pull-request-comment pull-request-comment-follow-up-content">
                    <div class="pull-request-comment-skeleton-avatar"></div>
                    <div class="pull-request-comment-content">
                        <div data-test="pull-request-comment-body">
                            <div class="pull-request-comment-content-info">
                                <div class="pull-request-comment-author-and-date">
                                    <span class="tlp-skeleton-text"></span>
                                </div>
                            </div>

                            <p class="pull-request-comment-text">
                                <span class="tlp-skeleton-text"></span>
                                <span class="tlp-skeleton-text"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
};

export const PullRequestCommentSkeletonComponent = define<PullRequestCommentSkeleton>({
    tag: PULL_REQUEST_COMMENT_SKELETON_ELEMENT_TAG_NAME,
    has_replies: true,
    content: (host) => html`
        <div class="pull-request-comment-component pull-request-comment-skeleton">
            <div class="pull-request-comment">
                <div class="pull-request-comment-skeleton-avatar"></div>
                <div class="pull-request-comment-content">
                    <div data-test="pull-request-comment-body">
                        <div class="pull-request-comment-content-info">
                            <div class="pull-request-comment-author-and-date">
                                <span class="tlp-skeleton-text"></span>
                            </div>
                        </div>

                        <p class="pull-request-comment-text">
                            <span class="tlp-skeleton-text"></span>
                            <span class="tlp-skeleton-text"></span>
                        </p>
                    </div>
                </div>
            </div>
            ${getFollowUpsSkeletons(host)}
        </div>
    `,
});
