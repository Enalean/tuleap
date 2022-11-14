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

import type { AnonymousUserWithAvatar, RegisteredUserWithAvatar } from "./users";
import type { HTMLFormat, TextFormat } from "@tuleap/plugin-tracker-constants";

type CommonMarkComment = {
    readonly format: HTMLFormat;
    readonly commonmark: string;
};

type TextOrHTMLComment = {
    readonly format: TextFormat | HTMLFormat;
};

type CommentRepresentation = (CommonMarkComment | TextOrHTMLComment) & {
    readonly post_processed_body: string;
};

type ChangesetWithAnonymousSubmitter = {
    readonly email: string;
    readonly submitted_by_details: AnonymousUserWithAvatar;
};

type ChangesetWithRegisteredSubmitter = {
    readonly email: null;
    readonly submitted_by_details: RegisteredUserWithAvatar;
};

export type ChangesetWithCommentRepresentation = (
    | ChangesetWithAnonymousSubmitter
    | ChangesetWithRegisteredSubmitter
) & {
    readonly id: number;
    readonly submitted_on: string;
    readonly last_comment: CommentRepresentation;
    readonly last_modified_by: AnonymousUserWithAvatar | RegisteredUserWithAvatar;
    readonly last_modified_date: string;
};
