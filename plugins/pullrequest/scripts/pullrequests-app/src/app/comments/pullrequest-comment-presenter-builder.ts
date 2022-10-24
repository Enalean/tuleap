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

import {
    getUserAbandonedPullRequest,
    getUserMergePullRequest,
    getUserRebasePullRequest,
    getUserUpdatePullRequest,
} from "../gettext-catalog";
import type { PullRequestUser, PullRequestCommentPresenter } from "./PullRequestComment";

export interface State {
    href: (name: string, url_parameters: Record<string, unknown>) => string;
}

export interface PullRequestData {
    id: number;
}

export interface FileDiffCommentPayload {
    content: string;
    user: PullRequestUser;
    post_date: string;
}

export interface TimelineEventPayload {
    is_inline_comment: boolean;
    post_date: string;
    file_url: string;
    content: string;
    file_path: string;
    type: "comment" | "inline-comment" | "timeline-event";
    event_type?: string;
    is_outdated: boolean;
    user: PullRequestUser;
}

interface BuildPullRequestCommentPresenter {
    fromFileDiffComment: (comment: FileDiffCommentPayload) => PullRequestCommentPresenter;
    fromTimelineEvent: (
        event: TimelineEventPayload,
        pull_request: PullRequestData
    ) => PullRequestCommentPresenter;
}

export const PullRequestCommentPresenterBuilder = (
    $state: State
): BuildPullRequestCommentPresenter => ({
    fromFileDiffComment: (comment: FileDiffCommentPayload): PullRequestCommentPresenter => {
        return {
            ...comment,
            content: replaceLineReturns(comment.content),
            type: "inline-comment",
            is_outdated: false,
            is_inline_comment: true,
        };
    },
    fromTimelineEvent: (
        event: TimelineEventPayload,
        pull_request: PullRequestData
    ): PullRequestCommentPresenter => {
        const is_inline_comment = event.type === "inline-comment";
        const file = is_inline_comment
            ? {
                  file: {
                      file_url: $state.href("diff", {
                          id: pull_request.id,
                          file_path: event.file_path,
                      }),
                      file_path: event.file_path,
                  },
              }
            : {};

        return {
            ...event,
            ...file,
            is_inline_comment,
            content: getContentMessage(event),
        };
    },
});

function replaceLineReturns(content: string): string {
    return content.replace(/(?:\r\n|\r|\n)/g, "<br/>");
}

function getContentMessage(event: TimelineEventPayload): string {
    if (event.type === "comment" || event.type === "inline-comment") {
        return replaceLineReturns(event.content);
    }

    return getTimelineEventMessage(event);
}

function getTimelineEventMessage(event: TimelineEventPayload): string {
    switch (event.event_type) {
        case "update":
            return getUserUpdatePullRequest();
        case "rebase":
            return getUserRebasePullRequest();
        case "merge":
            return getUserMergePullRequest();
        case "abandon":
            return getUserAbandonedPullRequest();
        default:
            return "";
    }
}
