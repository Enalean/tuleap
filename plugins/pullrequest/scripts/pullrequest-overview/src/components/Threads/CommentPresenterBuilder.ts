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

import { Option } from "@tuleap/option";
import type {
    PullRequestCommentPresenter,
    PullRequestCommentFile,
} from "@tuleap/plugin-pullrequest-comments";
import type { GlobalComment, CommentOnFile } from "@tuleap/plugin-pullrequest-rest-api-types";
import { TYPE_INLINE_COMMENT } from "@tuleap/plugin-pullrequest-constants";
import { formatFilePathForUIRouter } from "../../helpers/file-path-formatter";

export const CommentPresenterBuilder = {
    fromPayload: (
        payload: GlobalComment | CommentOnFile,
        base_url: URL,
        pull_request_id: number,
    ): PullRequestCommentPresenter => {
        const last_edition_date: Option<string> = payload.last_edition_date
            ? Option.fromValue(payload.last_edition_date)
            : Option.nothing();

        const common = {
            id: payload.id,
            user: payload.user,
            post_date: payload.post_date,
            last_edition_date,
            parent_id: payload.parent_id,
            color: payload.color,
            content: replaceLineReturns(payload.content),
            raw_content: payload.raw_content,
            post_processed_content: payload.post_processed_content,
            format: payload.format,
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
