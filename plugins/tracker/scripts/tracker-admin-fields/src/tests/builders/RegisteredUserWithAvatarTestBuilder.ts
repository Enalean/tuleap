/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

import type { RegisteredUserWithAvatar } from "@tuleap/plugin-tracker-rest-api-types";

export class RegisteredUserWithAvatarTestBuilder {
    private id = 101;
    private readonly uri = "users/103";
    private readonly user_url = "/users/user1";
    private readonly real_name = "User 1";
    private readonly display_name = "user1";
    private readonly username = "user1";
    private readonly ldap_id = "";
    private readonly avatar_url = "https://tuleap.example.com/users/user1/avatar-1234.png";
    private readonly has_avatar = true;

    private constructor() {}

    public static aRegisteredUserWithAvatar(): RegisteredUserWithAvatarTestBuilder {
        return new RegisteredUserWithAvatarTestBuilder();
    }

    public withId(id: number): RegisteredUserWithAvatarTestBuilder {
        this.id = id;
        return this;
    }

    public build(): RegisteredUserWithAvatar {
        return {
            id: this.id,
            uri: this.uri,
            user_url: this.user_url,
            real_name: this.real_name,
            display_name: this.display_name,
            username: this.username,
            ldap_id: this.ldap_id,
            avatar_url: this.avatar_url,
            is_anonymous: false,
            has_avatar: this.has_avatar,
        };
    }
}
