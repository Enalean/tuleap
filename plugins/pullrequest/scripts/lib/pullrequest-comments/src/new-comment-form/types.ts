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

import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";

import type {
    InlineCommentPosition,
    InlineCommentType,
    GlobalCommentType,
} from "@tuleap/plugin-pullrequest-constants";
import type {
    PullRequestGlobalCommentPresenter,
    PullRequestInlineCommentPresenter,
} from "../comment/PullRequestCommentPresenter";
import type { NewCommentFormPresenter } from "./NewCommentFormPresenter";
import type { PullRequestComment } from "@tuleap/plugin-pullrequest-rest-api-types";

type BaseCommentCreationContext = {
    readonly pull_request_id: number;
    readonly user_id: number;
};

export type GlobalCommentCreationContext = BaseCommentCreationContext & {
    readonly type: GlobalCommentType;
};

export type CommentOnFileCreationContext = BaseCommentCreationContext & {
    readonly type: InlineCommentType;
    readonly comment_context: InlineCommentContext;
};

export type ReplyToGlobalCommentContext = BaseCommentCreationContext & {
    readonly type: GlobalCommentType;
    readonly root_comment: PullRequestGlobalCommentPresenter;
};

export type ReplyToCommentOnFileContext = BaseCommentCreationContext & {
    readonly type: InlineCommentType;
    readonly root_comment: PullRequestInlineCommentPresenter;
};

export type CommentCreationContext = GlobalCommentCreationContext | CommentOnFileCreationContext;

export type ReplyCreationContext = ReplyToCommentOnFileContext | ReplyToGlobalCommentContext;

export type CommentContext = CommentCreationContext | ReplyCreationContext;

export type InlineCommentContext = {
    readonly file_path: string;
    readonly unidiff_offset: number;
    readonly position: InlineCommentPosition;
};

export type SaveComment = {
    saveComment(
        new_comment: NewCommentFormPresenter,
        context: CommentContext,
    ): ResultAsync<PullRequestComment, Fault>;
};
