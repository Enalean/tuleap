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

export type RegisteredUserWithAvatar = {
    readonly id: number;
    readonly uri: string;
    readonly user_url: string;
    readonly real_name: string;
    readonly display_name: string;
    readonly username: string;
    readonly ldap_id: string;
    readonly avatar_url: string;
    readonly is_anonymous: false;
    readonly has_avatar: boolean;
};

export type AnonymousUserWithAvatar = {
    readonly id: null;
    readonly uri: null;
    readonly user_url: null;
    readonly real_name: null;
    readonly display_name: string;
    readonly username: null;
    readonly ldap_id: null;
    readonly avatar_url: string;
    readonly is_anonymous: true;
    readonly has_avatar: true;
};

export type AnonymousUserWithEmailAndStatus = AnonymousUserWithAvatar & {
    readonly email: string;
    readonly status: null;
};

export type RegisteredUserWithEmailAndStatus = RegisteredUserWithAvatar & {
    readonly email: string;
    readonly status: string;
};

export type UserWithEmailAndStatus =
    | RegisteredUserWithEmailAndStatus
    | AnonymousUserWithEmailAndStatus;
