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

import type { LazyboxItem } from "@tuleap/lazybox";
import type { User } from "@tuleap/plugin-pullrequest-rest-api-types";

export interface TransformUsersToLazyboxItems {
    buildForDropdown(
        users: ReadonlyArray<User>,
        currently_selected_users: ReadonlyArray<User>,
    ): ReadonlyArray<LazyboxItem>;
    buildForSelection(users: ReadonlyArray<User>): ReadonlyArray<LazyboxItem>;
}

const transformUsersToLazyboxItems = (
    users: ReadonlyArray<User>,
    is_user_selected: (user: User) => boolean,
): ReadonlyArray<LazyboxItem> =>
    users.map((user) => ({
        id: String(user.id),
        value: {
            ...user,
        },
        is_disabled: is_user_selected(user),
    }));

export const UsersToLazyboxItemsTransformer = (): TransformUsersToLazyboxItems => ({
    buildForDropdown(
        users: ReadonlyArray<User>,
        currently_selected_users: ReadonlyArray<User>,
    ): ReadonlyArray<LazyboxItem> {
        return transformUsersToLazyboxItems(
            users,
            (user: User) =>
                currently_selected_users.findIndex(
                    (selected_user) => selected_user.id === user.id,
                ) !== -1,
        );
    },
    buildForSelection(users: ReadonlyArray<User>): ReadonlyArray<LazyboxItem> {
        return transformUsersToLazyboxItems(users, () => false);
    },
});
