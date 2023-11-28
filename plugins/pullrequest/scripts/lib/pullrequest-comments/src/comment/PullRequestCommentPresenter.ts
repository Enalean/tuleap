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

import { Option } from "@tuleap/option";
import type {
    GlobalCommentType,
    InlineCommentPosition,
    InlineCommentType,
    CommentTextFormat,
} from "@tuleap/plugin-pullrequest-constants";
import { TYPE_GLOBAL_COMMENT, TYPE_INLINE_COMMENT } from "@tuleap/plugin-pullrequest-constants";
import type {
    PullRequestComment,
    User,
    EditedComment,
} from "@tuleap/plugin-pullrequest-rest-api-types";

export interface PullRequestCommentFile {
    readonly file_path: string;
    readonly file_url: string;
    readonly position: InlineCommentPosition;
    readonly unidiff_offset: number;
    readonly is_displayed: boolean;
}

export interface CommonComment {
    readonly id: number;
    readonly user: User;
    readonly content: string;
    readonly raw_content: string;
    readonly post_processed_content: string;
    readonly format: CommentTextFormat | "";
    readonly post_date: string;
    readonly last_edition_date: Option<string>;
    readonly parent_id: number;
    color: string;
}

export type PullRequestGlobalCommentPresenter = CommonComment & {
    readonly type: GlobalCommentType;
};

export interface PullRequestInlineCommentPresenter extends CommonComment {
    readonly type: InlineCommentType;
    readonly file: PullRequestCommentFile;
    readonly is_outdated: boolean;
}

export type PullRequestCommentPresenter =
    | PullRequestGlobalCommentPresenter
    | PullRequestInlineCommentPresenter;

export const PullRequestCommentPresenter = {
    fromCommentReply: (
        parent_comment: PullRequestCommentPresenter,
        reply: PullRequestComment,
    ): PullRequestCommentPresenter => {
        const common = {
            id: reply.id,
            user: reply.user,
            post_date: reply.post_date,
            last_edition_date: Option.fromNullable(reply.last_edition_date),
            content: replaceLineReturns(reply.content),
            raw_content: reply.raw_content,
            post_processed_content: reply.post_processed_content,
            format: reply.format,
            parent_id: reply.parent_id,
            color: "",
        };

        if (parent_comment.type === TYPE_GLOBAL_COMMENT && reply.type === TYPE_GLOBAL_COMMENT) {
            return {
                ...common,
                type: TYPE_GLOBAL_COMMENT,
            };
        }

        if (parent_comment.type === TYPE_INLINE_COMMENT && reply.type === TYPE_INLINE_COMMENT) {
            return {
                ...common,
                type: TYPE_INLINE_COMMENT,
                is_outdated: false,
                file: parent_comment.file,
            };
        }

        throw new Error("Expected the root comment and the reply to have the same type.");
    },
    fromEditedComment: (
        original_comment: PullRequestCommentPresenter,
        edited_comment: EditedComment,
    ): PullRequestCommentPresenter => {
        return {
            ...original_comment,
            last_edition_date: Option.fromValue(edited_comment.last_edition_date),
            content: replaceLineReturns(edited_comment.content),
            raw_content: edited_comment.raw_content,
            post_processed_content: edited_comment.post_processed_content,
        };
    },
};

function replaceLineReturns(content: string): string {
    return content.replace(/(?:\r\n|\r|\n)/g, "<br/>");
}
