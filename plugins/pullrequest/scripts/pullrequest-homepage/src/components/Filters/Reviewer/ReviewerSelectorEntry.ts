/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import type { Fault } from "@tuleap/fault";
import type { SelectorEntry } from "@tuleap/plugin-pullrequest-selectors-dropdown";
import type { StoreListFilters } from "../ListFiltersStore";
import { ReviewerFilterBuilder, TYPE_FILTER_REVIEWER } from "./ReviewerFilter";
import { UserTemplatingCallback } from "../Common/UserTemplatingCallback";
import { UserFilteringCallback } from "../Common/UserFilteringCallback";
import { isUser } from "../Common/UserTypeGuard";
import { ReviewersLoader } from "./ReviewersLoader";

export const ReviewerSelectorEntry = (
    $gettext: (string: string) => string,
    on_error_callback: (fault: Fault) => void,
    filters_store: StoreListFilters,
    repository_id: number,
): SelectorEntry => ({
    entry_name: $gettext("Reviewer"),
    isDisabled: () => filters_store.hasAFilterWithType(TYPE_FILTER_REVIEWER),
    config: {
        placeholder: $gettext("Reviewer"),
        label: $gettext("Matching users"),
        empty_message: $gettext("No matching user"),
        disabled_message: $gettext("You can only filter on one reviewer"),
        templating_callback: UserTemplatingCallback,
        loadItems: ReviewersLoader(on_error_callback, repository_id),
        filterItems: UserFilteringCallback,
        onItemSelection: (item: unknown): void => {
            if (!isUser(item)) {
                return;
            }

            filters_store.storeFilter(ReviewerFilterBuilder($gettext).fromReviewer(item));
        },
    },
});
