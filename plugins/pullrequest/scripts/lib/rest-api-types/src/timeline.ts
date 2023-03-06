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
    CommentType,
    InlineCommentPosition,
    PullRequestEventType,
    ActionOnPullRequestEventType,
} from "@tuleap/plugin-pullrequest-constants";

import type { User } from "@tuleap/core-rest-api-types";
import type {
    GlobalCommentType,
    InlineCommentType,
    PullRequestActionEventType,
    ReviewerChangeEventType,
} from "@tuleap/plugin-pullrequest-constants/src/constants";

interface CommonComment {
    readonly id: number;
    readonly post_date: string;
    readonly content: string;
    readonly type: CommentType;
    readonly user: User;
    readonly parent_id: number;
    readonly color: string;
}

export interface GlobalComment extends CommonComment {
    readonly type: GlobalCommentType;
}

export interface CommentOnFile extends CommonComment {
    readonly type: InlineCommentType;
    readonly is_outdated: boolean;
    readonly file_path: string;
    readonly position: InlineCommentPosition;
    readonly unidiff_offset: number;
}

export interface PullRequestEvent {
    readonly type: PullRequestEventType;
    readonly post_date: string;
    readonly user: User;
}

export interface ActionOnPullRequestEvent extends PullRequestEvent {
    readonly type: PullRequestActionEventType;
    readonly event_type: ActionOnPullRequestEventType;
}

export interface ReviewerChangeEvent extends PullRequestEvent {
    readonly type: ReviewerChangeEventType;
}

export type PullRequestComment = GlobalComment | CommentOnFile;
export type TimelineItem =
    | GlobalComment
    | CommentOnFile
    | ActionOnPullRequestEvent
    | ReviewerChangeEvent;
