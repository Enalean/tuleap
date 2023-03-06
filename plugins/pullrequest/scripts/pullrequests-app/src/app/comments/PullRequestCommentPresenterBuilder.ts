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

import {
    EVENT_TYPE_ABANDON,
    EVENT_TYPE_MERGE,
    EVENT_TYPE_REBASE,
    EVENT_TYPE_REOPEN,
    EVENT_TYPE_UPDATE,
    TYPE_EVENT_PULLREQUEST_ACTION,
    TYPE_INLINE_COMMENT,
} from "@tuleap/plugin-pullrequest-constants";
import type {
    CommentOnFile,
    ActionOnPullRequestEvent,
    PullRequest,
} from "@tuleap/plugin-pullrequest-rest-api-types";
import type {
    PullRequestInlineCommentPresenter,
    PullRequestCommentPresenter,
    SupportedTimelineItem,
    PullRequestCommentFile,
} from "@tuleap/plugin-pullrequest-comments";
import {
    getUserAbandonedPullRequest,
    getUserMergePullRequest,
    getUserRebasePullRequest,
    getUserUpdatePullRequest,
    getUserReopenedPullRequest,
} from "../gettext-catalog";

export interface AngularUIRouterState {
    readonly href: (name: string, url_parameters: Record<string, unknown>) => string;
}

export const PullRequestCommentPresenterBuilder = {
    fromFileDiffComment: (comment: CommentOnFile): PullRequestInlineCommentPresenter => ({
        id: comment.id,
        user: comment.user,
        post_date: comment.post_date,
        content: replaceLineReturns(comment.content),
        type: TYPE_INLINE_COMMENT,
        is_outdated: false,
        is_inline_comment: true,
        unidiff_offset: comment.unidiff_offset,
        position: comment.position,
        file_path: comment.file_path,
        parent_id: comment.parent_id,
        is_file_diff_comment: true,
        color: comment.color,
    }),
    fromTimelineEvent: (
        $state: AngularUIRouterState,
        payload: SupportedTimelineItem,
        pull_request: PullRequest
    ): PullRequestCommentPresenter => {
        const id = payload.type === TYPE_EVENT_PULLREQUEST_ACTION ? 0 : payload.id;
        const is_inline_comment = payload.type === TYPE_INLINE_COMMENT;
        const is_outdated = is_inline_comment ? payload.is_outdated : false;
        const file = is_inline_comment ? buildFilePresenter(payload, $state, pull_request) : {};

        return {
            id,
            user: payload.user,
            content: getContentMessage(payload),
            type: payload.type,
            is_outdated,
            is_inline_comment,
            is_file_diff_comment: false,
            post_date: payload.post_date,
            ...file,
            ...buildThreadPresenter(payload),
        };
    },
};

function buildThreadPresenter(payload: SupportedTimelineItem): {
    parent_id: number;
    color: string;
} {
    if (payload.type === TYPE_EVENT_PULLREQUEST_ACTION) {
        return {
            parent_id: 0,
            color: "",
        };
    }
    return {
        parent_id: payload.parent_id,
        color: payload.color,
    };
}
function buildFilePresenter(
    payload: CommentOnFile,
    $state: AngularUIRouterState,
    pull_request: PullRequest
): { file: PullRequestCommentFile } {
    return {
        file: {
            file_url: $state.href("diff", {
                id: pull_request.id,
                file_path: payload.file_path,
                comment_id: payload.id,
            }),
            file_path: payload.file_path,
            unidiff_offset: payload.unidiff_offset,
            position: payload.position,
        },
    };
}

function replaceLineReturns(content: string): string {
    return content.replace(/(?:\r\n|\r|\n)/g, "<br/>");
}

function getContentMessage(payload: SupportedTimelineItem): string {
    if (payload.type === TYPE_EVENT_PULLREQUEST_ACTION) {
        return getTimelineEventMessage(payload);
    }

    return replaceLineReturns(payload.content);
}

function getTimelineEventMessage(event: ActionOnPullRequestEvent): string {
    switch (event.event_type) {
        case EVENT_TYPE_UPDATE:
            return getUserUpdatePullRequest();
        case EVENT_TYPE_REBASE:
            return getUserRebasePullRequest();
        case EVENT_TYPE_MERGE:
            return getUserMergePullRequest();
        case EVENT_TYPE_ABANDON:
            return getUserAbandonedPullRequest();
        case EVENT_TYPE_REOPEN:
            return getUserReopenedPullRequest();
        default:
            return "";
    }
}
