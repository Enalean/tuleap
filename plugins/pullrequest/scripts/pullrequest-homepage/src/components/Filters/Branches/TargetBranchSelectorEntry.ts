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
import type { Branch } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { SelectorEntry } from "@tuleap/plugin-pullrequest-selectors-dropdown";
import type { StoreListFilters } from "../ListFiltersStore";
import { BranchTemplatingCallback } from "./BranchTemplatingCallback";
import { BranchFilteringCallback } from "./BranchFilteringCallback";
import { BranchesLoader } from "./BranchesLoader";
import { TargetBranchFilterBuilder, TYPE_FILTER_TARGET_BRANCH } from "./TargetBranchFilter";

export const isBranch = (item_value: unknown): item_value is Branch =>
    typeof item_value === "object" && item_value !== null && "name" in item_value;

export const TargetBranchSelectorEntry = (
    $gettext: (string: string) => string,
    on_error_callback: (fault: Fault) => void,
    filters_store: StoreListFilters,
    repository_id: number,
): SelectorEntry => ({
    entry_name: $gettext("Branch"),
    isDisabled: (): boolean => filters_store.hasAFilterWithType(TYPE_FILTER_TARGET_BRANCH),
    config: {
        placeholder: $gettext("Target branch name"),
        label: $gettext("Matching branches"),
        empty_message: $gettext("No matching branch"),
        getDisabledMessage: () => $gettext("You can only filter on one target branch"),
        templating_callback: BranchTemplatingCallback,
        loadItems: BranchesLoader(on_error_callback, repository_id),
        filterItems: BranchFilteringCallback,
        onItemSelection: (item: unknown): void => {
            if (!isBranch(item)) {
                return;
            }

            filters_store.storeFilter(TargetBranchFilterBuilder($gettext).fromBranch(item));
        },
    },
});
