/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import fr_FR from "../po/fr_FR.po";
import type { Lazybox } from "@tuleap/lazybox";
import type { User } from "@tuleap/core-rest-api-types";
import { initGettextSync } from "@tuleap/gettext";
import { getAssignableUserTemplate, getSelectedUsers } from "./AssignableUserTemplate";
import type { FetchMatchingUsers } from "./UsersAutocompleter";
import { UsersAutocompleter } from "./UsersAutocompleter";
import { GroupOfUsersBuilder } from "./GroupOfUsersBuilder";
import { UsersToLazyboxItemsTransformer } from "./UsersToLazyboxItemsTransformer";
import { fetchMatchingUsers } from "./api/rest-querier";

type OnSelectionCallback = (selected_users: ReadonlyArray<User>) => void;

export const initUsersAutocompleter = (
    lazybox: Lazybox,
    already_selected_users: ReadonlyArray<User>,
    selection_callback: OnSelectionCallback,
    locale: string = "en_US",
    fetch_matching_users_callback: FetchMatchingUsers = fetchMatchingUsers,
): void => {
    const gettext_provider = initGettextSync("lazybox-users-autocomplete", { fr_FR }, locale);

    const users_transformer = UsersToLazyboxItemsTransformer();
    const group_builder = GroupOfUsersBuilder(users_transformer, gettext_provider);
    const autocompleter = UsersAutocompleter(group_builder, fetch_matching_users_callback);

    lazybox.options = {
        is_multiple: true,
        placeholder: gettext_provider.gettext("Search users by their names"),
        templating_callback: getAssignableUserTemplate,
        search_input_callback: (query): void => {
            autocompleter.autocomplete(lazybox, already_selected_users, query);
        },
        selection_callback: (selected_users): void => {
            already_selected_users = getSelectedUsers(selected_users);

            selection_callback(already_selected_users);
        },
    };
    lazybox.replaceDropdownContent([group_builder.buildEmpty()]);
    lazybox.replaceSelection(users_transformer.buildForSelection(already_selected_users));
};
