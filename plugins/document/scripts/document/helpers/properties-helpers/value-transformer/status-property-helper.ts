/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import type { Property, ListValue, Item, DefaultFileItem } from "../../../type";
import { getStatusFromMapping } from "../hardcoded-properties-mapping-helper";
import { assertListIsOnlyMultipleValue } from "./list-value-helper";

export function updateStatusProperty(property: Property, item: Item | DefaultFileItem): void {
    item.status = getItemStatus(property);
}

export function getItemStatus(property: Property): string {
    let status = "none";

    if (property && property.list_value && assertListIsOnlyMultipleValue(property.list_value)) {
        const multiple_list_value: ListValue = property.list_value[0];
        status = getStatusFromMapping(multiple_list_value.id);
    }

    return status;
}
