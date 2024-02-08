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
import type { ProjectLabel } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { SelectorEntry } from "@tuleap/plugin-pullrequest-selectors-dropdown";
import { LabelsTemplatingCallback } from "./LabelsTemplatingCallback";
import { LabelsLoader } from "./LabelsLoader";
import { LabelsFilteringCallback } from "./LabelsFilteringCallback";

export const isLabel = (item_value: unknown): item_value is ProjectLabel =>
    typeof item_value === "object" && item_value !== null && "id" in item_value;

export const LabelsSelectorEntry = (
    $gettext: (string: string) => string,
    on_error_callback: (fault: Fault) => void,
    project_id: number,
): SelectorEntry => ({
    entry_name: $gettext("Labels"),
    isDisabled: (): boolean => false,
    config: {
        placeholder: $gettext("Label name"),
        label: $gettext("Matching labels"),
        empty_message: $gettext("No matching labels"),
        disabled_message: "",
        templating_callback: LabelsTemplatingCallback,
        loadItems: LabelsLoader(on_error_callback, project_id),
        filterItems: LabelsFilteringCallback,
        onItemSelection(): void {
            // Do nothing for the moment
        },
    },
});
