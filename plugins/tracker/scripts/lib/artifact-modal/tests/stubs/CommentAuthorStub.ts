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

import type {
    AnonymousCommentAuthor,
    RegisteredCommentAuthor,
} from "../../src/domain/comments/FollowUpComment";

export const ANONYMOUS_DISPLAY_NAME = "Anonymous user";
export const DEFAULT_AVATAR_URI =
    "https://tuleap.example.com/themes/common/images/avatar_default.png";

export const CommentAuthorStub = {
    aRegisteredUser: (real_name: string, user_name: string): RegisteredCommentAuthor => ({
        display_name: `${real_name} (${user_name})`,
        avatar_uri: `https://tuleap.example.com/users/${user_name}/avatar-e0cc78.png`,
        profile_uri: `/users/${user_name}`,
    }),

    anAnonymousUser: (): AnonymousCommentAuthor => ({
        display_name: ANONYMOUS_DISPLAY_NAME,
        avatar_uri: DEFAULT_AVATAR_URI,
    }),
};
