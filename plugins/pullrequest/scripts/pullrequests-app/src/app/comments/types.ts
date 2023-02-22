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

import type { InlineCommentPosition, PullRequestUser } from "@tuleap/plugin-pullrequest-comments";

export interface FileDiffCommentPayload {
    readonly id: number;
    readonly content: string;
    readonly user: PullRequestUser;
    readonly post_date: string;
    readonly unidiff_offset: number;
    readonly position: InlineCommentPosition;
    readonly file_path: string;
    readonly parent_id: number;
    readonly color: string;
}
