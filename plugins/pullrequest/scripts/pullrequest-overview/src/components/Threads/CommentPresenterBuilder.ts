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

import type {
    PullRequestCommentPresenter,
    PullRequestCommentFile,
    SupportedTimelineItem,
} from "@tuleap/plugin-pullrequest-comments";
import type {
    ActionOnPullRequestEvent,
    CommentOnFile,
} from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    EVENT_TYPE_ABANDON,
    EVENT_TYPE_MERGE,
    EVENT_TYPE_REBASE,
    EVENT_TYPE_REOPEN,
    EVENT_TYPE_UPDATE,
    TYPE_EVENT_PULLREQUEST_ACTION,
    TYPE_INLINE_COMMENT,
} from "@tuleap/plugin-pullrequest-constants";
import type { CommentTextFormat } from "@tuleap/plugin-pullrequest-constants";
import { formatFilePathForUIRouter } from "../../helpers/file-path-formatter";

export const CommentPresenterBuilder = {
    fromPayload: (
        payload: SupportedTimelineItem,
        base_url: URL,
        pull_request_id: number,
        $gettext: (msgid: string) => string,
    ): PullRequestCommentPresenter => {
        const common = {
            id: payload.type === TYPE_EVENT_PULLREQUEST_ACTION ? 0 : payload.id,
            user: payload.user,
            post_date: payload.post_date,
            ...getThreadData(payload),
            ...getContentData(payload, $gettext),
        };

        if (payload.type === TYPE_INLINE_COMMENT) {
            return {
                ...common,
                type: TYPE_INLINE_COMMENT,
                is_outdated: payload.is_outdated,
                file: buildFilePresenter(payload, base_url, pull_request_id),
            };
        }

        return {
            ...common,
            type: payload.type,
        };
    },
};

function getThreadData(payload: SupportedTimelineItem): {
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

function getContentData(
    payload: SupportedTimelineItem,
    $gettext: (msgid: string) => string,
): {
    content: string;
    raw_content: string;
    post_processed_content: string;
    format: CommentTextFormat | "";
} {
    if (payload.type === TYPE_EVENT_PULLREQUEST_ACTION) {
        return {
            content: getContentMessage(payload, $gettext),
            raw_content: "",
            post_processed_content: "",
            format: "",
        };
    }

    return {
        content: getContentMessage(payload, $gettext),
        raw_content: payload.raw_content,
        post_processed_content: payload.post_processed_content,
        format: payload.format,
    };
}

function buildFilePresenter(
    payload: CommentOnFile,
    base_url: URL,
    pull_request_id: number,
): PullRequestCommentFile {
    const file_url = new URL(base_url);
    const formatted_file_path = formatFilePathForUIRouter(payload.file_path);

    file_url.hash = `#/pull-requests/${encodeURIComponent(
        pull_request_id,
    )}/files/diff-${encodeURIComponent(formatted_file_path)}/${encodeURIComponent(payload.id)}`;

    return {
        file_url: file_url.toString(),
        file_path: payload.file_path,
        unidiff_offset: payload.unidiff_offset,
        position: payload.position,
        is_displayed: true,
    };
}

function replaceLineReturns(content: string): string {
    return content.replace(/(?:\r\n|\r|\n)/g, "<br/>");
}

function getContentMessage(
    payload: SupportedTimelineItem,
    $gettext: (msgid: string) => string,
): string {
    if (payload.type === TYPE_EVENT_PULLREQUEST_ACTION) {
        return getTimelineEventMessage(payload, $gettext);
    }

    return replaceLineReturns(payload.content);
}

function getTimelineEventMessage(
    event: ActionOnPullRequestEvent,
    $gettext: (msgid: string) => string,
): string {
    switch (event.event_type) {
        case EVENT_TYPE_UPDATE:
            return $gettext("Has updated the pull request.");
        case EVENT_TYPE_REBASE:
            return $gettext("Has rebased the pull request.");
        case EVENT_TYPE_MERGE:
            return $gettext("Has merged the pull request.");
        case EVENT_TYPE_ABANDON:
            return $gettext("Has abandoned the pull request.");
        case EVENT_TYPE_REOPEN:
            return $gettext("Has reopened the pull request.");
        default:
            return "";
    }
}
