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

import type { PullRequestCommentPresenter } from "./PullRequestCommentPresenter";
import { PullRequestCommentRepliesCollectionPresenter } from "./PullRequestCommentRepliesCollectionPresenter";
import { TYPE_GLOBAL_COMMENT, TYPE_INLINE_COMMENT } from "@tuleap/plugin-pullrequest-constants";

export interface StorePullRequestCommentReplies {
    getCommentReplies: (
        comment: PullRequestCommentPresenter,
    ) => PullRequestCommentRepliesCollectionPresenter;
    getAllRootComments: () => PullRequestCommentPresenter[];
    addRootComment: (comment: PullRequestCommentPresenter) => void;
    addReplyToComment: (
        comment: PullRequestCommentPresenter,
        reply: PullRequestCommentPresenter,
    ) => void;
}

function getOnlyGlobalCommentReplies(comment: PullRequestCommentPresenter): boolean {
    return comment.parent_id !== 0 && comment.type === TYPE_GLOBAL_COMMENT;
}

function getOnlyInlineCommentReplies(comment: PullRequestCommentPresenter): boolean {
    return comment.parent_id !== 0 && comment.type === TYPE_INLINE_COMMENT;
}

function getOnlyRootComments(comment: PullRequestCommentPresenter): boolean {
    return comment.parent_id === 0;
}

function sortRepliesByDate(replies: PullRequestCommentPresenter[]): PullRequestCommentPresenter[] {
    return replies.sort(
        (a: PullRequestCommentPresenter, b: PullRequestCommentPresenter) =>
            Date.parse(a.post_date) - Date.parse(b.post_date),
    );
}

function buildMapFromComments(
    comments: PullRequestCommentPresenter[],
): Map<number, PullRequestCommentPresenter[]> {
    const map = comments.reduce(
        (replies_by_comments, current_comment): Map<number, PullRequestCommentPresenter[]> => {
            const comment_replies = replies_by_comments.get(current_comment.parent_id);
            if (!comment_replies) {
                replies_by_comments.set(current_comment.parent_id, [current_comment]);

                return replies_by_comments;
            }

            replies_by_comments.set(
                current_comment.parent_id,
                comment_replies.concat([current_comment]),
            );

            return replies_by_comments;
        },
        new Map<number, PullRequestCommentPresenter[]>(),
    );

    map.forEach((replies: PullRequestCommentPresenter[], parent_id: number, map) => {
        map.set(parent_id, sortRepliesByDate(replies));
    });

    return map;
}

function addCommentReply(
    map: Map<number, PullRequestCommentPresenter[]>,
    comment: PullRequestCommentPresenter,
    reply: PullRequestCommentPresenter,
): void {
    const replies = map.get(comment.id);
    if (!replies) {
        map.set(comment.id, [reply]);
        return;
    }

    map.set(comment.id, sortRepliesByDate([...replies, reply]));
}

export const PullRequestCommentRepliesStore = (
    comments: readonly PullRequestCommentPresenter[],
): StorePullRequestCommentReplies => {
    const global_comments_replies_map = buildMapFromComments(
        comments.filter(getOnlyGlobalCommentReplies),
    );
    const inline_comments_replies_map = buildMapFromComments(
        comments.filter(getOnlyInlineCommentReplies),
    );
    const root_comments = comments.filter(getOnlyRootComments);

    return {
        getCommentReplies: (
            comment: PullRequestCommentPresenter,
        ): PullRequestCommentRepliesCollectionPresenter => {
            let replies;

            if (comment.type === TYPE_GLOBAL_COMMENT) {
                replies = global_comments_replies_map.get(comment.id);
            } else if (comment.type === TYPE_INLINE_COMMENT) {
                replies = inline_comments_replies_map.get(comment.id);
            }

            return replies
                ? PullRequestCommentRepliesCollectionPresenter.fromReplies(replies)
                : PullRequestCommentRepliesCollectionPresenter.buildEmpty();
        },
        getAllRootComments: (): PullRequestCommentPresenter[] => root_comments,
        addRootComment: (comment: PullRequestCommentPresenter): void => {
            if (comment.type !== TYPE_INLINE_COMMENT && comment.type !== TYPE_GLOBAL_COMMENT) {
                return;
            }

            if (comment.type === TYPE_INLINE_COMMENT) {
                inline_comments_replies_map.set(comment.id, []);
            }

            if (comment.type === TYPE_GLOBAL_COMMENT) {
                global_comments_replies_map.set(comment.id, []);
            }

            root_comments.push(comment);
        },
        addReplyToComment: (
            comment: PullRequestCommentPresenter,
            reply: PullRequestCommentPresenter,
        ): void => {
            if (comment.type === TYPE_INLINE_COMMENT) {
                addCommentReply(inline_comments_replies_map, comment, reply);
                return;
            }

            if (comment.type === TYPE_GLOBAL_COMMENT) {
                addCommentReply(global_comments_replies_map, comment, reply);
            }
        },
    };
};
