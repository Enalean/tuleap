/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

export type AnonymousCommentAuthor = {
    readonly display_name: string;
    readonly avatar_uri: string;
};

export type RegisteredCommentAuthor = {
    readonly display_name: string;
    readonly profile_uri: string;
    readonly avatar_uri: string;
};

export type CommentAuthor = AnonymousCommentAuthor | RegisteredCommentAuthor;

type AnonymousComment = {
    readonly email: string;
    readonly submitted_by: AnonymousCommentAuthor;
};

type RegisteredUserComment = {
    readonly submitted_by: RegisteredCommentAuthor;
};

export type FollowUpComment = (AnonymousComment | RegisteredUserComment) & {
    readonly submission_date: string;
    readonly last_modified_by: CommentAuthor;
    readonly last_modified_date: string;
    readonly body: string;
};
