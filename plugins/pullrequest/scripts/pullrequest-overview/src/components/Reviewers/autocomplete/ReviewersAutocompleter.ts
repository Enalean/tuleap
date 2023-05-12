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

import { fetchMatchingUsers } from "../../../api/tuleap-rest-querier";
import type { Lazybox } from "@tuleap/lazybox";
import type { User } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { BuildGroupOfReviewers } from "./GroupOfReviewersBuilder";

export interface AutocompleteReviewers {
    autocomplete(lazybox: Lazybox, currently_selected_users: User[], query: string): void;
}

export const ReviewersAutocompleter = (
    group_builder: BuildGroupOfReviewers
): AutocompleteReviewers => {
    return {
        autocomplete(lazybox: Lazybox, currently_selected_users: User[], query: string): void {
            if (query.length <= 2) {
                lazybox.replaceDropdownContent([group_builder.buildEmptyNotEnoughCharacters()]);
                return;
            }

            lazybox.replaceDropdownContent([group_builder.buildLoading()]);

            fetchMatchingUsers(query).match(
                (users: User[]) => {
                    lazybox.replaceDropdownContent([
                        group_builder.buildWithUsers(users, currently_selected_users),
                    ]);
                },
                () => {
                    lazybox.replaceDropdownContent([group_builder.buildEmpty()]);
                }
            );
        },
    };
};
