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

import { html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import type { User } from "@tuleap/plugin-pullrequest-rest-api-types";

type UserWithAvatar = Pick<User, "avatar_url">;

export const getCommentAvatarTemplate = (user: UserWithAvatar): UpdateFunction<HTMLElement> => html`
    <div class="tlp-avatar-medium" data-test="comment-author-avatar">
        <img
            src="${user.avatar_url}"
            class="media-object"
            aria-hidden="true"
            data-test="comment-author-avatar-img"
        />
    </div>
`;
