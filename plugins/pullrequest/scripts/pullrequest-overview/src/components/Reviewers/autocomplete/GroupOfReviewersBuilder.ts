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

import type { GroupOfItems } from "@tuleap/lazybox";
import type { User } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { TransformUsersToLazyboxItems } from "./UsersToLazyboxItemsTransformer";

export interface BuildGroupOfReviewers {
    buildEmpty(): GroupOfItems;
    buildEmptyNotEnoughCharacters(): GroupOfItems;
    buildLoading(): GroupOfItems;
    buildWithUsers(
        users: ReadonlyArray<User>,
        currently_selected_users: ReadonlyArray<User>,
    ): GroupOfItems;
}

export const GroupOfReviewersBuilder = (
    users_transformer: TransformUsersToLazyboxItems,
    $gettext: (msgid: string) => string,
): BuildGroupOfReviewers => {
    const empty_group: GroupOfItems = {
        label: $gettext("Matching users"),
        empty_message: $gettext("No matching users found"),
        is_loading: false,
        footer_message: "",
        items: [],
    };

    return {
        buildEmpty(): GroupOfItems {
            return empty_group;
        },
        buildEmptyNotEnoughCharacters(): GroupOfItems {
            return {
                ...empty_group,
                empty_message: $gettext("Type at least 3 characters"),
            };
        },
        buildLoading(): GroupOfItems {
            return {
                ...empty_group,
                empty_message: "",
                is_loading: true,
            };
        },
        buildWithUsers(
            users: ReadonlyArray<User>,
            currently_selected_users: ReadonlyArray<User>,
        ): GroupOfItems {
            return {
                ...empty_group,
                is_loading: false,
                items: users_transformer.buildForDropdown(users, currently_selected_users),
            };
        },
    };
};
