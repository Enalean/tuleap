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

import type { User } from "@tuleap/core-rest-api-types";
import type {
    InlineCommentPosition,
    GlobalCommentType,
    CommentTextFormat,
} from "@tuleap/plugin-pullrequest-constants";

interface CommonNewComment {
    readonly id: number;
    readonly post_date: string;
    readonly content: string;
    readonly raw_content: string;
    readonly user: User;
    readonly parent_id: number;
    readonly color: string;
    readonly post_processed_content: string;
    readonly format: CommentTextFormat;
}

export interface NewCommentOnFile extends CommonNewComment {
    readonly unidiff_offset: number;
    readonly position: InlineCommentPosition;
    readonly file_path: string;
}

export interface NewGlobalComment extends CommonNewComment {
    readonly type: GlobalCommentType;
}

export type NewComment = NewCommentOnFile | NewGlobalComment;
