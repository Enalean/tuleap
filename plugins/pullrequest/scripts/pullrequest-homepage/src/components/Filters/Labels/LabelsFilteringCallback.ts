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

import type { LazyboxItem } from "@tuleap/lazybox";
import type { SelectorsDropdownFilterItemsCallback } from "@tuleap/plugin-pullrequest-selectors-dropdown";
import { isLabel } from "./LabelsSelectorEntry";

export const LabelsFilteringCallback: SelectorsDropdownFilterItemsCallback = (
    query: string,
    items: LazyboxItem[],
) => {
    const lowercase_query = query.toLowerCase();
    if (lowercase_query === "") {
        return items;
    }

    return items.filter(
        (label) =>
            isLabel(label.value) && label.value.label.toLowerCase().includes(lowercase_query),
    );
};
