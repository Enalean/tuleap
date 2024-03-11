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
import type { LazyboxItem } from "@tuleap/lazybox";
import type { ProjectLabel } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { SelectorEntry } from "@tuleap/plugin-pullrequest-selectors-dropdown";
import type { StoreListFilters } from "../ListFiltersStore";
import { LabelsTemplatingCallback } from "./LabelsTemplatingCallback";
import { LabelsLoader } from "./LabelsLoader";
import { LabelsFilteringCallback } from "./LabelsFilteringCallback";
import { LabelFilterBuilder } from "./LabelFilter";
import { LazyboxItemLabelBuilder } from "./LazyboxItemLabelBuilder";

export const isLabel = (item_value: unknown): item_value is ProjectLabel =>
    typeof item_value === "object" &&
    item_value !== null &&
    "id" in item_value &&
    "label" in item_value;

export const LabelsSelectorEntry = (
    $gettext: (string: string) => string,
    on_error_callback: (fault: Fault) => void,
    filters_store: StoreListFilters,
    project_id: number,
): SelectorEntry => ({
    entry_name: $gettext("Labels"),
    isDisabled: (): boolean => false,
    config: {
        placeholder: $gettext("Label name"),
        label: $gettext("Matching labels"),
        empty_message: $gettext("No matching labels"),
        getDisabledMessage: () => "",
        templating_callback: LabelsTemplatingCallback($gettext),
        loadItems: LabelsLoader(
            on_error_callback,
            LazyboxItemLabelBuilder(filters_store),
            project_id,
        ),
        filterItems: LabelsFilteringCallback,
        onItemSelection(item: unknown): void {
            if (!isLabel(item)) {
                return;
            }

            filters_store.storeFilter(LabelFilterBuilder($gettext).fromLabel(item));
        },
        getDisabledItems: (items: LazyboxItem[]): LazyboxItem[] =>
            items.map(LazyboxItemLabelBuilder(filters_store).fromLazyboxItem),
    },
});
